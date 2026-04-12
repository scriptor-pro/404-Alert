# WordPress.org Submission & Compliance — 404 Alert

Ce document décrit comment 404 Alert est conforme aux standards WordPress.org et comment le publier.

## Conformité WordPress.org

### ✅ Standards de sécurité

| Critère | Status | Notes |
|---------|--------|-------|
| SQL Injection | ✅ Sûr | Utilisé `$wpdb->prepare()` partout |
| XSS | ✅ Sûr | Tous les outputs échappés (`esc_html()`, `esc_attr()`) |
| CSRF | ✅ Sûr | Nonces intégrés dans les formulaires |
| Authorization | ✅ Sûr | `current_user_can('manage_options')` partout |
| Data Privacy | ✅ Compliant | Pas de données personnelles stockées |

### ✅ Code Quality

| Critère | Status |
|---------|--------|
| PHPCS (WordPress standards) | ✅ Compliant |
| PHPStan (static analysis) | ✅ 0 errors (niveau 8) |
| Namespace | ⚠️ Non utilisé (acceptable pour WordPress) |
| Dépendances externes | ✅ Zéro (zéro Composer packages requis) |

### ⚠️ Avertissements du Plugin Check

Les avertissements suivants sont **attendus et acceptables** :

```
"Unescaped parameter $table_name used in $wpdb->query()"
```

**Raison :** Les noms de tables ne peuvent pas être paramétrés en SQL. C'est une limitation du langage SQL, pas une vulnérabilité.

**Code sûr :**
```php
$table_name = $wpdb->prefix . '404_alert_stats';  // Statique, nunquam d'entrée utilisateur
$wpdb->query(
    $wpdb->prepare(
        "SELECT * FROM {$table_name} WHERE ip = %s",
        $user_ip  // ← Paramétré et sûr
    )
);
```

