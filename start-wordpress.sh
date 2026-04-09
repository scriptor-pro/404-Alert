#!/bin/bash

# Script de démarrage rapide : WordPress + 404 Alert en local avec Docker

set -e  # Arrêter si une commande échoue

echo "🚀 Lancement de WordPress local avec Docker..."
echo ""

# Créer le dossier de travail
WORK_DIR="$HOME/wordpress-local"
if [ -d "$WORK_DIR" ]; then
    echo "⚠️  Le dossier $WORK_DIR existe déjà."
    read -p "Veux-tu le supprimer et recommencer ? (y/n) " -n 1 -r
    echo
    if [[ $REPLY =~ ^[Yy]$ ]]; then
        rm -rf "$WORK_DIR"
        echo "✅ Ancien dossier supprimé"
    else
        echo "Utilisation du dossier existant..."
    fi
fi

mkdir -p "$WORK_DIR"
cd "$WORK_DIR"

echo "📁 Dossier de travail : $WORK_DIR"
echo ""

# Créer le docker-compose.yml
echo "📝 Création du docker-compose.yml..."
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
      WORDPRESS_CONFIG_EXTRA: |
        define('WP_DEBUG_LOG', true);
        define('WP_DEBUG_DISPLAY', false);
    volumes:
      - ./wp-content/plugins:/var/www/html/wp-content/plugins
      - ./wp-content/themes:/var/www/html/wp-content/themes
      - wordpress_data:/var/www/html
    depends_on:
      - db
    restart: unless-stopped

  db:
    image: mysql:8.0
    environment:
      MYSQL_DATABASE: wordpress
      MYSQL_ROOT_PASSWORD: root123
      MYSQL_USER: wordpress
      MYSQL_PASSWORD: wordpress123
    volumes:
      - db_data:/var/lib/mysql
    restart: unless-stopped

  phpmyadmin:
    image: phpmyadmin:latest
    ports:
      - "8081:80"
    environment:
      PMA_HOST: db
      PMA_USER: wordpress
      PMA_PASSWORD: wordpress123
    depends_on:
      - db
    restart: unless-stopped

volumes:
  wordpress_data:
  db_data:
EOF
echo "✅ docker-compose.yml créé"
echo ""

# Créer la structure wp-content
echo "📂 Création de la structure wp-content..."
mkdir -p wp-content/plugins
mkdir -p wp-content/themes
echo "✅ Structure créée"
echo ""

# Copier le plugin 404 Alert
PLUGIN_SOURCE="/home/Baudouin/Documents/Projets/404-alert"
if [ -d "$PLUGIN_SOURCE" ]; then
    echo "📦 Copie du plugin 404 Alert..."
    cp -r "$PLUGIN_SOURCE" wp-content/plugins/
    echo "✅ Plugin 404 Alert copié"
    echo ""
else
    echo "⚠️  Plugin 404 Alert introuvable à $PLUGIN_SOURCE"
    echo "   Tu pourras le copier manuellement plus tard"
    echo ""
fi

# Lancer Docker
echo "🐳 Démarrage des conteneurs Docker..."
echo ""

if ! command -v docker &> /dev/null; then
    echo "❌ Docker n'est pas installé. Installe-le depuis https://www.docker.com/products/docker-desktop"
    exit 1
fi

if ! command -v docker &> /dev/null; then
    echo "❌ Docker n'est pas installé."
    exit 1
fi

docker compose up -d

echo ""
echo "⏳ Attente du démarrage de WordPress (15-20 secondes)..."
sleep 20

# Vérifier le statut
echo ""
echo "📊 Statut des conteneurs :"
docker compose ps
echo ""

# Afficher les infos d'accès
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "✨ WordPress est prêt !"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo ""
echo "🌐 ACCÈS"
echo "  - Site : http://localhost:8080"
echo "  - Admin : http://localhost:8080/wp-admin"
echo "  - phpMyAdmin (Base de données) : http://localhost:8081"
echo ""
echo "🔐 IDENTIFIANTS"
echo "  - WordPress admin :"
echo "    • Identifiant : admin (à créer lors de l'installation)"
echo "    • Mot de passe : à créer lors de l'installation"
echo "  - MySQL :"
echo "    • Utilisateur : wordpress"
echo "    • Mot de passe : wordpress123"
echo "    • Base : wordpress"
echo ""
echo "📋 PLUGIN 404 ALERT"
if [ -d "wp-content/plugins/404-alert" ]; then
    echo "  ✅ Déjà copié et prêt"
    echo "  1. Aller à http://localhost:8080"
    echo "  2. Installer WordPress"
    echo "  3. Aller à Extensions > Plugins"
    echo "  4. Chercher '404 Alert' et l'activer"
else
    echo "  ⚠️  À copier manuellement :"
    echo "     cp -r /home/Baudouin/Documents/Projets/404-alert wp-content/plugins/"
fi
echo ""
echo "🛑 POUR ARRÊTER"
echo "  cd $WORK_DIR"
echo "  docker compose down"
echo ""
echo "🗑️  POUR TOUT SUPPRIMER (y compris les données)"
echo "  cd $WORK_DIR"
echo "  docker compose down -v"
echo ""
echo "📖 POUR VOIR LES LOGS"
echo "  cd $WORK_DIR"
echo "  docker compose logs -f wordpress"
echo ""
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo ""
echo "Ouvre maintenant : http://localhost:8080"
echo ""
