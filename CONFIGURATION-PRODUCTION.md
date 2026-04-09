# Configuration Envoi Email en Production - Guide Utilisateur

Ce guide explique comment configurer l'envoi d'emails pour le plugin **404 Alert** en production, de manière simple et sécurisée.

## 📋 Vue d'ensemble

Le plugin 404 Alert envoie automatiquement une notification email chaque fois qu'un visiteur accède à une page inexistante (erreur 404) sur votre site WordPress.

**Pour que cela fonctionne, vous avez besoin de:**

1. ✅ Un serveur SMTP (fourni par votre hébergeur ou un service gratuit)
2. ✅ Les identifiants SMTP
3. ✅ 5 minutes pour configurer le plugin

---

## 🚀 Étape 1: Choisir un Service Email

Voici les options les plus populaires et éprouvées:

### Option A: Gmail (Gratuit - Recommandé pour Débutants)

**Avantages:**

- ✅ Gratuit
- ✅ Très fiable
- ✅ Facile à configurer
- ✅ Limite: ~500 emails/jour

**Inconvénients:**

- Nécessite une étape supplémentaire (App Password)

**Procédure:**

1. **Allez sur votre compte Google**

   - Ouvrez https://myaccount.google.com
   - Connectez-vous avec votre compte Gmail

