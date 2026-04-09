# Architecture — 404 Alert

Ce document décrit l'architecture technique du plugin 404 Alert et comment ses composants interagissent.

## Vue d'ensemble

Le plugin suit une architecture **orientée objet avec classes statiques**. Chaque responsabilité est encapsulée dans sa propre classe, ce qui facilite la maintenance et les tests.

```
Visiteur accède à /page-inexistante
        ↓
WordPress charge le template 404
        ↓
Hook `template_redirect` déclenche Alert404_Detector::on_template_redirect()
        ↓
├─ Validation IP (filter_var)
├─ Rate limiting (Alert404_RateLimiter)
│   ├─ Vérification cooldown par IP
│   └─ Vérification limite quotidienne
├─ Collecte des données (payload)
└─ Envoi email (Alert404_Mailer)
        ↓
Email HTML reçu par admin / Logs générés
```

## Composants

### 1. `Alert404_Detector` (class-detector.php)

**Responsabilité** : Détecte les erreurs 404 et orchestre le flux.

**Points d'entrée** :

- `Alert404_Detector::init()` — Enregistre le hook `template_redirect`
- `Alert404_Detector::on_template_redirect()` — Callback du hook

**Flux** :

1. Extrait l'IP source (supporte les proxies via `HTTP_X_FORWARDED_FOR`)
2. Valide l'IP avec `filter_var($ip, FILTER_VALIDATE_IP)`
3. Log les IPs invalides via `Alert404_Logger::log_invalid_ip()`
4. Appelle `Alert404_RateLimiter::check_and_increment($ip)`
5. Si autorisé, collecte le payload et appelle `Alert404_Mailer::send()`

**Données collectées** :

```php
[
    'url'        => URL demandée,
    'referrer'   => Referer HTTP (optionnel),
    'userAgent'  => User-Agent HTTP,
    'ip'         => Adresse IP source (validée),
    'occurredAt' => Timestamp ISO 8601
]
```

### 2. `Alert404_RateLimiter` (class-rate-limiter.php)

**Responsabilité** : Prévient les abus via rate limiting par IP et limite quotidienne.

**Caractéristiques** :

- **Pattern atomique** : Verrous légers pour éviter les race conditions
- **Double-vérification** : S'assure que le verrou est bien acquis avant de modifier les données

**Deux niveaux de rate limiting** :

#### 2.1 Cooldown par IP

- **Clé transient** : `404_alert_ip_` + `wp_hash(IP)`
- **TTL** : Défini par l'option `ip_cooldown` (défaut: 300 secondes)
- **Logique** :
  ```php
  Si (timestamp_actuel - dernier_timestamp) < cooldown
    → BLOQUÉ (log via Alert404_Logger::log_rate_limit_ip())
  Sinon
    → AUTORISÉ (transient mis à jour)
  ```

#### 2.2 Limite quotidienne

- **Clé transient** : `404_alert_global_` + date du jour (YYYY-MM-DD)
- **TTL** : Expiration à minuit UTC
- **Logique** :
  ```php
  Si compteur_aujourd'hui >= daily_limit
    → BLOQUÉ (log via Alert404_Logger::log_rate_limit_daily())
  Sinon
    → AUTORISÉ (compteur incrémenté)
  ```

**Système de verrous** :

- `acquire_lock($lock_key, $timeout)` : Acquiert un verrou avec double-vérification
- `release_lock($lock_key)` : Libère le verrou
- Les verrous ont un timeout court (5 secondes) pour éviter les deadlocks
- En cas de conflit, la requête est autorisée plutôt que bloquée (fail-open)

### 3. `Alert404_Mailer` (class-mailer.php)

**Responsabilité** : Envoie les notifications email avec support d'extensions.

**Points d'entrée** :

- `Alert404_Mailer::send(array $payload)` — Envoie un email

**Filtres d'extensibilité** :

```php
// Les plugins tiers peuvent modifier ces valeurs
apply_filters('404_alert_email_to', $to, $payload)
apply_filters('404_alert_email_subject', $subject, $payload)
apply_filters('404_alert_email_headers', $headers, $payload)
apply_filters('404_alert_email_body', $html, $payload)
```

**Actions d'extensibilité** :

```php
// Les plugins tiers peuvent écouter ces événements
do_action('404_alert_email_sent', $to, $subject, $payload)
do_action('404_alert_email_failed', $to, $subject, $payload)
```

**Vérification du succès** :

- Capture le retour booléen de `wp_mail()`
- Log via `Alert404_Logger::log_email_sent()` ou `::log_email_failed()`
- Déclenche les actions correspondantes

**Format de l'email** :

- Content-Type : `text/html; charset=UTF-8`
- Contenu formaté avec les données du payload
- JSON raw en `<pre>` pour débogage

### 4. `Alert404_Logger` (class-logger.php)

**Responsabilité** : Centralise le logging de tous les événements.

**Conditions de log** :

- Logs uniquement si `WP_DEBUG_LOG` est défini et activé
- Enregistre dans `wp-content/debug.log` via `error_log()`

**Événements loggés** :

| Méthode                  | Événement          | Contexte                        |
| ------------------------ | ------------------ | ------------------------------- |
| `log_404_detected()`     | `404_detected`     | IP, URL, données additionnelles |
| `log_rate_limit_ip()`    | `rate_limit_ip`    | IP, cooldown en secondes        |
| `log_rate_limit_daily()` | `rate_limit_daily` | Limite quotidienne              |
| `log_email_sent()`       | `email_sent`       | Destinataire, URL               |
| `log_email_failed()`     | `email_failed`     | Destinataire, raison            |
| `log_invalid_ip()`       | `invalid_ip`       | IP brute reçue                  |

