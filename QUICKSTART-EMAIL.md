# ⚡ Quick Start - Configurer l'Email en 5 Minutes

**Pour les gens pressés:** Voici comment configurer l'envoi d'email du plugin 404 Alert en 5 minutes.

---

## 🎯 Étape 1: Obtenir les Credentials (Choisir 1 option)

### Option 1️⃣ Gmail (Plus facile)

1. Allez sur https://myaccount.google.com
2. **Sécurité** → **Vérification en 2 étapes** → Activez (si pas fait)
3. **Sécurité** → **Mots de passe des app** → Créer
   - App: Mail
   - Appareil: Windows
   - Copier le code 16 caractères généré

✅ **Notez:**

```
Serveur: smtp.gmail.com
Port: 587
Email: votre.email@gmail.com
Password: [code 16 caractères]
Chiffrement: TLS
```

---

### Option 2️⃣ Brevo (Plus stable)

1. Allez sur https://www.brevo.com → S'inscrire
2. Vérifiez votre email
3. **Paramètres** (engrenage) → **SMTP & API**
4. Cliquez **Generate SMTP Key**
5. Copier la clé

✅ **Notez:**

```
Serveur: smtp-relay.brevo.com
Port: 587
Email: votre.email@brevo.com
Password: [clé SMTP]
Chiffrement: TLS
```

---

### Option 3️⃣ Votre Hébergeur

1. Connectez-vous au panel (cPanel/Plesk/OVH)
2. Cherchez "Email" ou "SMTP"
3. Copier les infos

---

## ⚙️ Étape 2: Configurer le Plugin (2 minutes)

1. **WordPress Admin** → **Paramètres** → **Surveillance 404**

2. **Remplissez:**

   ```
   Email destinataire: admin@monsite.com

   Serveur SMTP: smtp.gmail.com (ou smtp-relay.brevo.com)
   Port: 587
   Username: votre.email@gmail.com
   Password: [votre mot de passe]
   Chiffrement: TLS
   Email d'envoi: noreply@monsite.com
   Nom d'envoi: 404 Alerts
   ```

3. Cliquez **[Tester la connexion]** → attendez ✅

4. Cliquez **[Enregistrer les paramètres]**

---

## ✅ Étape 3: Tester (1 minute)

1. Accédez à une URL inexistante:

   ```
   https://monsite.com/ceci-n-existe-pas
   ```

2. Vérifiez votre email → vous devez recevoir une alerte 404

3. ✅ C'est bon!

---

## ❌ Ça n'a pas marché?

| Erreur                   | Solution                                       |
| ------------------------ | ---------------------------------------------- |
| "Connexion SMTP refusée" | Vérifiez les credentials exactement            |
| "Port invalide"          | Utilisez 587 (ou 465)                          |
| "Pas d'email reçu"       | Vérifiez le dossier Spam                       |
| "Timeout"                | Votre serveur bloque le port? Essayez port 465 |

---

**C'est tout! Vous pouvez arrêter là.** 🎉

Pour plus de détails, lisez `CONFIGURATION-PRODUCTION.md`.
