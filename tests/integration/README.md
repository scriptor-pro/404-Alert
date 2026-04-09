# Tests d'Intégration E2E - 404 Alert

Ce répertoire contient les tests d'intégration end-to-end (E2E) pour le plugin 404 Alert. Ces tests valident les workflows complets du plugin, de la détection d'une erreur 404 jusqu'à l'affichage des statistiques dans le tableau de bord admin.

## Structure des Tests E2E

Les tests E2E testent les **workflows complets**, pas les fonctions individuelles. Chaque test simule un scénario réel d'utilisation du plugin.

### Fichiers de Test

- **`Test_Alert404_E2E.php`** : 12 scénarios de test couvrant tous les workflows critiques

## Scénarios de Test

### 1. 404 déclenche Email + Storage
**Test:** `test_e2e_404_triggers_email_and_storage`

Vérifie que lorsqu'une erreur 404 est détectée :
- Les données sont collectées correctement
- Un email est envoyé à l'administrateur
- Les statistiques sont stockées en base

```
404 → Détection → Email + Storage
```

### 2. Rate Limiting bloque les doublons
**Test:** `test_e2e_rate_limiting_blocks_duplicate_ips`

Vérifie que le rate limiting fonctionne :
- Un IP qui fait un 404 peut être limité
- Les 404 suivants du même IP ne déclenchent pas de nouvel email
- Les données sont toujours collectées en stats

```
IP1/404#1 → Email + Storage
IP1/404#2 → Bloqué (pas d'email)
```

### 3. Redis stocke les limites de taux
**Test:** `test_e2e_redis_stores_rate_limit_when_available`

Vérifie que quand Redis est disponible :
- Les cooldowns sont stockés dans Redis
- L'atomicité Redis prévient les race conditions

```
404 → Redis.SET(ip_key, timestamp, TTL)
```

### 4. Fallback vers Transients
**Test:** `test_e2e_transient_fallback_when_redis_unavailable`

Vérifie que quand Redis n'est pas disponible :
- Le plugin utilise les WordPress transients
- Le rate limiting fonctionne malgré tout
- Les données sont toujours collectées

```
Redis unavailable → Transients fallback → Data stored
```

### 5. SMTP envoie l'email
**Test:** `test_e2e_smtp_configuration_affects_email_sending`

Vérifie que la configuration SMTP est utilisée :
- Les paramètres SMTP sont lus depuis les options
- Les emails sont envoyés via SMTP si configuré
- Les paramètres (port, encryption, TLS) sont appliqués

```
SMTP configured → wp_mail() → SMTP Handler → Gmail/SendGrid/etc
```

### 6. Fallback wp_mail si SMTP échoue
**Test:** `test_e2e_wp_mail_fallback_when_smtp_fails`

Vérifie la résilience en cas d'erreur SMTP :
- Si SMTP échoue, `wp_mail()` est utilisée en fallback
- Les 404 sont toujours enregistrés même si l'email échoue
- Les stats sont collectées

```
SMTP fails → wp_mail() fallback → Data stored
```

### 7. Persistence des Settings
**Test:** `test_e2e_settings_persistence_across_requests`

Vérifie que les paramètres sont sauvegardés et chargés :
- Les settings sont stockées dans WordPress options
- Les settings sont appliquées à chaque requête
- Les changements persistent après rechargement

```
Settings save → 404 triggered → Settings loaded and applied
```

### 8. Page Admin affiche les Stats
**Test:** `test_e2e_admin_page_displays_statistics`

Vérifie que le tableau de bord fonctionne :
- Un admin peut accéder à la page `/wp-admin/?page=404-alert-stats`
- Les statistiques sont affichées
- Les données collectées s'affichent correctement

```
404s triggered → Dashboard.render() → Stats visible
```

### 9. Workflow Complet : 404 → Dashboard
**Test:** `test_e2e_complete_workflow_404_to_dashboard`

Test intégration complète en cascade :
1. Un 404 est déclenché
2. Rate limiting est appliqué
3. Email est envoyé
4. Données sont stockées
5. Dashboard les affiche

```
404 → RateLimit.check() → Mailer.send() → Storage.save() → Dashboard.render()
```

### 10. IPs traitées indépendamment
**Test:** `test_e2e_multiple_ips_are_rate_limited_independently`

Vérifie que chaque IP a son propre quota :
- IP1/404 → Email #1
- IP1/404 → Bloqué (rate limited)
- IP2/404 → Email #2 (quota indépendant)
- Chaque IP a ses propres stats

```
IP1: limit=1
IP2: limit=1
→ Deux emails distincts, deux rate limits indépendants
```

### 11. Daily Limit global
**Test:** `test_e2e_daily_limit_prevents_excess_emails`

Vérifie que le limite quotidienne totale fonctionne :
- Configurer `daily_limit` à 3
- Déclencher 4 IPs différentes
- Max 3 emails sont envoyés au total
- Le 4ème est bloqué par la limite quotidienne

