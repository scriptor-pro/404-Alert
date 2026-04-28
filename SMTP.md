# SMTP Configuration — 404 Alert

Ce guide couvre la configuration SMTP pour envoyer les emails 404 via un serveur SMTP personnalisé.

## Vue d'ensemble

Par défaut, 404 Alert utilise `wp_mail()` de WordPress. Optionnellement, vous pouvez configurer un serveur SMTP pour :
- Augmenter la délivrabilité (éviter la filtrante spam)
- Utiliser des services comme Gmail, SendGrid, AWS SES, etc.
- Chiffrer les credentials

## Configuration

### Via wp-config.php

Ajouter avant `/* That's all, stop editing! */` :

```php
// SMTP Configuration
define( 'ALERT404_SMTP_HOST', 'smtp.gmail.com' );           // Serveur SMTP
define( 'ALERT404_SMTP_PORT', 587 );                        // Port (587 TLS, 465 SSL, 25 plain)
define( 'ALERT404_SMTP_USERNAME', 'your-email@gmail.com' ); // Utilisateur
define( 'ALERT404_SMTP_PASSWORD', 'your-password' );        // Password (sera chiffré)
define( 'ALERT404_SMTP_ENCRYPTION', 'tls' );                // 'tls', 'ssl', or 'none'
define( 'ALERT404_SMTP_FROM_EMAIL', 'your-email@gmail.com' ); // Adresse d'envoi
define( 'ALERT404_SMTP_FROM_NAME', 'Site Admin' );          // Nom d'envoi
define( 'ALERT404_SMTP_ENABLED', true );                    // Activer SMTP
```

### Serveurs SMTP populaires

#### Gmail

```php
define( 'ALERT404_SMTP_HOST', 'smtp.gmail.com' );
define( 'ALERT404_SMTP_PORT', 587 );
define( 'ALERT404_SMTP_USERNAME', 'your-email@gmail.com' );
define( 'ALERT404_SMTP_PASSWORD', 'your-app-password' ); // 16 caractères, généré via Google Account
define( 'ALERT404_SMTP_ENCRYPTION', 'tls' );
define( 'ALERT404_SMTP_FROM_EMAIL', 'your-email@gmail.com' );
define( 'ALERT404_SMTP_ENABLED', true );
```

