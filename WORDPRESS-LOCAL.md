# Lancer WordPress en local pour tester 404 Alert

## Option 1 : Docker (recommandé, le plus rapide)

### Prérequis

- **Docker** et **Docker Compose** installés
  - Linux : `sudo apt install docker.io docker-compose`
  - macOS : installer [Docker Desktop](https://www.docker.com/products/docker-desktop)
  - Windows : installer [Docker Desktop](https://www.docker.com/products/docker-desktop)

### Étapes (5 minutes)

#### 1. Créer un dossier de travail

```bash
mkdir wordpress-local && cd wordpress-local
```

#### 2. Créer le fichier `docker-compose.yml`

```bash
cat > docker-compose.yml << 'EOF'
version: '3.8'

services:
  wordpress:
    image: wordpress:latest
    ports:
      - "8080:80"
    environment:
      WORDPRESS_DB_HOST: db
      WORDPRESS_DB_NAME: wordpress
      WORDPRESS_DB_USER: wordpress
      WORDPRESS_DB_PASSWORD: wordpress123
      WORDPRESS_DEBUG: "true"
    volumes:
      - ./wp-content/plugins:/var/www/html/wp-content/plugins
      - ./wp-content/themes:/var/www/html/wp-content/themes
    depends_on:
      - db

  db:
    image: mysql:8.0
    environment:
      MYSQL_DATABASE: wordpress
      MYSQL_ROOT_PASSWORD: root123
      MYSQL_USER: wordpress
      MYSQL_PASSWORD: wordpress123
    volumes:
      - db_data:/var/lib/mysql

volumes:
  db_data:
EOF
```

#### 3. Lancer les conteneurs

```bash
docker-compose up -d
```

Attendre 15-20 secondes que WordPress démarre.

#### 4. Vérifier que tout tourne

```bash
docker-compose ps
```

Tu dois voir :

```
NAME               STATUS
wordpress-local-wordpress-1   Up 10 seconds
wordpress-local-db-1          Up 10 seconds
```

#### 5. Accéder à WordPress

Ouvrir dans le navigateur : **http://localhost:8080**

Tu vas voir l'écran d'installation WordPress.

#### 6. Configuration initiale

- **Langue** : Français
- **Titre du site** : "Test 404 Alert" (ou ce que tu veux)
- **Identifiant** : `admin`
- **Mot de passe** : quelque chose de simple (ex: `admin123`)
- **Email** : n'importe quel email (ex: `test@example.com`)
- Cliquer **Installer WordPress**

#### 7. Se connecter

- Aller à http://localhost:8080/wp-admin
- Identifiant : `admin`
- Mot de passe : celui que tu as choisi

---

## Option 2 : WordPress sans Docker (sur serveur local)

Si tu n'as pas Docker, tu peux installer WordPress directement.

### Prérequis

- PHP 8.1+ avec extension MySQL
- MySQL/MariaDB en cours d'exécution
- Serveur web (Apache, Nginx) ou serveur PHP intégré

### Étapes

#### 1. Télécharger WordPress

```bash
# Aller dans ton dossier de projets
cd ~/Documents/Projets

# Télécharger WordPress
wget https://wordpress.org/latest.zip
unzip latest.zip
cd wordpress
```

#### 2. Configurer la base de données

```bash
# Créer la base de données (via MySQL CLI)
mysql -u root -p << 'EOF'
CREATE DATABASE wordpress_local;
CREATE USER 'wordpress'@'localhost' IDENTIFIED BY 'wordpress123';
GRANT ALL PRIVILEGES ON wordpress_local.* TO 'wordpress'@'localhost';
FLUSH PRIVILEGES;
EOF
```

#### 3. Configurer `wp-config.php`

```bash
# Copier le fichier de configuration
cp wp-config-sample.php wp-config.php

# Éditer et remplacer les credentials
nano wp-config.php
```

Chercher et remplacer :

```php
define( 'DB_NAME', 'wordpress_local' );
define( 'DB_USER', 'wordpress' );
define( 'DB_PASSWORD', 'wordpress123' );
define( 'DB_HOST', 'localhost' );
```

#### 4. Lancer le serveur PHP intégré

```bash
php -S localhost:8080
```

Accéder à http://localhost:8080

#### 5. Configuration initiale

Même process que Option 1 étape 6.

---

## Installer le plugin 404 Alert

### Avec Docker (recommandé)

#### 1. Copier le plugin

```bash
# Depuis le dossier wordpress-local
cp -r /home/Baudouin/Documents/Projets/404-alert wp-content/plugins/
```

Vérifier que le fichier existe :

```bash
ls wp-content/plugins/404-alert/404-alert.php
```

#### 2. Recharger WordPress

Accéder à http://localhost:8080/wp-admin

Le plugin devrait apparaître dans **Extensions > Plugins installés** (chercher "404 Alert").

#### 3. Activer le plugin

Cliquer **Activer** à côté de "404 Alert".

### Sans Docker

```bash
# Copier depuis le projet
cp -r /home/Baudouin/Documents/Projets/404-alert ~/Documents/Projets/wordpress/wp-content/plugins/
```

Puis activer depuis wp-admin.

---

## Tester le plugin

### 1. Configurer (optionnel)

- Aller à **Réglages > 404 Alert**
- Vérifier l'email destinataire (par défaut : email administrateur)
- Laisser les seuils par défaut
- Cliquer **Enregistrer les modifications**

### 2. Déclencher un 404

Visiter une URL inexistante :

```
http://localhost:8080/page-inexistante
```

Tu dois voir :

- ✅ La page 404 WordPress standard
- 📧 Un email envoyé (attendre 2-3 secondes)

### 3. Vérifier l'email

Les emails envoyés via `wp_mail()` en local sont **stockés dans les logs** ou captés par un plugin de test.

#### Voir les logs (Docker)

```bash
docker-compose logs wordpress | grep -i mail
```

#### Utiliser un plugin de capture d'email (WordPress)

1. Dans wp-admin, aller à **Extensions > Ajouter**
2. Chercher "Mail Capture" ou "Email Log"
3. Installer et activer
4. Aller à **Outils > Email Log** (ou équivalent)
5. Les emails y apparaîtront

Recommandé : **"WP Mail SMTP"** (gratuit, interface claire)

---

## Arrêter & nettoyer

### Avec Docker

```bash
# Arrêter les conteneurs
docker-compose down

# Arrêter et supprimer tout (données incluses)
docker-compose down -v
```

### Sans Docker

```bash
# Arrêter le serveur PHP
# Ctrl+C dans le terminal où tu as lancé `php -S localhost:8080`
```

---

## Dépannage

### "Connection refused" au démarrage

```bash
# Vérifier que Docker est bien lancé
docker ps

# Si vide, relancer Docker
docker-compose up -d
```

### WordPress affiche une page blanche

```bash
# Vérifier les logs
docker-compose logs wordpress

# Redémarrer les conteneurs
docker-compose restart
```

### Le plugin n'apparaît pas dans les Extensions

1. Vérifier que le fichier est bien dans `wp-content/plugins/404-alert/404-alert.php`
2. Vérifier les permissions : `chmod 755 wp-content/plugins/404-alert/`
3. Aller à **Extensions** et faire un refresh (F5)
4. Vérifier les erreurs PHP dans wp-admin

### Pas d'email reçu

1. Installer le plugin **WP Mail SMTP** ou **Mail Capture**
2. Vérifier les logs du serveur : `docker-compose logs wordpress`
3. Tester un simple `wp_mail()` depuis wp-admin

---

## Accès rapide (Docker)

```bash
# Depuis le dossier wordpress-local

# Lancer
docker-compose up -d

# Arrêter
docker-compose down

# Voir les logs
docker-compose logs -f wordpress

# Entrer dans le conteneur WordPress
docker exec -it wordpress-local-wordpress-1 bash
```

C'est tout ! 🚀
