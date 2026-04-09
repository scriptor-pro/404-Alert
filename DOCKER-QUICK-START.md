# 🚀 WordPress + 404 Alert avec Docker — Guide rapide

## Installation en 2 étapes

### ✅ Étape 1 : Prérequis

Installer Docker Desktop :
- **macOS/Windows** : https://www.docker.com/products/docker-desktop
- **Linux** : `sudo apt install docker.io docker-compose`

Vérifier l'installation :
```bash
docker --version
docker-compose --version
```

### ✅ Étape 2 : Lancer WordPress

```bash
/home/Baudouin/Documents/Projets/404-alert/start-wordpress.sh
```

C'est tout ! ✨

Le script va :
1. ✅ Créer un dossier `~/wordpress-local`
2. ✅ Télécharger les images Docker (WordPress, MySQL, phpMyAdmin)
3. ✅ Lancer les conteneurs
4. ✅ Copier le plugin 404 Alert
5. ✅ Afficher les accès

Attendre 20 secondes que tout démarre, puis ouvrir : **http://localhost:8080**

---

## 📖 Première utilisation

### 1. Installation WordPress

À la première visite, tu vas voir l'écran d'installation :

1. **Langue** : Français (ou English)
2. **Titre du site** : "Test 404 Alert" (ce que tu veux)
3. **Identifiant** : `admin`
4. **Mot de passe** : `admin123` (ou ce que tu veux)
5. **Email** : `test@example.com` (ou le tien)
6. Cliquer **Installer WordPress**

### 2. Se connecter

Aller à : **http://localhost:8080/wp-admin**

Identifiant : `admin`
Mot de passe : celui que tu as choisi

### 3. Activer le plugin

1. Aller à **Extensions > Plugins installés**
2. Chercher **404 Alert**
3. Cliquer **Activer**

C'est bon ! Le plugin est actif.

---

## 🧪 Tester le plugin

### Test 1 : Détection 404

1. Visiter une URL inexistante :
   ```
   http://localhost:8080/ceci-nexiste-pas
   ```

2. Tu dois voir :
   - ✅ Une page d'erreur 404 WordPress
   - 📧 Un email envoyé (attendre 2-3 secondes)

### Test 2 : Voir l'email

Les emails en local sont capturés (pas envoyés vraiment).

#### Option A : Via phpMyAdmin

1. Ouvrir : **http://localhost:8081**
2. Identifiant : `wordpress`
3. Mot de passe : `wordpress123`
4. Table `wp_options` → clé `404_alert_options` (vérifier que l'email est bien sauvegardé)

#### Option B : Via les logs

```bash
cd ~/wordpress-local
docker-compose logs wordpress | grep -i mail
```

Tu dois voir :
```
wp_mail() called with:
To: test@example.com
Subject: 404 sur Test 404 Alert - http://localhost:8080/ceci-nexiste-pas
```

#### Option C : Installer un plugin de capture (WordPress)

1. Dans wp-admin : **Extensions > Ajouter**
2. Chercher **"Mail Capture"** ou **"WP Mail SMTP"** (gratuit)
3. Installer et activer
4. Les emails vont s'afficher dans un menu dédié

### Test 3 : Rate limiting par IP

1. Visiter : http://localhost:8080/inexistante
2. Recharger immédiatement
   - ❌ Pas d'email (bloqué par rate limit 5 min)
3. Attendre 5 minutes
4. Recharger
   - ✅ Nouvel email reçu (rate limit dépassé)

### Test 4 : Configurer les limites

1. Aller à **Réglages > 404 Alert**
2. Modifier :
   - Email destinataire
   - Limite journalière (tester avec 2 pour déboguer)
   - Délai par IP (tester avec 30 secondes pour tester plus vite)
3. Cliquer **Enregistrer les modifications**
4. Retester

---

## 🛠️ Commandes utiles

### Voir le statut
```bash
cd ~/wordpress-local
docker-compose ps
```

### Voir les logs
```bash
cd ~/wordpress-local
docker-compose logs -f wordpress
# ou pour MySQL
docker-compose logs -f db
```

### Arrêter (sans supprimer les données)
```bash
cd ~/wordpress-local
docker-compose stop
```

### Redémarrer
```bash
cd ~/wordpress-local
docker-compose restart
```

### Arrêter et supprimer tout
```bash
cd ~/wordpress-local
docker-compose down -v
# Les données WordPress et la base sont supprimées
```

### Entrer dans le conteneur (pour déboguer)
```bash
cd ~/wordpress-local
docker exec -it wordpress-local-wordpress-1 bash
# Maintenant tu es dans le conteneur
# ls / → voir les fichiers
# exit → quitter
```

---

## 📊 Accès aux services

| Service | URL | Identifiant |
|---|---|---|
| **WordPress (site)** | http://localhost:8080 | — |
| **WordPress (admin)** | http://localhost:8080/wp-admin | admin / ton mdp |
| **phpMyAdmin (BD)** | http://localhost:8081 | wordpress / wordpress123 |
| **404 Alert settings** | http://localhost:8080/wp-admin/options-general.php?page=404_alert | — |

---

## 🐛 Dépannage

### "Connection refused"
```bash
# Vérifier que Docker tourne
docker ps

# Si vide, redémarrer
cd ~/wordpress-local
docker-compose up -d
```

### "404 Alert n'apparaît pas dans Extensions"
```bash
# Vérifier que le plugin est bien copié
ls ~/wordpress-local/wp-content/plugins/404-alert/404-alert.php

# Si absent, le copier manuellement
cp -r /home/Baudouin/Documents/Projets/404-alert ~/wordpress-local/wp-content/plugins/

# Puis recharger la page Extensions (F5)
```

### Page blanche WordPress
```bash
# Vérifier les logs
cd ~/wordpress-local
docker-compose logs wordpress
```

### Les emails ne s'envoient pas
1. Vérifier que le plugin est bien activé
2. Vérifier les logs : `docker-compose logs wordpress | grep mail`
3. Installer un plugin de capture d'email (WP Mail SMTP)
4. Vérifier l'email dans Réglages > 404 Alert

---

## 📝 Fichiers importants

```
~/wordpress-local/
├── docker-compose.yml          # Config Docker
├── wp-content/
│   ├── plugins/
│   │   └── 404-alert/          # Le plugin
│   └── themes/
└── (données WordPress)
```

---

## 🧹 Cleanup complet (après les tests)

```bash
cd ~/wordpress-local
docker-compose down -v
cd ~
rm -rf wordpress-local
```

Tout est supprimé. Zéro trace sur ton système. 🎉

---

## Questions ?

Consulte les autres fichiers du plugin :
- `README.md` — Documentation complète du plugin
- `COMPARAISON-INSTALLATION.md` — Comparaison Docker vs Direct

Bonne chance ! 🚀