**Notes :**
- Activer [2FA sur Gmail](https://myaccount.google.com/security)
- Générer un [App Password](https://myaccount.google.com/apppasswords) (16 chars)
- Ne **pas** utiliser le password Gmail principal

#### Yahoo Mail

```php
define( 'ALERT404_SMTP_HOST', 'smtp.mail.yahoo.com' );
define( 'ALERT404_SMTP_PORT', 587 );
define( 'ALERT404_SMTP_USERNAME', 'your-email@yahoo.com' );
define( 'ALERT404_SMTP_PASSWORD', 'your-app-password' ); // App password from Yahoo
define( 'ALERT404_SMTP_ENCRYPTION', 'tls' );
define( 'ALERT404_SMTP_FROM_EMAIL', 'your-email@yahoo.com' );
define( 'ALERT404_SMTP_ENABLED', true );
```

**Notes :**
- Générer un [App Password](https://login.yahoo.com/account/security) depuis les paramètres de sécurité Yahoo
- Limite : 450 emails/jour

#### ProtonMail

```php
define( 'ALERT404_SMTP_HOST', 'smtp.protonmail.com' );
define( 'ALERT404_SMTP_PORT', 1025 );
define( 'ALERT404_SMTP_USERNAME', 'your-email@protonmail.com' );
define( 'ALERT404_SMTP_PASSWORD', 'your-app-password' ); // App password from ProtonMail
define( 'ALERT404_SMTP_ENCRYPTION', 'tls' );
define( 'ALERT404_SMTP_FROM_EMAIL', 'your-email@protonmail.com' );
define( 'ALERT404_SMTP_ENABLED', true );
```

**Notes :**
- Générer un [App Password](https://account.protonmail.com/mail/settings/passwords) depuis les paramètres de sécurité ProtonMail
- Utilisez votre adresse email ProtonMail comme identifiant
- Limite : illimité pour les comptes payants, 150/jour pour les freemium

#### Outlook / Office 365

```php
define( 'ALERT404_SMTP_HOST', 'smtp.office365.com' );
define( 'ALERT404_SMTP_PORT', 587 );
define( 'ALERT404_SMTP_USERNAME', 'your-email@company.com' );
define( 'ALERT404_SMTP_PASSWORD', 'your-password' );
define( 'ALERT404_SMTP_ENCRYPTION', 'tls' );
define( 'ALERT404_SMTP_FROM_EMAIL', 'your-email@company.com' );
define( 'ALERT404_SMTP_ENABLED', true );
```

#### SendGrid

```php
define( 'ALERT404_SMTP_HOST', 'smtp.sendgrid.net' );
define( 'ALERT404_SMTP_PORT', 587 );
define( 'ALERT404_SMTP_USERNAME', 'apikey' );  // Littéral : "apikey"
define( 'ALERT404_SMTP_PASSWORD', 'SG.xxx...' ); // Votre API key
define( 'ALERT404_SMTP_ENCRYPTION', 'tls' );
define( 'ALERT404_SMTP_FROM_EMAIL', 'noreply@mysite.com' );
define( 'ALERT404_SMTP_ENABLED', true );
```

#### AWS SES

```php
define( 'ALERT404_SMTP_HOST', 'email-smtp.us-east-1.amazonaws.com' );
define( 'ALERT404_SMTP_PORT', 587 );
define( 'ALERT404_SMTP_USERNAME', 'AKIAIOSFODNN7EXAMPLE' );  // AWS Access Key ID
define( 'ALERT404_SMTP_PASSWORD', 'xxx' );                   // AWS Secret
define( 'ALERT404_SMTP_ENCRYPTION', 'tls' );
define( 'ALERT404_SMTP_FROM_EMAIL', 'noreply@mysite.com' );  // Doit être vérifié dans SES
define( 'ALERT404_SMTP_ENABLED', true );
```

#### Brevo (anciennement Sendinblue)

```php
define( 'ALERT404_SMTP_HOST', 'smtp-relay.brevo.com' );
define( 'ALERT404_SMTP_PORT', 587 );
define( 'ALERT404_SMTP_USERNAME', 'your-email@example.com' );
define( 'ALERT404_SMTP_PASSWORD', 'xxxxx' );  // SMTP key from Brevo
define( 'ALERT404_SMTP_ENCRYPTION', 'tls' );
define( 'ALERT404_SMTP_FROM_EMAIL', 'your-email@example.com' );
define( 'ALERT404_SMTP_ENABLED', true );
```

#### Mailtrap (Test/Dev)

```php
define( 'ALERT404_SMTP_HOST', 'smtp.mailtrap.io' );
define( 'ALERT404_SMTP_PORT', 2525 );
define( 'ALERT404_SMTP_USERNAME', 'xxxxx' );  // From Mailtrap credentials
define( 'ALERT404_SMTP_PASSWORD', 'xxxxx' );  // From Mailtrap credentials
define( 'ALERT404_SMTP_ENCRYPTION', 'tls' );
define( 'ALERT404_SMTP_FROM_EMAIL', 'test@example.com' );
define( 'ALERT404_SMTP_ENABLED', true );
```

## Vérification

### Test de connexion

Dans wp-admin :

1. Aller à `Réglages > 404 Alert`
2. Si configuration valide : **"SMTP connected"**
3. Sinon : **"SMTP connection failed"** avec détails erreur

### Tests manuels

Créer une page 404 test :

```bash
curl -i http://yoursite.com/inexistent-page
```

Vérifier que l'email est reçu en 30 secondes.

### Via logs

```bash
tail -f wp-content/debug.log | grep -E "(SMTP|404-Alert)"
```

Expected:
```
[404-Alert] smtp_connected: SMTP working
[404-Alert] email_sent: admin@example.com
```

## Dépannage

### "SMTP connection failed"

**Vérifier :**

1. Host/Port correct ?
   ```bash
   telnet smtp.gmail.com 587
   # Connected (ok) ou Connection refused (problème)
   ```

2. Username/Password correct ?
   - Gmail : Utiliser [App Password](https://myaccount.google.com/apppasswords), pas le password principal
   - SendGrid : Username = `apikey` (littéral)
   - AWS SES : Vérifier les credentials AWS

3. Encryption correct ?
   - Port 587 → TLS
   - Port 465 → SSL
   - Port 25 → none

**Solution :**
```php
// Utiliser les logs pour déboguer
define( 'WP_DEBUG', true );
define( 'WP_DEBUG_LOG', true );
```

### "AUTH failed"

**Cause commune :**
- Gmail : Utiliser password principal au lieu d'App Password
- SendGrid : Username ≠ `apikey`
- Port incorrect (465 vs 587)

**Solution :**
- Régénérer credentials depuis le service
- Vérifier la case à cocher "Enable SMTP auth"

### "Emails sent but not received"

**Vérifier :**

1. **SPF/DKIM/DMARC** :
   - Ajouter les enregistrements DNS du service SMTP
   - Gmail : [Setup SPF](https://support.google.com/a/answer/33786)
   - SendGrid : [DKIM setup](https://docs.sendgrid.com/ui/account-and-settings/dkim)

2. **From address** :
   - Doit être vérifiée dans le service
   - Gmail : Doit être votre email Gmail
   - AWS SES : Doit être dans liste vérifiée

3. **Spam folder** :
   - Vérifier si l'email est en spam
   - Ajouter l'adresse à la liste blanche

### "Timeout"

**Cause :**
- Firewall bloque le port SMTP
- Service SMTP down

**Solution :**
```bash
# Tester la connectivité
nc -zv smtp.gmail.com 587
# Connection successful ou refused

# Vérifier firewall
sudo iptables -L | grep 587
```

### Performance lente

**Vérifier la latence du serveur SMTP :**

```php
// wp-config.php
define( 'ALERT404_SMTP_TIMEOUT', 5 ); // Augmenter si nécessaire (défaut 2)
```

## Fallback

Si SMTP échoue, le plugin utilise automatiquement `wp_mail()` de WordPress.

Logs :
```
[404-Alert] smtp_connection_failed: [error]
[404-Alert] using_wordpress_mail: fallback
```

## Sécurité

### Chiffrement des passwords

Les passwords SMTP sont chiffrés AES-256-CBC au stockage (option WordPress).

**Ne jamais :**
- Committer `wp-config.php` dans Git (ajouter à `.gitignore`)
- Partager les credentials
- Utiliser des passwords en clair en production

### Best Practices

1. Utiliser des App Passwords (Gmail, Outlook) au lieu de passwords principaux
2. Utiliser des API keys dédiquées (SendGrid, Brevo)
3. Limiter les permissions (SES : envoyer uniquement)
4. Monitorer les logs pour les tentatives failed

## Production Checklist

- [ ] SMTP configuré et testé
- [ ] Credentials valides et chiffrés
- [ ] SPF/DKIM/DMARC enregistrements ajoutés
- [ ] From address vérifiée dans le service
- [ ] Test d'envoi passé
- [ ] Logs du plugin monitrés
- [ ] Fallback vers `wp_mail()` testé
- [ ] Timeout configuré approprié

## Support

- **Gmail App Passwords** : https://myaccount.google.com/apppasswords
- **SendGrid SMTP** : https://docs.sendgrid.com/for-developers/sending-email/integrating-with-the-smtp-api
- **AWS SES** : https://docs.aws.amazon.com/ses/latest/dg/send-email-smtp.html
- **Mailtrap** : https://help.mailtrap.io/