2. **Activez la vérification en 2 étapes (si ce n'est pas fait)**

   - Allez dans **Sécurité** (menu à gauche)
   - Cherchez "Vérification en 2 étapes"
   - Cliquez sur **Activer la vérification en 2 étapes**
   - Suivez les instructions (SMS ou app d'authentification)

3. **Créez un "App Password"**

   - Allez dans **Sécurité**
   - Cherchez "Mots de passe des app" (en bas)
   - Cliquez dessus
   - Sélectionnez "Mail" et "Windows" (ou l'appareil)
   - Google génère un mot de passe à 16 caractères → **Copiez-le**

4. **Notez vos identifiants:**
   ```
   Serveur SMTP: smtp.gmail.com
   Port: 587
   Username: votre.email@gmail.com
   Password: [le mot de passe généré par Google - 16 caractères]
   Chiffrement: TLS
   ```

---

### Option B: Brevo (Gratuit - Recommandé pour Production)

**Avantages:**

- ✅ Gratuit
- ✅ Spécialisé email (meilleure délivrabilité)
- ✅ Limite: 300 emails/jour
- ✅ No configuration complexity

**Procédure:**

1. **Créez un compte Brevo**

   - Allez sur https://www.brevo.com
   - Cliquez sur "S'inscrire" (en haut à droite)
   - Remplissez le formulaire avec votre email
   - Vérifiez votre email (lien de confirmation)

2. **Obtenez les credentials SMTP**

   - Allez dans **Paramètres** (icône engrenage, en haut à droite)
   - Cliquez sur **SMTP & API**
   - Cherchez "SMTP" → Vous verrez:
     ```
     Server: smtp-relay.brevo.com
     Port: 587
     Username: [votre email Brevo]
     Password: [clé SMTP - cliquez sur "Generate SMTP key"]
     ```

3. **Notez vos identifiants:**
   ```
   Serveur SMTP: smtp-relay.brevo.com
   Port: 587
   Username: votre.email@brevo.com
   Password: [clé SMTP générée]
   Chiffrement: TLS
   ```

---

### Option C: Votre Hébergeur (Si fourni)

**Avantages:**

- ✅ Souvent déjà configuré
- ✅ Meilleure délivrabilité locale

**Comment trouver vos credentials:**

1. **Connectez-vous à votre panel d'hébergement** (cPanel, Plesk, OVH, etc.)
2. **Cherchez "Email" ou "SMTP"**
3. **Vous trouverez quelque chose comme:**

   ```
   Serveur SMTP: mail.monsite.com
   Port: 587 ou 465
   Username: admin@monsite.com
   Password: [votre mot de passe]
   Chiffrement: TLS ou SSL
   ```

4. **Notez ces informations**

---

## ⚙️ Étape 2: Configurer le Plugin WordPress

### Accès à la Configuration

1. **Connectez-vous à WordPress**

   - Allez sur `https://monsite.com/wp-admin`
   - Entrez votre login/password admin

2. **Allez aux Paramètres du Plugin**
   - Menu à gauche: **Paramètres** > **Surveillance 404**

### Remplir la Configuration

Vous verrez deux sections:

#### Section 1: Paramètres de Surveillance

```
Email destinataire:     admin@monsite.com
                        (ou l'email où vous voulez recevoir les alertes)

Limite journalière:     500
                        (nombre max d'emails par jour)

Délai par IP (secondes): 300
                         (5 minutes - évite les spams d'une même IP)
```

#### Section 2: Configuration SMTP

Remplissez avec les credentials que vous avez notés plus haut:

```
Serveur SMTP:           smtp.gmail.com      (ou smtp-relay.brevo.com)
Port SMTP:              587
Nom d'utilisateur:      votre.email@gmail.com
Mot de passe:           [le mot de passe/clé]
Chiffrement:            TLS (Port 587)
Email d'envoi:          noreply@monsite.com (ou votre email admin)
Nom d'envoi:            Mon Site 404 Alerts (ou ce que vous préférez)
```

**⚠️ Points importants:**

- **Port 587 + TLS** = Configuration standard recommandée
- **Port 465 + SSL** = Alternative (moins courante)
- **Ne jamais** mettre Port 25 en production
- **Le mot de passe est chiffré** avant sauvegarde (sécurisé)

### Tester la Connexion

1. **Cliquez sur [Tester la connexion]**
2. Attendez quelques secondes
3. Vous verrez:
   - ✅ **"Connexion SMTP réussie!"** → Configuration OK
   - ❌ **"Erreur de connexion SMTP: ..."** → Vérifiez les identifiants

**Si erreur:**

- Vérifiez que vos credentials sont exacts
- Pour Gmail: avez-vous généré l'App Password? (pas le mot de passe Gmail normal)
- Pour Brevo: la clé SMTP est-elle valide?
- Essayez le Port 465 au lieu de 587

### Sauvegarder

1. Cliquez sur **[Enregistrer les paramètres]**
2. Vous verrez: "Les paramètres ont été enregistrés avec succès"

---

## ✅ Étape 3: Tester l'Envoi d'Email

### Test Automatique (Recommandé)

1. Allez à une URL inexistante sur votre site:

   ```
   https://monsite.com/page-qui-n-existe-pas-1234
   ```

2. Vous devriez voir une **page 404**

3. Vérifiez votre email (celui que vous avez configuré dans "Email destinataire")

4. Vous devriez recevoir un email avec:
   - ✅ Titre: "🚨 404 sur [Nom du site]"
   - ✅ L'URL accédée
   - ✅ Le navigateur/OS du visiteur
   - ✅ L'IP
   - ✅ Et d'autres infos

### Test Manual (Si vous n'avez pas reçu l'email)

1. Allez à **Paramètres > Surveillance 404**
2. Cliquez à nouveau sur **[Tester la connexion]**
3. Vérifiez:
   - ✅ "Connexion SMTP réussie!" = Le serveur répond
   - ❌ Erreur = Vérifiez les identifiants

---

## 🔍 Dépannage

### Je ne reçois pas les emails

**Vérification 1: Configuration SMTP**

- Allez à **Paramètres > Surveillance 404**
- Cliquez sur **[Tester la connexion]**
- Si ❌ erreur: Vérifiez Host, Port, Username, Password

**Vérification 2: Emails du plugin**

- Cherchez dans votre dossier **Spam/Indésirables**
- Marquez l'email comme "Pas du spam"

**Vérification 3: Rate Limiting**

- Si vous avez accédé à plusieurs 404 d'affilée, c'est peut-être bloqué par le rate limiter
- Attendez 5 minutes (délai par défaut)
- Puis accédez à une autre URL inexistante

**Vérification 4: Logs WordPress**

- Connectez-vous en FTP/SFTP
- Allez dans `/wp-content/`
- Ouvrez `debug.log`
- Cherchez `[404-Alert]`
- Vous verrez si l'email a été envoyé ou pourquoi il a échoué

---

## 🔒 Sécurité

### Bonnes Pratiques

✅ **À FAIRE:**

- Utilisez TLS (Port 587) plutôt que SSL
- Ne partagez jamais vos credentials SMTP
- Changez régulièrement votre App Password (Gmail)
- Utilisez un email séparé pour les notifications (si possible)
- Limitez le nombre d'emails/jour dans les paramètres

❌ **À ÉVITER:**

- Ne pas utiliser le Port 25 (risqué en production)
- Ne pas mettre les credentials en dur dans du code
- Ne pas utiliser votre mot de passe WordPress comme password SMTP
- Ne pas désactiver les limites de rate limiting

### Données Sensibles

- ✅ Les credentials SMTP sont **chiffrés** en base de données
- ✅ Seuls les **administrateurs WordPress** peuvent les voir
- ✅ Les **visiteurs** ne peuvent pas y accéder
- ✅ Les logs ne contiennent **pas** les mots de passe

---

## 📊 Comparaison des Services

| Service       | Gratuit | Limit/jour | Config | Délivrabilité | Support   |
| ------------- | ------- | ---------- | ------ | ------------- | --------- |
| **Gmail**     | ✅      | 500        | ⭐⭐   | Excellente    | Très bon  |
| **Brevo**     | ✅      | 300        | ⭐⭐   | Excellente    | Très bon  |
| **SendGrid**  | ✅      | 100        | ⭐⭐⭐ | Excellente    | Excellent |
| **Hébergeur** | ✅      | Illimité   | ⭐     | Variable      | Variable  |

---

## 💡 Conseils

### Pour Blogueurs/PME

→ Utilisez **Gmail** (simple et efficace)

### Pour Sites Importants

→ Utilisez **Brevo** ou **SendGrid** (mieux pour la délivrabilité)

### Pour Agences

→ Utilisez **votre serveur SMTP** (meilleur contrôle)

### Réduire les Emails Reçus

Si vous recevez trop d'emails:

1. Réduisez la **"Limite journalière"** à 50 au lieu de 500
2. Augmentez le **"Délai par IP"** à 3600 (1 heure) au lieu de 300 (5 min)
3. Mettez en place un **monitoring** séparé si vraiment besoin

---

## 📞 Support

**Problème avec la configuration?**

1. **Vérifiez les logs WordPress:**

   ```
   /wp-content/debug.log
   ```

   Cherchez `[404-Alert]`

2. **Testez la connexion SMTP:**

   - Allez à Paramètres > Surveillance 404
   - Cliquez [Tester la connexion]
   - Le message d'erreur indique le problème

3. **Consultez la FAQ du service:**
   - Gmail: https://support.google.com/accounts/answer/185833
   - Brevo: https://www.brevo.com/fr/help/
   - SendGrid: https://sendgrid.com/docs/

---

## Prochaines Étapes

Une fois configuré:

- ✅ Le plugin enverra automatiquement un email à chaque 404
- ✅ Vous recevrez l'URL accédée et des infos sur le visiteur
- ✅ Vous pourrez identifier les pages manquantes
- ✅ Vous pourrez corriger les erreurs 404 importantes

**Idée:** Configurez une redirection pour les 404 les plus fréquentes vers votre page d'accueil ou un formulaire de recherche!

---

**Besoin d'aide?** Consultez `MAILTRAP-SETUP.md` pour un exemple de configuration détaillé avec captures d'écran.
