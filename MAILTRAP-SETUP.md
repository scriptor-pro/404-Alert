# Configuration Mailtrap pour 404 Alert Plugin

Ce guide explique comment configurer **Mailtrap** pour tester l'envoi d'emails du plugin 404 Alert dans votre environnement WordPress local.

## Qu'est-ce que Mailtrap?

Mailtrap est un service SMTP gratuit de test qui capture tous les emails envoyés sans les envoyer réellement. Cela vous permet de:

- ✅ Tester l'envoi d'emails en développement
- ✅ Voir tous les emails dans une interface web
- ✅ Vérifier le contenu, les en-têtes, les pièces jointes
- ✅ Aucun spam réel ne sera envoyé

**Plan gratuit**: 500 emails/mois, illimité en nombre de projets/boîtes

---

## Étape 1: Créer un compte Mailtrap

1. Allez sur https://mailtrap.io
2. Cliquez sur **Sign Up** (en haut à droite)
3. Remplissez le formulaire:
   - Email: votre adresse email
   - Password: créez un mot de passe fort
   - Acceptez les conditions
4. Cliquez sur **Sign up**
5. Confirmez votre email (lien envoyé à votre boîte mail)

---

## Étape 2: Créer une boîte (Inbox) Mailtrap

Après connexion sur https://app.mailtrap.io:

### 2.1 - Accéder à Email Testing

```
Vue après connexion:
┌─────────────────────────────────────────────────────────────┐
│  MAILTRAP                                           🔔 👤    │
├─────────────────────────────────────────────────────────────┤
│ ☰ Menu                                                       │
│                                                              │
│ □ Email Sending                                              │
│ □ Email Sandbox                                              │
│   ├─ Email Testing  ← CLIQUEZ ICI                           │
│   ├─ Email Marketing                                         │
│   └─ Integrations                                            │
│                                                              │
│ □ Billing                                                    │
│ □ Settings                                                   │
└─────────────────────────────────────────────────────────────┘
```

