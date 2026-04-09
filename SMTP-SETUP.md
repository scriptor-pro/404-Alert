# 📧 Configuration SMTP - 404 Alert

Guide complet pour configurer SMTP dans le plugin 404 Alert pour l'envoi d'emails 404.

---

## 📋 Table des matières

1. [Vue d'ensemble](#vue-densemble)
2. [Configuration rapide (5 minutes)](#configuration-rapide-5-minutes)
3. [Gmail (Recommandé)](#gmail-recommandé)
4. [Microsoft 365 / Outlook](#microsoft-365--outlook)
5. [SendGrid](#sendgrid)
6. [Autres services](#autres-services)
7. [Sécurité des credentials](#sécurité-des-credentials)
8. [Test de connexion](#test-de-connexion)
9. [Troubleshooting](#troubleshooting)
10. [Fallback à wp_mail()](#fallback-à-wp_mail)

---

## Vue d'ensemble

Le plugin 404 Alert utilise SMTP pour envoyer des notifications d'erreurs 404. Vous avez deux options :

### Option 1 : SMTP via Settings Page (UI WordPress)
- **Avantage** : Simple, configuration graphique, test intégré
- **Qui l'utilise** : La plupart des utilisateurs
- **Temps** : 5 minutes

### Option 2 : Configuration via wp-config.php
- **Avantage** : Plus sécurisé, credentials en constantes
- **Qui l'utilise** : Administrateurs système, déploiement automatisé
- **Temps** : 2 minutes

---

## Configuration rapide (5 minutes)

### Étape 1: Accéder à la Settings Page

1. Allez à **Paramètres → Surveillance 404**
2. Descendez à la section **Configuration SMTP**

### Étape 2: Remplir les champs

```
Serveur SMTP:     smtp.gmail.com      (dépend du service)
Port SMTP:        587                 (ou 465 pour SSL)
Nom d'utilisateur: your-email@gmail.com
Mot de passe:     ****PASSWORD****    (jamais réaffiché)
Chiffrement:      TLS                 (recommandé)
Email d'envoi:    your-email@gmail.com
Nom d'envoi:      404 Alert
```

### Étape 3: Tester

1. Cliquez sur **Tester la connexion**
2. Vous verrez un message de succès ✓ ou d'erreur ✗

### Étape 4: Enregistrer

Cliquez sur **Enregistrer les paramètres**

✅ **SMTP est configuré !**

---

## Gmail (Recommandé)

### Configuration SMTP

```
Serveur SMTP:  smtp.gmail.com
Port:          587
Chiffrement:   TLS
Nom d'utilisateur: your-email@gmail.com
Mot de passe:  Mot de passe d'application (voir ci-dessous)
```

### Obtenir le mot de passe d'application

⚠️ **N'utilisez PAS votre mot de passe Gmail direct !**

1. Accédez à [Google Account Security](https://myaccount.google.com/security)
2. Activez l'authentification 2FA si ce n'est pas fait
3. Allez à [App Passwords](https://myaccount.google.com/apppasswords)
4. Sélectionnez:
   - Appareil: **Mail**
   - OS: **Windows / Linux / Mac**
5. Générez le mot de passe (16 caractères)
6. Copiez-collez ce mot de passe dans le champ **Mot de passe** du plugin

### Limites Gmail

- **Limite/jour**: 500 emails
- **Limite/heure**: ~80 emails
- **Délai**: ~0.5s par email

### Statut des erreurs courantes

| Erreur | Cause | Solution |
|--------|-------|----------|
| `535 Login Attempt blocked` | Authentification 2FA manquante | Activer 2FA |
| `535 Invalid credentials` | Mauvais mot de passe d'application | Vérifier le mot de passe |
| `530 Must issue a STARTTLS command first` | Chiffrement mal configuré | Utiliser TLS (port 587) |

---

## Microsoft 365 / Outlook

### Configuration SMTP

```
Serveur SMTP:     smtp.office365.com
Port:             587
Chiffrement:      TLS
Nom d'utilisateur: your-email@company.com
Mot de passe:     Votre mot de passe Microsoft 365
```

### Pré-requis

- Compte Microsoft 365 actif
- Authentification SMTP activée (par défaut)
- Si MFA: [Générer un mot de passe d'application](https://support.microsoft.com/en-us/account-billing/using-app-passwords-with-apps-that-don-t-support-multi-factor-authentication-5896ed9b-4263-e681-128a-a6f2979a7944)

### Alternative: Graph API

⚠️ Plus complexe, requiert configuration OAuth. Pas encore supporté par le plugin.

### Limites Microsoft 365

- **Limite/jour**: Dépend du plan (100-10k emails)
- **Délai**: ~0.2s par email
- **Ports acceptés**: 587 (TLS), 25, 465 (SSL)

---

## SendGrid

### Configuration SMTP

```
Serveur SMTP:     smtp.sendgrid.net
Port:             587 ou 465
Chiffrement:      TLS ou SSL
Nom d'utilisateur: apikey
Mot de passe:     SG.xxxxxxxxxxxx... (votre API Key)
Email d'envoi:    sender@verified-domain.com
```

### Obtenir la clé API SendGrid

1. Créez un compte gratuit à [SendGrid](https://sendgrid.com)
2. Allez à **Settings → API Keys**
3. Créez une nouvelle clé avec permission **Mail Send**
4. Copiez la clé dans le champ **Mot de passe** du plugin

### Important: Domaine vérifié

- L'email d'envoi doit provenir d'un **domaine vérifié**
- Allez à **Settings → Sender Authentication**
- Vérifiez le SPF et DKIM

### Limites SendGrid

- **Gratuit**: 100 emails/jour, illimité si utilisateur payant
- **Payant**: 100k emails/mois à partir de $9.95
- **Délai**: ~0.1s par email

---

## Autres services

### Brevo (anciennement Sendinblue)

```
Serveur SMTP:     smtp-relay.brevo.com
Port:             587
Chiffrement:      TLS
Nom d'utilisateur: your-email@example.com
Mot de passe:     Clé SMTP Brevo (Settings → Transactional → SMTP)
```

**Limites**: 300 emails/jour gratuit, illimité payant

---

### Mailtrap (Testing seulement)

```
Serveur SMTP:     sandbox.smtp.mailtrap.io
Port:             465 ou 2525
Chiffrement:      SSL
Nom d'utilisateur: votre username Mailtrap
Mot de passe:     votre password Mailtrap
```

**Attention**: Mailtrap ne livre pas réellement les emails, les capture pour inspection.

---

### MailerSend

```
Serveur SMTP:     smtp.mailersend.net
Port:             587
Chiffrement:      TLS
Nom d'utilisateur: MS_xxxxx (API Token comme username)
Mot de passe:     Laissez vide ou utilisez un dummy
```

**Limites**: 3000 emails/mois gratuit

---

### Postmark

```
Serveur SMTP:     smtp.postmarkapp.com
Port:             587
Chiffrement:      TLS
Nom d'utilisateur: postmark
Mot de passe:     Votre Server Token (Settings → Credentials)
Email d'envoi:    Doit être vérifié au préalable
```

**Limites**: 100 emails/mois gratuit

---

## Sécurité des credentials

### Problème: Les credentials dans la base de données

Le plugin stocke les credentials dans `wp_options` avec chiffrement simple. C'est acceptable pour la plupart des sites, mais pas idéal.

### Solution 1: wp-config.php (Recommandé)

Définez les constantes dans `wp-config.php` :

```php
// wp-config.php

define( 'ALERT404_SMTP_HOST', 'smtp.gmail.com' );
define( 'ALERT404_SMTP_PORT', 587 );
define( 'ALERT404_SMTP_USERNAME', 'your-email@gmail.com' );
define( 'ALERT404_SMTP_PASSWORD', 'your-app-password' );
define( 'ALERT404_SMTP_ENCRYPTION', 'tls' );
define( 'ALERT404_SMTP_FROM_EMAIL', 'your-email@gmail.com' );
define( 'ALERT404_SMTP_FROM_NAME', '404 Alert' );
```

**Avantage**:
- Credentials en dehors de la base de données
- Facilement deployable via CI/CD
- Plus sécurisé sur serveurs multi-tenant

**Désavantage**:
- Nécessite accès fichier wp-config.php

### Solution 2: Variables d'environnement

```php
// wp-config.php

define( 'ALERT404_SMTP_HOST', getenv( 'SMTP_HOST' ) );
define( 'ALERT404_SMTP_PASSWORD', getenv( 'SMTP_PASSWORD' ) );
// ... etc
```

Puis définir les variables en variables d'environnement système.

### Solution 3: Mot de passe d'application (Gmail/Microsoft)

Utilisez un **mot de passe d'application** au lieu du vrai mot de passe :
- Moins dangereux si exposé
- Peut être révoqué facilement
- Spécifique à l'application

---

## Test de connexion

### Via l'interface Settings

1. Allez à **Paramètres → Surveillance 404**
2. Descendez à **Configuration SMTP**
3. Cliquez sur **Tester la connexion**

Vous verrez:
- ✓ **Connexion réussie** → Les paramètres sont corrects
- ✗ **Connexion échouée** → Vérifiez les paramètres (voir Troubleshooting)

### Via la ligne de commande (WP-CLI)

```bash
# Tester directement
wp plugin eval '
require_once "includes/class-smtp-handler.php";
$result = Alert404_SMTP_Handler::test_connection();
print_r($result);
'
```

### Via WordPress Debug

Activez le debug logging dans `wp-config.php`:

```php
define( 'WP_DEBUG', true );
define( 'WP_DEBUG_LOG', true );
define( 'WP_DEBUG_DISPLAY', false );
```

Puis consultez `wp-content/debug.log` pour les erreurs SMTP.

---

## Troubleshooting

### 1. Connexion échouée: "Connection refused"

**Cause**: Port fermé ou serveur SMTP invalide

**Solutions**:
- Vérifiez que le serveur SMTP est correct (ex: `smtp.gmail.com`)
- Vérifiez le port:
  - Port 587 = TLS
  - Port 465 = SSL
  - Port 25 = SMTP non chiffré (déprécié)
- Vérifiez les pare-feu/WAF bloquant les connexions SMTP

**Test rapide**:
```bash
telnet smtp.gmail.com 587
```

### 2. Authentification échouée: "535 Invalid credentials"

**Cause**: Mauvais username/password

**Solutions**:
- Vérifiez le username (email pour Gmail)
- Vérifiez le mot de passe
- Pour Gmail: Utilisez mot de passe d'application, pas le mot de passe Google
- Pour Microsoft: Activez SMTP (peut être désactivé par politique)

### 3. TLS Error: "Must issue a STARTTLS command first"

**Cause**: Chiffrement mal configuré

**Solutions**:
- Utilisez **TLS** (port 587)
- OU utilisez **SSL** (port 465)
- N'utilisez pas STARTTLS avec port 465 (conflict)

### 4. Timeout: "Read timed out after 30 seconds"

**Cause**: Serveur SMTP lent ou réseau problématique

**Solutions**:
- Augmentez le timeout (configurable en code, défaut 30s)
- Testez la connectivité: `ping smtp.gmail.com`
- Vérifiez la bande passante
- Essayez un autre port (587 vs 465)

### 5. Gmail: "535 Login Attempt blocked"

**Cause**: Google bloque l'authentification (sécurité)

**Solutions**:
1. Activez 2FA sur votre compte Google
2. Générez un mot de passe d'application
3. Utilisez ce mot de passe au lieu du vrai mot de passe
4. Autoriser les appareils moins sécurisés: Allez à [myaccount.google.com](https://myaccount.google.com), cherchez "Less secure app access"

### 6. Microsoft 365: "535 5.7.139 Authentication unsuccessful"

**Cause**: Authentification 2FA ou policy bloquée

**Solutions**:
1. Vérifiez que SMTP est autorisé par l'admin Microsoft 365
2. Si MFA activé: Générez un mot de passe d'application
3. Essayez un authentification avec token OAuth (nécessite configuration complexe)

### 7. Emails non reçus (pas d'erreur)

**Cause**: Emails envoyés mais bloqués par le destinataire ou spam

**Solutions**:
- Vérifiez le dossier Spam/Junk
- Vérifiez les logs: `tail -f wp-content/debug.log | grep smtp`
- Testez avec une autre adresse email
- Vérifiez les "From Headers" (SPF, DKIM, DMARC)

### 8. "Certificate verify failed"

**Cause**: Problème SSL/TLS avec certificat

**Solutions**:
- Vérifiez que le serveur SMTP a un certificat SSL valide
- Downgrade à TLS au lieu de SSL
- Mise à jour PHP/OpenSSL

---

## Fallback à wp_mail()

Si SMTP n'est pas configuré ou échoue, le plugin utilise automatiquement la fonction WordPress `wp_mail()`.

### Comportement

```php
// Ordre de priorité:
1. SMTP configuré → Utilisé
2. SMTP échoue → Fallback à wp_mail()
3. wp_mail() échoue → Log l'erreur, arrête

// Configuration SMTP:
if ( Alert404_SMTP_Handler::is_configured() ) {
    return Alert404_SMTP_Handler::send();  // Réseau SMTP
}

// Fallback automatique:
return wp_mail( $to, $subject, $message, $headers );  // wp_mail
```

### Quand wp_mail() est utilisé

- SMTP non configuré (pas de host)
- Extension PHP mail() disponible
- Pas de configuration wp-config.php pour SMTP

### Configurer wp_mail() correctement

Dans `wp-config.php`:

```php
// Option 1: Utiliser sendmail (Linux)
define( 'SENDMAIL_PATH', '/usr/sbin/sendmail -t -i' );

// Option 2: Utiliser un relay SMTP local
define( 'WP_MAIL_SMTP', 'localhost' );

// Option 3: Installer un plugin SMTP (WP Mail SMTP, Easy WP SMTP, etc.)
```

---

## Checklist de production

Avant de déployer en production:

- [ ] SMTP configuré (via Settings ou wp-config.php)
- [ ] Test de connexion réussi ✓
- [ ] Test email reçu
- [ ] Adresse email valide configurée
- [ ] Authentification 2FA pour Gmail (si utilisé)
- [ ] Mot de passe d'application généré (si utilisé)
- [ ] SPF/DKIM/DMARC configurés (si serveur custom)
- [ ] Logging activé pour le débogage
- [ ] Limite quotidienne appropriée
- [ ] Cooldown IP approprié
- [ ] Adresse "From" vérifiée (si SendGrid/similaire)

---

## FAQ

### Q: Quel service SMTP recommandez-vous?
**A**: 
- **Gratuit**: Gmail (500/jour) ou Brevo (300/jour)
- **Payant**: SendGrid ($10+/mois) ou Mailgun ($10+/mois)
- **Enterprise**: Microsoft 365 ou Postmark

### Q: Puis-je utiliser mon serveur mail personnalisé?
**A**: Oui. Entrez simplement le serveur SMTP de votre hébergeur (demandez à l'support).

### Q: Les emails sont-ils chiffrés en transit?
**A**: Oui, si vous utilisez TLS (port 587) ou SSL (port 465).

### Q: Puis-je utiliser un relai SMTP local?
**A**: Oui. Configurez simplement `localhost` comme serveur SMTP.

### Q: Combien de temps avant d'être envoyé?
**A**: ~100ms. Les emails sont envoyés lors du traitement de la requête 404.

### Q: Que se passe-t-il si SMTP échoue?
**A**: L'email utilise le fallback `wp_mail()`. Aucune erreur utilisateur.

---

## Ressources

- [Gmail App Passwords](https://myaccount.google.com/apppasswords)
- [Microsoft 365 SMTP](https://learn.microsoft.com/en-us/exchange/clients-and-mobile-in-exchange-online/authenticated-client-smtp-submission)
- [SendGrid SMTP](https://sendgrid.com/docs/for-developers/sending-email/integrating-with-the-smtp-api/)
- [RFC 5321 - SMTP](https://tools.ietf.org/html/rfc5321)

---

**Besoin d'aide ?** Consultez les logs: `wp-content/debug.log` ou créez une issue.

**Dernière mise à jour**: 9 avril 2026