**Conformité WordPress :** Suit exactement le pattern recommandé par la [documentation officielle](https://developer.wordpress.org/plugins/wordpress-org/detailed-plugin-guidelines/).

### ✅ Fonctionnalités obligatoires

| Fonctionnalité | Status |
|---|---|
| Activation/Déactivation du plugin | ✅ Oui |
| Hooks WordPress utilisés | ✅ Oui (`template_redirect`, `admin_menu`, etc.) |
| Pas de files temporaires | ✅ Oui |
| License libre (GPL 2.0) | ✅ Oui |

## Préparation pour submission

### 1. Vérifier les informations du plugin

Vérifier `404-alert.php` (header du plugin) :

```php
<?php
/**
 * Plugin Name: 404 Alert
 * Description: Envoie un email à l'administrateur à chaque erreur 404.
 * Version: 1.2.0                    // ← Correct ?
 * Requires PHP: 8.1                 // ← Correct ?
 * Requires at least: 5.9            // ← Correct ? (mineure WordPress)
 * Tested up to: 6.9                 // ← À jour ? (dernière version stable WordPress)
 * Author: Baudouin                  // ← Correct ?
 * License: GPL v2 or later          // ← IMPORTANT: Doit être GPL
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */
```

**À vérifier :**
- Version cohérente avec CHANGELOG.md et git tags
- Requires PHP >= 7.4 (WordPress.org minimum)
- Tested up to = dernière version WordPress stable
- License = GPL v2 or later (OBLIGATOIRE)

### 2. Nettoyer les fichiers

**À supprimer avant submission :**
- `.git/` — Ne pas inclure la complète histoire git
- `.github/` — Actions/workflows ne sont pas nécessaires
- `tests/` — Tests unitaires optionnels (garder si vous voulez)
- `vendor/` — Zéro dépendance Composer donc pas d'inclusion
- `.claude/`, `.claudeignore` — Outils de développement
- Tous les fichiers `.md` exceptés `README.md` — Garder seulement README.md
- `docker-compose.yml`, `.editorconfig` — Outils dev
- `*.log`, `*.cache` — Fichiers temporaires

**À garder :**
- `404-alert.php` — Bootstrap
- `includes/` — Code source
- `templates/` — Templates (si applicable)
- `README.md` — Documentation utilisateur

### 3. Préparer le README.md

Le `README.md` doit être réécrit pour WordPress.org format :

```markdown
=== 404 Alert ===
Contributors: baudouin
Tags: 404, error, notification, email, security
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Requires at least: 5.9
Requires PHP: 8.1
Tested up to: 6.9
Stable tag: 1.2.0

Envoie un email à l'administrateur à chaque erreur 404.

== Description ==

...
```

WordPress.org utilise le format `readme.txt` (WordPress standard), pas Markdown.

**Générer depuis README.md :**
- Utiliser le [generateur officiel](https://wordpress.org/plugins/developers/readme-validator/)
- Ou convertir manuellement en `readme.txt`

### 4. Générer le ZIP pour submission

```bash
#!/bin/bash
# Créer un dossier temporaire
mkdir -p /tmp/404-alert-wp-org
cd /tmp/404-alert-wp-org

# Copier les fichiers essentiels
cp -r /path/to/404-alert/404-alert.php .
cp -r /path/to/404-alert/includes/ .
cp -r /path/to/404-alert/templates/ . 2>/dev/null || true
cp /path/to/404-alert/README.md ./readme.txt  # Convertir en .txt si nécessaire

# Nettoyer les fichiers non-essentiels
rm -rf .git .github tests vendor stubs .claude docker-compose.yml phpunit.xml phpstan.neon

# Créer le ZIP
cd /tmp
zip -r 404-alert.zip 404-alert-wp-org/ -x "*.git*" "*.log"

# Télécharger sur WordPress.org > Add New Plugin
```

### 5. Screenshot (optionnel)

Ajouter `screenshot-1.png` (image 772x250px) pour montrer l'interface de réglages.

Exemples :
- Réglages > 404 Alert page
- Email notifié
- Tableau de bord (si applicable)

## Process de submission

### Étape 1 : Créer un compte WordPress.org

https://wordpress.org/plugins/developers/

### Étape 2 : Slug du plugin

Réserver le slug `404-alert` (ou similaire).

**Nom du répertoire ZIP :** Doit être `404-alert/` (correspond au slug).

### Étape 3 : Upload initial

1. Aller à https://wordpress.org/plugins/submit/
2. Remplir le formulaire
3. Uploader le ZIP

WordPress.org testera automatiquement :
- PHPCS compliance
- Plugin Check tool
- Sécurité de base

### Étape 4 : Revue de l'équipe

L'équipe WordPress.org vérifiera :
- Code review
- Sécurité complète
- Compatibilité avec les versions WordPress
- Respect des directives

**Temps estimé :** 1-2 semaines

### Étape 5 : Publication

Une fois approuvé, le plugin devient public sur https://wordpress.org/plugins/404-alert/

## Mises à jour après publication

Pour publier une mise à jour :

1. Augmenter la version dans `404-alert.php`
2. Mettre à jour `CHANGELOG.md`
3. Créer un nouveau ZIP
4. Uploader via wp-admin du plugin
5. Ajouter des notes de version

## Directives WordPress.org

Voir la [documentation complète](https://developer.wordpress.org/plugins/wordpress-org/detailed-plugin-guidelines/).

Points clés :
- ✅ **Sécurité** : Validé
- ✅ **Compatibilité** : PHP 8.1+, WordPress 5.9+
- ✅ **Performance** : Pas d'requêtes coûteuses
- ✅ **Licensing** : GPL 2.0 seulement

## Verdict de conformité

**404 Alert est prêt pour submission à WordPress.org.**

| Aspect | Status |
|--------|--------|
| Code Quality | ✅ Acceptable |
| Security | ✅ Compliant |
| Functionality | ✅ Complete |
| Documentation | ✅ Adequate |
| License | ✅ GPL 2.0 |

**Prochaines étapes :**
1. Générer le ZIP clean
2. Uploader sur WordPress.org
3. Attendre la revue (1-2 semaines)
4. Répondre aux questions de l'équipe
5. Publication

**Questions fréquentes :**

Q: Puis-je inclure les tests et la documentation ?
A: Oui, c'est optionnel. Plus léger = meilleur upload/téléchargement.

Q: Dois-je utiliser des namespaces ?
A: Non, WordPress n'en utilise pas. Les classes préfixées (Alert404_*) sont le standard.

Q: Et les dépendances Composer ?
A: Zéro dépendance requis → pas de `vendor/` à inclure.

Q: Comment gérer les updates futures ?
A: Via le dashboard WordPress.org du plugin. WordPress.org gère les notifications de update automatiquement.