**Format du log** :

```
[404-Alert] [YYYY-MM-DD HH:MM:SS] event_name: {"key": "value"}
```

### 5. `Alert404_Settings` (class-settings.php)

**Responsabilité** : Gère la page de réglages dans l'admin WordPress.

**Points d'entrée** :

- `Alert404_Settings::init()` — Enregistre les hooks admin
- `Alert404_Settings::render_page()` — Affiche la page

**Champs configurables** :

| Champ              | Clé           | Défaut      | Limites          |
| ------------------ | ------------- | ----------- | ---------------- |
| Email              | `email`       | Email admin | Doit être valide |
| Limite quotidienne | `daily_limit` | 500         | 1-10000          |
| Cooldown IP        | `ip_cooldown` | 300 sec     | 60-3600 sec      |

**Stockage** :

- Clé option WordPress : `404_alert_options`
- Type : Array sérialisé
- Sanitization : `Alert404_Settings::sanitize_options()`
- Protection CSRF : Nonce intégré via `settings_fields('404_alert')`

### 6. `Alert404_Template` (class-404-template.php)

**Responsabilité** : Charge un template 404 personnalisé (optionnel).

**Points d'entrée** :

- `Alert404_Template::init()` — Enregistre le filtre `template_include`
- `Alert404_Template::load_404_template()` — Charge le template

**Logique** :

1. Vérifie si c'est un 404 (`is_404()`)
2. Cherche le template personnalisé (`templates/404.php`)
3. Le charge s'il existe, sinon laisse WordPress utiliser son template par défaut

## Flux complet d'exécution

```
1. Visiteur accède à /page-inexistante
   ↓
2. WordPress génère une 404
   ↓
3. Hook `template_redirect` déclenche Alert404_Detector::on_template_redirect()
   ↓
4. Extraction + Validation IP
   - Si IP invalide → LOG invalid_ip + RETURN
   ↓
5. Alert404_RateLimiter::check_and_increment()
   - Acquire lock sur IP
   - Vérifier cooldown IP
   - Release lock
   - Acquire lock sur jour
   - Vérifier limite quotidienne
   - Release lock
   - Si bloqué → LOG rate_limit_* + RETURN
   ↓
6. Alert404_Detector::collect_payload()
   - Recueille URL, referrer, UA, IP, timestamp
   ↓
7. Alert404_Mailer::send()
   - Apply filters sur to, subject, headers, body
   - Appelle wp_mail()
   - Vérifie le retour
   - LOG success/failure
   - Do action sent/failed
   ↓
8. Email reçu par admin + Logs générés (si WP_DEBUG_LOG)
```

## Dépendances

### WordPress core

- `add_action()`, `add_filter()`, `do_action()`, `apply_filters()`
- `get_option()`, `update_option()`
- `get_transient()`, `set_transient()`, `delete_transient()`
- `wp_mail()`, `sanitize_email()`, `esc_html()`, `esc_attr()`
- `current_time()`, `wp_hash()`, `is_404()`

### Aucune dépendance externe

- Pas de Composer packages obligatoires
- Pas d'appels externes à des APIs
- Zéro JavaScript

## Extensibilité

Les plugins tiers peuvent étendre 404 Alert via :

### Filtres

```php
// Modifier le destinataire
add_filter('404_alert_email_to', function($to, $payload) {
    return 'admin@example.com';
}, 10, 2);

// Modifier le sujet
add_filter('404_alert_email_subject', function($subject, $payload) {
    return '[URGENT] ' . $subject;
}, 10, 2);

// Modifier le contenu HTML
add_filter('404_alert_email_body', function($html, $payload) {
    return $html . '<p>IP suspecte: ' . $payload['ip'] . '</p>';
}, 10, 2);
```

### Actions

```php
// Réagir à un email envoyé
add_action('404_alert_email_sent', function($to, $subject, $payload) {
    // Envoyer à Slack, DB, etc.
}, 10, 3);

// Réagir à un email non envoyé
add_action('404_alert_email_failed', function($to, $subject, $payload) {
    // Notifier l'admin, escalade, etc.
}, 10, 3);
```

## Considérations de performance

- **Transients vs. Options** : Utilise les transients pour les données temporelles (rate limiting)
- **Verrous légers** : N'utilise pas de tables de base de données, basé sur les transients
- **Pas de boucles coûteuses** : O(1) pour la plupart des opérations
- **Fail-safe** : Si un verrou ne peut pas être acquis, la requête est autorisée plutôt que bloquée

## Sécurité

- **Validation IP** : Utilise `filter_var(FILTER_VALIDATE_IP)`
- **Sanitization** : Utilise `sanitize_email()`, `esc_html()`, `esc_attr()`
- **Protection CSRF** : Nonces intégrés dans la page de réglages
- **Logging des IPs** : Enregistre les tentatives suspectes
- **Rate limiting** : Double protection (par IP + global)
- **Gestion d'erreurs** : Utilise try/finally pour libérer les verrous

## Limitations connues

1. **Pas de stockage persistant** : Les statistiques 404 ne sont pas sauvegardées en BD
2. **Pas de whitelist** : Toutes les 404 sont traitées de la même manière
3. **Pas de webhook** : Intégration limitée à l'email et les hooks WordPress
4. **Pas de dashboard** : Pas de visualisation des statistiques en temps réel
