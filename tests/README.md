# Tests 404 Alert

Suite de tests PHPUnit pour le plugin 404 Alert.

## Structure

```
tests/
├── bootstrap.php              # Bootstrap PHPUnit et configuration WordPress
├── README.md                  # Ce fichier
├── unit/                      # Tests unitaires
│   ├── Test_Alert404_RateLimiter.php # Tests du rate limiting (11 tests)
│   ├── Test_Alert404_Detector.php    # Tests de la détection 404 (12 tests)
│   ├── Test_Alert404_Mailer.php      # Tests de l'envoi d'email (16 tests)
│   └── Test_Alert404_Logger.php      # Tests du logging (12 tests)
└── fixtures/                  # Données de test (non utilisées actuellement)
```

## Exécuter les tests

### Avec Composer

```bash
# Exécuter tous les tests
composer test

# Exécuter un test spécifique
composer test tests/unit/test-rate-limiter.php

# Exécuter avec coverage
composer test -- --coverage-html coverage
```

### Directement avec PHPUnit

```bash
# Tous les tests
vendor/bin/phpunit

# Tests d'une classe spécifique
vendor/bin/phpunit tests/unit/Test_Alert404_Detector.php

# Avec verbose et coverage
vendor/bin/phpunit --verbose --coverage-html coverage

# Arrêter au premier échec
vendor/bin/phpunit --stop-on-failure
```

## Configuration requise

### PHP

- **Version minimale** : PHP 8.1
- **Extensions** : curl, gd, mbstring, mysqli, xmlrpc, zip

### Dépendances

Les dépendances de test sont définies dans `composer.json` :

```bash
composer install
```

Cela installe :

- `phpunit/phpunit` : Framework de test
- `php-stubs/wordpress-stubs` : Stubs pour l'autocomplétion IDE
- `phpstan/phpstan` : Analyse statique (niveau 8)
- `squizlabs/php_codesniffer` : Vérification du style de code

### WordPress Test Framework

Le bootstrap charge automatiquement le WordPress Test Framework. Vous pouvez spécifier son chemin via la variable d'environnement `WORDPRESS_TESTS_DIR` :

```bash
export WORDPRESS_TESTS_DIR=/path/to/wordpress-test-lib
composer test
```

### Base de donnees de test (Docker recommande)

Le repo inclut un MySQL dedie pour les tests dans `tests/docker-compose.yml`.

```bash
# Demarrer MySQL de test
docker compose -f tests/docker-compose.yml up -d

# Verifier l'etat
docker compose -f tests/docker-compose.yml ps

# Lancer les tests
composer test
```

Configuration par defaut utilisee par `phpunit.xml` et `tests/wp-tests-config.php` :

- Host: `127.0.0.1:3307`
- Database: `wordpress_test`
- User: `wordpress`
- Password: `wordpress123`

Vous pouvez surcharger ces valeurs avec les variables d'environnement `WORDPRESS_DB_HOST`, `WORDPRESS_DB_NAME`, `WORDPRESS_DB_USER`, `WORDPRESS_DB_PASSWORD`.

## Tests disponibles

### 1. Test_Alert404_RateLimiter (Test_Alert404_RateLimiter.php)

Vérifie le fonctionnement du rate limiting par IP et limite quotidienne.

| Test                                       | Description                            |
| ------------------------------------------ | -------------------------------------- |
| `test_first_request_is_allowed`            | Première requête d'une IP = autorisée  |
| `test_second_immediate_request_is_blocked` | Deuxième requête immédiate = bloquée   |
| `test_different_ips_are_independent`       | Les IPs différentes ne s'affectent pas |
| `test_daily_limit_blocks_after_threshold`  | Limite quotidienne fonctionnelle       |
| `test_both_limits_work_together`           | Cooldown + limite quotidienne ensemble |
| `test_default_options_are_applied`         | Valeurs par défaut correctes           |
| `test_ipv6_is_supported`                   | Support IPv6                           |
| `test_localhost_is_handled`                | IP localhost (127.0.0.1)               |
| `test_cooldown_can_be_configured`          | Modification du cooldown               |
| `test_daily_limit_can_be_configured`       | Modification de la limite quotidienne  |
| `test_transient_expiration`                | Expiration des transients              |

### 2. Test_Alert404_Detector (Test_Alert404_Detector.php)

Vérifie la détection 404 et l'extraction d'IP.

| Test                                      | Description                       |
| ----------------------------------------- | --------------------------------- |
| `test_init_registers_hook`                | Hook template_redirect enregistré |
| `test_non_404_does_not_trigger`           | Non-404 n'envoie pas d'email      |
| `test_ip_extraction_from_remote_addr`     | Extraction de REMOTE_ADDR         |
| `test_ip_extraction_from_x_forwarded_for` | Extraction de X-FORWARDED-FOR     |
| `test_ip_extraction_with_whitespace`      | Trimming des espaces              |
| `test_invalid_ips_are_ignored`            | IPs invalides skippées            |
| `test_payload_collection`                 | Données collectées correctement   |
| `test_payload_is_size_limited`            | Limites de taille respectées      |
| `test_null_values_in_payload`             | Gestion des NULL                  |
| `test_missing_ip_returns_empty_string`    | IP manquante = chaîne vide        |
| `test_ip_order_is_preserved`              | Ordre des IPs respecté            |
| `test_ipv6_is_extracted`                  | Support IPv6                      |