```
IPs: 1,2,3,4 | daily_limit: 3 → Emails: 3 max
```

### 12. Sanitization des données sensibles
**Test:** `test_e2e_sensitive_data_is_sanitized`

Vérifie la sécurité contre l'injection :
- Les User-Agents longs sont tronqués
- Les scripts injected ne sont pas sauvegardés
- Les données dangereuses ne causent pas de faille XSS
- Les URL avec paramètres dangereux sont échappées

```
Input: <script>alert('xss')</script> + long text
→ Storage: sanitized + truncated
→ Dashboard: safe to display
```

## Exécution des Tests

### Tous les tests E2E
```bash
cd /home/Baudouin/Documents/Projets/404-alert
php vendor/bin/phpunit tests/integration/Test_Alert404_E2E.php
```

### Un test spécifique
```bash
php vendor/bin/phpunit tests/integration/Test_Alert404_E2E.php::Test_Alert404_E2E::test_e2e_404_triggers_email_and_storage
```

### Avec verbose
```bash
php vendor/bin/phpunit tests/integration/Test_Alert404_E2E.php -v
```

### Avec couverture de code
```bash
php vendor/bin/phpunit tests/integration/Test_Alert404_E2E.php --coverage-html coverage/
```

## Configuration Requise pour les Tests

### Variables d'Environnement (optionnel)
```bash
export WP_TESTS_DIR="/path/to/wordpress-test-lib"
export WORDPRESS_TESTS_DIR="/path/to/wordpress-test-lib"
export WORDPRESS_DB_HOST="127.0.0.1"
export WORDPRESS_DB_NAME="wordpress_test"
export WORDPRESS_DB_USER="wordpress"
export WORDPRESS_DB_PASSWORD="wordpress123"
```

### Dépendances
- WordPress Test Framework (automatiquement chargé par `tests/bootstrap.php`)
- PHPUnit 9.5+ (installé via Composer)
- Redis (optionnel, les tests fonctionnent sans)

### Bases de Données
Les tests utilisent une base de données temporaire SQLite par défaut. Si vous avez besoin de MySQL/MariaDB, configurez via les variables d'environnement.

## Dépannage

### Redis n'est pas disponible
Certains tests seront ignorés avec `markTestSkipped()`. C'est normal.

### Erreur : "Impossible de trouver WordPress Test Framework"
Installez le WordPress Test Framework :
```bash
# Voir le fichier tests/bootstrap.php pour les emplacements supportés
# Ou définir WORDPRESS_TESTS_DIR
```

### Erreur de connexion à la base de données
Vérifiez que les variables d'environnement `WORDPRESS_DB_*` sont correctement définies.

### Les tests prennent du temps
C'est normal pour les E2E tests. Chaque test initialise WordPress, crée des utilisateurs, déclenche des hooks, etc. Une suite E2E complète peut prendre 30-60 secondes.

## Couverture de Code

Les 12 tests E2E couvrent les intégrations entre :
- `Alert404_Detector` : Détection des 404
- `Alert404_RateLimiter` : Limitation du taux
- `Alert404_Redis_Handler` : Stockage Redis
- `Alert404_Mailer` : Envoi d'emails
- `Alert404_SMTP_Handler` : Configuration SMTP
- `Alert404_Storage` : Stockage des stats
- `Alert404_Dashboard` : Affichage admin
- `Alert404_Settings` : Paramètres

**Couverture estimée:** 75-85% des workflows critiques

## Ajout de Nouveaux Tests E2E

Pour ajouter un nouveau test E2E :

1. Créer une méthode `test_e2e_*` dans `Test_Alert404_E2E`
2. Utiliser les helpers :
   - `$this->set_404()` : Simuler une erreur 404
   - `$this->setup_plugin_options()` : Configurer les options
   - `add_filter()` : Capturer les appels (comme `wp_mail`)
3. Vérifier les résultats :
   - `Alert404_Storage::get_stats()` : Vérifier le stockage
   - `ob_start()` + `Alert404_Dashboard::render_page()` : Tester l'admin
   - Email captured via filter

Exemple :
```php
public function test_e2e_my_workflow() {
    $this->set_404('/my-test');
    
    // Capture
    $captured = [];
    add_filter('wp_mail', function($args) use (&$captured) {
        $captured[] = $args;
        return true;
    });
    
    // Execute
    Alert404_Detector::on_template_redirect();
    
    // Assert
    $this->assertCount(1, $captured);
}
```

## Relation avec les Tests Unitaires

| Type de Test | Dossier | Quoi | Quand |
|---|---|---|---|
| **Unitaires** | `tests/unit/` | Fonction unique | `Développement` |
| **Intégration E2E** | `tests/integration/` | Workflow complet | `Avant production` |

Les tests unitaires testent les classes isolées. Les tests E2E testent leur interaction réelle.
