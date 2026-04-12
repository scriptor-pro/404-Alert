# Installation & Setup — 404 Alert

Instructions d'installation et de configuration du plugin 404 Alert.

## Prérequis

- **WordPress** : 5.9+ (compatible WordPress 6.9)
- **PHP** : 8.1+ (8.2+ recommandé)
- **Dépendances** : Aucune (zéro package externe)
- **Optionnel** : Redis pour optimiser le rate limiting

## Installation standard

### Via WordPress admin

1. Télécharger le ZIP depuis [WordPress.org](https://wordpress.org/plugins/404-alert/) ou le repository GitHub
2. Aller à **Extensions > Ajouter une nouvelle extension**
3. Cliquer **Envoyer une extension**
4. Sélectionner le ZIP
5. Cliquer **Installer maintenant**
6. Cliquer **Activer le plugin**

### Via FTP / SFTP

1. Décompresser le ZIP : `unzip 404-alert.zip`
2. Uploader le dossier `404-alert/` vers `wp-content/plugins/`
3. Aller à **Extensions** et cliquer **Activer**

### Via Git (pour développeurs)

```bash
cd wp-content/plugins
git clone https://github.com/baudouin/404-alert.git
```

Puis **Activer** dans Extensions.

## Configuration de base

### Étape 1 : Ouvrir la page de réglages

Aller à **Réglages > 404 Alert**

### Étape 2 : Configurer les paramètres

| Paramètre | Défaut | Description |
|-----------|--------|-------------|
| **Email destinataire** | Admin email | Où recevoir les notifications 404 |
| **Limite quotidienne** | 500 | Max emails/jour (0 = illimité) |
| **Délai par IP** | 300 sec | Cooldown entre 2 emails pour la même IP |

### Étape 3 : Tester

1. Accéder à une URL inexistante (p. ex. `/test-404`)
2. Vérifier la réception de l'email
3. Tenter un 2e accès immédiat (doit être bloqué par rate limit)

## Configuration avancée

### Redis (optionnel, recommandé)

Pour améliorer les performances du rate limiting 10-15x :

Ajouter à `wp-config.php` :

```php
define( 'ALERT404_REDIS_ENABLED', true );
define( 'ALERT404_REDIS_HOST', 'localhost' );
define( 'ALERT404_REDIS_PORT', 6379 );
```

Voir [REDIS.md](./REDIS.md) pour les détails.

### SMTP (optionnel)

Pour utiliser un serveur SMTP personnalisé (p. ex. Gmail, SendGrid) :

Ajouter à `wp-config.php` :

```php
define( 'ALERT404_SMTP_ENABLED', true );
define( 'ALERT404_SMTP_HOST', 'smtp.gmail.com' );
define( 'ALERT404_SMTP_PORT', 587 );
define( 'ALERT404_SMTP_USERNAME', 'your-email@gmail.com' );
define( 'ALERT404_SMTP_PASSWORD', 'app-password' );
define( 'ALERT404_SMTP_ENCRYPTION', 'tls' );
define( 'ALERT404_SMTP_FROM_EMAIL', 'your-email@gmail.com' );
define( 'ALERT404_SMTP_FROM_NAME', 'Site Admin' );
```

Voir [SMTP.md](./SMTP.md) pour les détails et les configurations cloud.

### Logging (optionnel)

Pour déboguer et monitorer en production :

Ajouter à `wp-config.php` :

```php
define( 'WP_DEBUG', true );
define( 'WP_DEBUG_LOG', true );  // Logs dans wp-content/debug.log
define( 'WP_DEBUG_DISPLAY', false );  // Ne pas afficher les logs en frontend
```

Les logs du plugin incluront :
- Détection 404
- Rate limiting
- Envoi/échec email
- Erreurs Redis/SMTP

## Dépannage

### "Aucun email reçu"

**Vérifier :**

1. Le plugin est-il activé ? Aller à **Extensions** et chercher 404 Alert
2. L'email destination est-il correct ? Aller à **Réglages > 404 Alert**
3. La limite quotidienne est-elle atteinte ? Attendre demain ou modifier dans la page de réglages
4. Le cooldown IP bloque-t-il ? Attendre 5 min ou modifier dans la page de réglages

**Solution :**

Augmenter la limite quotidienne temporairement :
1. Aller à **Réglages > 404 Alert**
2. Mettre **Limite quotidienne** à 10000
3. Sauvegarder
4. Tester avec une nouvelle URL 404

### "Les emails arrivent en spam"

**Cause :** Authentification email faible (pas de SPF/DKIM/DMARC)

**Solution :**

Configurer SMTP avec un service fiable :
- **Gmail** : Voir [SMTP.md](./SMTP.md) section Gmail
- **SendGrid / Brevo** : Service EMAIL dédié recommandé
- **Votre serveur mail** : Ajouter les enregistrements SPF/DKIM/DMARC

### "Redis indisponible"

**Message :** "Redis unavailable - using transients fallback"

**Solution :** Le plugin fonctionne quand même via les transients (plus lent). Voir [REDIS.md](./REDIS.md) dépannage.

### "Erreur de permission en wp-admin"

**Cause :** Vous ne disposez pas de la capacité `manage_options`

**Solution :** Configurer le plugin via wp-config.php au lieu de l'admin

## Désinstallation

1. Aller à **Extensions**
2. Chercher 404 Alert
3. Cliquer **Désactiver**
4. Cliquer **Supprimer**

**Note :** La suppression efface les options WordPress stockées (email, limites, etc).

## Mise à jour

Les mises à jour se font automatiquement si le plugin est publié sur WordPress.org.

### Mise à jour manuelle (Git)

```bash
cd wp-content/plugins/404-alert
git pull origin main
```

Puis rechargez la page WordPress (cache peut être vidé via **Outils > Santé du site**).

## Support & Contribution

- **Issues** : https://github.com/baudouin/404-alert/issues
- **Pull requests** : https://github.com/baudouin/404-alert/pulls
- **License** : GPL 2.0

Voir [CONTRIBUTING.md](./CONTRIBUTING.md) pour contribuer au code.
