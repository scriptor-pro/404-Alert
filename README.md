# 404 Alert — Plugin WordPress

Un plugin léger et autonome qui envoie un email à l'administrateur chaque fois qu'un visiteur accède à une page introuvable (erreur 404).

## Prérequis

Consulter `requirement.md` pour la liste complète des prérequis runtime, dev, tests et qualité.

## Fonctionnalités

- 📧 **Email instantané** : notification en HTML à chaque 404 détecté
- 🛡️ **Rate limiting** : protection contre les abus (5 min par IP, 500/jour max)
- ⚙️ **Configurabilité** : adresse email, seuils, cooldown via page de réglages
- 🚀 **Léger** : zéro dépendance, utilise uniquement WordPress core
- 🔒 **Sécurisé** : sanitization des données, protection XSS

## Installation

### 1. Télécharger le plugin

Cloner ou télécharger dans le répertoire des plugins WordPress :

```bash
cd wp-content/plugins
git clone https://github.com/baudouin/404-alert.git
# ou décompresser le ZIP fourni
```

### 2. Activer le plugin

- Accéder à `wp-admin > Extensions`
- Chercher **404 Alert**
- Cliquer sur **Activer**

### 3. Configurer (optionnel)

- Aller à `Réglages > 404 Alert`
- Par défaut :
  - **Email destinataire** : email de l'administrateur du site
  - **Limite journalière** : 500 emails/jour
  - **Délai par IP** : 300 secondes (5 minutes)
- Modifier si nécessaire et cliquer **Enregistrer les modifications**

## Utilisation

Une fois activé, le plugin détecte automatiquement les erreurs 404 et envoie un email pour chaque occurrence, dans les limites configurées.

### Email reçu

Chaque email contient :

```
Sujet: 404 sur Mon Site - https://monsite.com/page-inexistante

Corps HTML:
  404 détectée

  URL: https://monsite.com/page-inexistante
  Referer: https://google.com (ou vide)
  IP: 192.168.1.100
  User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64)...

  [JSON complet avec tous les détails]
```

### Logging

Les emails sont envoyés via le système natif WordPress `wp_mail()`.

Si vous utilisez un plugin SMTP (p. ex. WP Mail SMTP, Brevo, etc.), les emails passeront par ce plugin.

Pour déboguer :

- Vérifier les logs du plugin SMTP utilisé
- Ajouter un filtre `wp_mail_from` pour contrôler l'adresse d'envoi
- Consulter les logs du serveur mail

## Architecture

```
404-alert/
├── 404-alert.php              # Bootstrap plugin
├── includes/
│   ├── class-settings.php            # Page de réglages (Réglages > 404 Alert)
│   ├── class-rate-limiter.php        # Rate limiting par IP + global
│   ├── class-mailer.php              # Envoi email HTML
│   └── class-detector.php            # Détection 404 + collecte données
└── README.md
```

### Flux d'exécution

1. Visiteur accède à `/page-inexistante`
2. WordPress déclenche `is_404()` → hook `template_redirect`
3. `S404_Detector::on_template_redirect()`
   - Extrait IP, URL, referrer, user-agent
   - `S404_RateLimiter::check_and_increment()` vérifie les limites
   - Si OK → `S404_Mailer::send()` envoie l'email
4. Email reçu par l'admin

## Rate Limiting

### Par IP

- **Clé** : `404_alert_ip_{hash_ip}`
- **Valeur** : timestamp actuel
- **TTL** : cooldown configuré (défaut 300s = 5 min)
- **Comportement** : bloque si une requête de cette IP existe dans la fenêtre

### Global journalier

- **Clé** : `404_alert_global_{YYYY-MM-DD}`
- **Valeur** : compteur incrémenté
- **TTL** : 48h (survit à minuit UTC)
- **Comportement** : bloque au-delà de la limite journalière (défaut 500)

Ces transients sont stockés dans la base de données WordPress (ou dans un cache externe si configuré).

## Sécurité

### XSS Protection

Tous les champs affichés dans l'email HTML passent par `esc_html()` avant insertion.

### Validation & Sanitization

| Champ              | Validation                                     |
| ------------------ | ---------------------------------------------- |
| URL                | Chaîne, tronquée à 2000 caractères             |
| Referer            | Chaîne, tronquée à 2000 caractères             |
| User-Agent         | Chaîne, tronquée à 500 caractères              |
| IP                 | Extraite de `X-Forwarded-For` ou `REMOTE_ADDR` |
| Email destinataire | Validé par `sanitize_email()`                  |

### Limitations volontaires

- **Pas de base de données locale** : les 404 ne sont pas archivés (email uniquement)
- **Pas de détection de bots** : tous les 404 sont signalés (bloc par rate limit seulement)
- **Pas de logs persistants** : tout passe par le système email WordPress

## Dépannage

### Les emails ne sont pas reçus

1. Vérifier la configuration SMTP du site (via un plugin comme WP Mail SMTP)
2. Confirmer que `Réglages > 404 Alert > Email destinataire` est correct
3. Tester l'envoi d'email WordPress avec un plugin de test (p. ex. Test Mail Plugins)
4. Consulter les logs du serveur mail (`/var/log/mail.log` ou équivalent)

### Les emails sont envoyés même après le rate limit

Le rate limit fonctionne via les transients WordPress. Si les transients ne sont pas persistants (p. ex. stockage en mémoire), ils peuvent être perdus.

**Solution** : configurer un backend de cache persistant (Redis, Memcached, ou DB WordPress)

### Trop d'emails reçus

- Réduire la **Limite journalière** dans `Réglages > 404 Alert`
- Augmenter le **Délai par IP** pour espacer les notifications

## Développement & Contribution

### Structure des classes

Chaque classe utilise des méthodes statiques pour une intégration directe aux hooks WordPress.

```php
class S404_Settings {
    public static function init(): void { ... }
    public static function add_menu(): void { ... }
    // ...
}
```

### Filtres & Actions (futurs)

À ajouter si besoin :

```php
apply_filters('s404_before_send_email', $payload);
do_action('s404_email_sent', $to, $subject);
```

## Licence

ISC

## Comparaison avec 404-bavarde

| Aspect            | 404 Alert            | 404-bavarde       |
| ----------------- | -------------------- | ----------------- |
| **Plateforme**    | WordPress plugin     | Vercel serverless |
| **Email**         | `wp_mail()`          | Resend API        |
| **Rate limit**    | Transients WordPress | Redis (Upstash)   |
| **Stats**         | Non (MVP)            | Oui (Redis)       |
| **Configuration** | wp-admin UI          | Env variables     |
| **Dépendances**   | 0 externes           | @upstash/redis    |

---

**Questions ?** Consulter les sources ou ouvrir une issue.