**Action**: Cliquez sur **Email Testing** (c'est la section Sandbox)

### 2.2 - Page Email Testing

Vous arriverez sur cette page:

```
┌─────────────────────────────────────────────────────────────┐
│  Email Testing                           [+ Add Inbox]       │
├─────────────────────────────────────────────────────────────┤
│                                                              │
│  Vous pouvez voir:                                           │
│  - Une inbox par défaut appelée "Demo inbox" (optionnel)    │
│  - OU vous êtes sur une page vide                           │
│                                                              │
│  Si une inbox par défaut existe:                            │
│  ┌────────────────────────────────────┐                     │
│  │ 📧 Demo inbox                      │                     │
│  │ inbox_id: 1234567                  │                     │
│  │ 0 emails                           │                     │
│  └────────────────────────────────────┘                     │
│                                                              │
│  Si c'est vide, cliquez sur [+ Add Inbox] en haut à droite  │
└─────────────────────────────────────────────────────────────┘
```

### 2.3 - Créer une nouvelle Inbox

**Option A: Si vous voyez une inbox par défaut (RECOMMANDÉ)**

- ✅ Vous pouvez directement utiliser la **"Demo inbox"** existante
- Pas besoin de créer une nouvelle
- Passez à l'Étape 3

**Option B: Si vous voulez créer une nouvelle inbox**

1. Cliquez sur le bouton bleu **[+ Add Inbox]** (en haut à droite)

2. Un formulaire apparaîtra:

```
┌──────────────────────────────────────────────┐
│  Create new inbox                            │
├──────────────────────────────────────────────┤
│                                              │
│  Name:                                       │
│  ┌──────────────────────────────────────┐   │
│  │ 404-Alert-Local                      │   │
│  └──────────────────────────────────────┘   │
│                                              │
│  ☐ All emails from Inboxes                  │
│                                              │
│  [Cancel]  [Create Inbox]                   │
└──────────────────────────────────────────────┘
```

3. Remplissez le nom: `404-Alert-Local` (ou ce que vous préférez)

4. **IMPORTANT**: Assurez-vous que la case **"All emails from Inboxes"** est **décochée** ☐

   - Cette case est pour regrouper plusieurs inboxes
   - Pour l'instant, nous voulons une inbox unique

5. Cliquez sur **[Create Inbox]**

### 2.4 - Votre inbox est créée!

Après création, vous serez redirigé vers votre inbox:

```
┌─────────────────────────────────────────────────────────────┐
│  404-Alert-Local                          [Settings] [Delete]│
├─────────────────────────────────────────────────────────────┤
│                                                              │
│  📧 Inbox ID: 1234567                                       │
│  Status: Active ✓                                            │
│  Emails: 0                                                   │
│                                                              │
│  Tab: Inbox | SMTP Settings | API                           │
│                                                              │
│  [No emails yet]                                             │
│                                                              │
└─────────────────────────────────────────────────────────────┘
```

### 2.5 - Quoi faire ensuite?

Parfait! Votre inbox est créée.

**Vous êtes maintenant prêt pour l'Étape 3** (récupérer les credentials SMTP).

**Note importante**:

- Si vous aviez une "Demo inbox" par défaut, vous pouvez l'utiliser directement
- Si vous en avez créé une nouvelle, c'est celle-ci que vous utiliserez
- Les deux fonctionnent exactement pareil

---

## Étape 3: Récupérer vos credentials SMTP

Dans votre boîte Mailtrap:

1. Cliquez sur l'inbox que vous venez de créer
2. Allez dans l'onglet **Integrations** ou **SMTP Settings**
3. Vous verrez les informations SMTP:

```
Host: smtp.mailtrap.io
Port: 587 (TLS) ou 2525
Username: [votre username - long code]
Password: [votre password - long code]
```

**Exemple (les vrais codes seront différents)**:

```
Host: smtp.mailtrap.io
Port: 587
Username: abc123def456ghi
Password: xyz789uvw012rst
```

📌 **Gardez ces credentials à proximité**, vous en aurez besoin à l'étape suivante.

---

## Étape 4: Configurer Mailtrap dans le plugin 404 Alert

✅ **Le plugin 404 Alert gère maintenant directement l'envoi SMTP!** Aucun autre plugin n'est nécessaire.

### A. Allez à la page de configuration du plugin

1. Allez sur http://localhost:8080/wp-admin
2. Connectez-vous avec: `admin` / `admin123`
3. Allez dans **Paramètres** > **Surveillance 404**

### B. Remplissez les champs de configuration SMTP

Vous verrez deux sections:

#### Section 1: Paramètres de surveillance

- **Email destinataire**: Adresse email pour les alertes 404
- **Limite journalière**: Nombre maximum d'emails par jour (ex: 500)
- **Délai par IP**: Temps d'attente entre deux emails de la même IP (ex: 300 secondes)

#### Section 2: Configuration SMTP

Remplissez les champs avec vos credentials Mailtrap:

```
Serveur SMTP:        sandbox.smtp.mailtrap.io
Port SMTP:           587
Nom d'utilisateur:   dbfcdec39fc861 (votre username Mailtrap)
Mot de passe:        ****3f21 (votre password Mailtrap)
Chiffrement:         TLS (Port 587) ← Sélectionnez cette option
Email d'envoi:       admin@test.local
Nom d'envoi:         404 Alert
```

### C. Tester la connexion SMTP

1. Remplissez tous les champs ci-dessus
2. Cliquez sur le bouton **[Tester la connexion]** dans la section "Test de connexion"
3. Vous verrez un message:
   - ✅ **"Connexion SMTP réussie!"** si tout est correct
   - ❌ **"Erreur de connexion SMTP: ..."** si quelque chose ne va pas

### D. Sauvegarder les paramètres

Une fois la connexion SMTP testée avec succès:

1. Cliquez sur **[Enregistrer les paramètres]** en bas de la page
2. Vous verrez un message de confirmation: **"Les paramètres ont été enregistrés avec succès."**

---

## ⚠️ Important: Où obtenir vos credentials Mailtrap?

Sur votre page Mailtrap (https://app.mailtrap.io):

1. Allez dans **Sandboxes** > **My Inbox** (ou votre inbox personnalisée)
2. Allez à l'onglet **Integration**
3. Cliquez sur le bouton **SMTP** pour voir les credentials:

```
Host:     sandbox.smtp.mailtrap.io
Port:     587 (recommandé)
Username: dbfcdec39fc861
Password: Cliquez sur l'œil pour le voir en entier
```

Copiez-collez ces credentials dans les champs du plugin 404 Alert.

---

## Étape 5: Tester l'envoi d'emails avec le plugin 404 Alert

Une fois Mailtrap configuré:

1. Allez sur http://localhost:8080
2. Essayez d'accéder à une page inexistante:
   ```
   http://localhost:8080/this-page-does-not-exist
   ```
3. Le plugin 404 Alert détectera le 404 et enverra un email

4. Allez sur https://mailtrap.io dans votre inbox
5. Vous devriez voir l'email s'afficher avec:
   - ✅ Subject: `404 Alert: Page not found`
   - ✅ From: `admin@test.local`
   - ✅ Contenu: détails de la page manquante

---

## Dépannage

### L'email n'apparaît pas dans Mailtrap

1. **Vérifiez les logs WordPress**:

   ```bash
   tail -f ~/wordpress-local/wp-content/debug.log
   ```

   Cherchez les lignes contenant `[404-Alert]`

2. **Vérifiez que le plugin 404 Alert est activé**:

   - Allez sur http://localhost:8080/wp-admin/plugins.php
   - Vérifiez que "404 Alert" est bien actif (coché)

3. **Vérifiez vos credentials Mailtrap**:

   - Sur https://mailtrap.io, vérifiez que Username et Password sont corrects
   - Assurez-vous de ne pas avoir d'espaces au début ou à la fin

4. **Testez manuellement avec WP Mail SMTP**:
   - Allez dans **Paramètres** > **WP Mail SMTP**
   - Cliquez sur **Test Email** en bas
   - Cela enverra un email de test que vous verrez immédiatement dans Mailtrap

### Erreur: "SMTP connection failed"

1. Vérifiez le port (devrait être 587 ou 2525)
2. Vérifiez que TLS est bien sélectionné (pas SSL)
3. Vérifiez votre connexion internet

### L'email arrive mais le contenu est vide

1. Vérifiez que le plugin 404 Alert est bien configuré dans **Paramètres** > **404 Alert**
2. Vérifiez que l'email de notification est rempli

---

## Ressources

- **Mailtrap Docs**: https://mailtrap.io/blog/
- **WP Mail SMTP**: https://wpmailsmtp.com/
- **Plugin 404 Alert - IMPROVEMENTS.md**: Voir la section "Email Verification" pour les détails techniques

---

## Prochaines étapes

Une fois Mailtrap configuré et testé:

1. ✅ Vous pouvez maintenant développer et tester les emails localement
2. 📧 Tous les emails iront dans Mailtrap (aucun spam réel)
3. 🔍 Vous pouvez inspecter le contenu, les en-têtes, les logs
4. 📝 Pour la **production**, vous utiliserez un vrai service SMTP (Brevo, SendGrid, Gmail, etc.)

---

**Besoin d'aide?** Consultez le fichier `IMPROVEMENTS.md` pour les détails techniques du plugin 404 Alert.
