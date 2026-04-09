# Requirements

Ce document liste les prerequis pour utiliser et developper le plugin 404 Alert.

## Runtime (production)

- PHP 8 requis (minimum PHP 8.1)
- WordPress 6.x recommande (plugin WordPress actif)
- Extensions PHP: `mbstring`, `json`, `curl`, `openssl`, `mysqli`
- Base de donnees MySQL 5.7+ ou MariaDB 10.4+
- Acces sortant email fonctionnel (`wp_mail()` ou SMTP configure)
- Droits d'ecriture WordPress standards pour stocker options/transients

## Configuration plugin

- Une adresse email administrateur valide
- Optionnel: configuration SMTP (host, port, username, password)
- Optionnel: `WP_DEBUG_LOG=true` pour activer les logs de diagnostic

## Developpement local

- Composer 2
- Dependances installees via `composer install`
- Docker + Docker Compose recommandes pour environnement WordPress local

## Tests et qualite

- PHPUnit (via Composer)
- PHPCS (WordPress Coding Standards)
- PHPStan
- WordPress Test Framework disponible (variable `WP_TESTS_DIR`)