### 3. Test_Alert404_Mailer (Test_Alert404_Mailer.php)

Vérifie l'envoi d'email et les hooks d'extensibilité.

| Test                                               | Description                                    |
| -------------------------------------------------- | ---------------------------------------------- |
| `test_email_uses_configured_recipient`             | Destinataire configuré utilisé                 |
| `test_email_uses_default_admin_email`              | Email admin par défaut                         |
| `test_email_subject_format`                        | Format du sujet correct                        |
| `test_email_content_is_html`                       | Contenu en HTML                                |
| `test_email_content_contains_data`                 | Données présentes dans l'email                 |
| `test_email_headers_content_type`                  | Content-Type correct                           |
| `test_filter_email_to_is_applied`                  | Filtre `404_alert_email_to` fonctionne         |
| `test_filter_email_subject_is_applied`             | Filtre `404_alert_email_subject` fonctionne    |
| `test_filter_email_body_is_applied`                | Filtre `404_alert_email_body` fonctionne       |
| `test_action_email_sent_is_triggered`              | Action `404_alert_email_sent` déclenchée       |
| `test_action_email_failed_is_triggered_on_failure` | Action `404_alert_email_failed` en cas d'échec |
| `test_data_is_escaped`                             | Échappement XSS                                |
| `test_payload_json_is_included`                    | JSON du payload inclus                         |

### 4. Test_Alert404_Logger (Test_Alert404_Logger.php)

Vérifie le logging de tous les événements.

| Test                                  | Description                     |
| ------------------------------------- | ------------------------------- |
| `test_log_invalid_ip`                 | Logging des IPs invalides       |
| `test_log_rate_limit_ip`              | Logging du rate limit par IP    |
| `test_log_rate_limit_daily`           | Logging du rate limit quotidien |
| `test_log_email_sent`                 | Logging des emails envoyés      |
| `test_log_email_failed`               | Logging des emails échoués      |
| `test_log_404_detected`               | Logging des 404 détectées       |
| `test_log_includes_timestamp`         | Timestamp inclus                |
| `test_log_format_is_json_for_context` | Format JSON pour le contexte    |
| `test_sensitive_data_is_logged`       | Données sensibles loggées       |
| `test_multiple_logs_are_recorded`     | Plusieurs logs enregistrés      |
| `test_log_includes_plugin_prefix`     | Préfixe [404-Alert] présent     |
| `test_null_values_are_handled`        | Gestion des valeurs nulles      |

## Code Coverage

Générer un rapport de coverage :

```bash
# HTML
vendor/bin/phpunit --coverage-html coverage

# Clover (pour Codecov, CI/CD)
vendor/bin/phpunit --coverage-clover coverage/clover.xml

# Text
vendor/bin/phpunit --coverage-text
```

**Objectif minimum** : >70% pour les nouvelles fonctionnalités

Visualiser le coverage :

```bash
open coverage/index.html  # macOS
xdg-open coverage/index.html  # Linux
start coverage/index.html  # Windows
```

## CI/CD Integration

Les tests s'exécutent automatiquement sur GitHub Actions :

- **Sur chaque push** vers `main` ou `develop`
- **Sur chaque pull request** vers `main` ou `develop`
- **PHP versions** : 8.1, 8.2, 8.3
- **WordPress versions** : latest, 6.0

Voir `.github/workflows/tests.yml` pour la configuration.

## Debugging

### Mode verbose

```bash
vendor/bin/phpunit --verbose
```

### Arrêter au premier échec

```bash
vendor/bin/phpunit --stop-on-failure
```

### Exécuter un seul test

```bash
vendor/bin/phpunit tests/unit/Test_Alert404_RateLimiter.php --filter test_first_request_is_allowed
```

### Avec xdebug (si configuré)

```bash
export XDEBUG_CONFIG="idekey=phpstorm"
vendor/bin/phpunit
```

## Ajouter de nouveaux tests

1. Créer un fichier `tests/unit/test-*.php`
2. Étendre `Alert404_UnitTestCase`
3. Utiliser les méthodes d'helpers :
   ```php
   $this->setup_plugin_options(['email' => 'test@example.com']);
   $this->set_404('/inexistent');
   $this->clear_all_transients();
   ```

Exemple :

```php
<?php
class Test_MyFeature extends Alert404_UnitTestCase {

    public function test_something() {
        $this->setup_plugin_options();

        // Test...
        $this->assertTrue( ... );
    }
}
```

## Ressources

- [PHPUnit 9.5 Documentation](https://phpunit.de/documentation.html)
- [WordPress Testing Documentation](https://developer.wordpress.org/plugins/testing/)
- [GitHub Actions Documentation](https://docs.github.com/en/actions)

## Questions ?

Consultez `CONTRIBUTING.md` pour les guidelines de contribution.
