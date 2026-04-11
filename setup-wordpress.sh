#!/bin/bash

# Script pour configurer WordPress avec WP-CLI après son démarrage

set -e

echo "📋 Configuration de WordPress..."

# Télécharger WP-CLI dans le conteneur
echo "📥 Installation de WP-CLI..."
docker exec 404-alert-wordpress bash -c 'curl -o /usr/local/bin/wp https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar && chmod +x /usr/local/bin/wp' 2>/dev/null || echo "⚠️  WP-CLI déjà installé"

# Vérifier si WordPress est déjà installé
echo "🔍 Vérification du statut WordPress..."
if ! docker exec 404-alert-wordpress wp --allow-root option get home > /dev/null 2>&1; then
    echo "🔧 Installation de WordPress..."
    docker exec 404-alert-wordpress wp --allow-root core install \
        --url=http://localhost:8082 \
        --title='404-Alert Test Site' \
        --admin_user=admin \
        --admin_password=admin \
        --admin_email=admin@example.com
else
    echo "✅ WordPress est déjà installé"
fi

# Activer le plugin 404-alert
echo "🔌 Activation du plugin 404-alert..."
docker exec 404-alert-wordpress wp --allow-root plugin activate 404-alert 2>/dev/null || echo "⚠️  404-alert non trouvé ou déjà actif"

# Installer Plugin Checker
echo "📦 Installation du plugin WordPress Plugin Checker..."
if docker exec 404-alert-wordpress wp --allow-root plugin install plugin-check 2>/dev/null; then
    echo "✅ Plugin Checker installé"
    docker exec 404-alert-wordpress wp --allow-root plugin activate plugin-check 2>/dev/null || echo "⚠️  Plugin Checker installé mais non activé"
else
    echo "⚠️  Impossible d'installer Plugin Checker (peut ne pas être disponible en libre-service)"
fi

# Afficher les informations d'accès
echo ""
echo "════════════════════════════════════════════════════════════"
echo "✨ WordPress est maintenant prêt!"
echo "════════════════════════════════════════════════════════════"
echo ""
echo "🌐 WordPress URL: http://localhost:8082"
echo ""
echo "👤 Identifiants de connexion:"
echo "   Username: admin"
echo "   Password: admin"
echo ""
echo "📂 Plugins installés:"
echo "   - 404-Alert (plugin principal)"
echo "   - Plugin Checker (vérification de compatibilité WordPress)"
echo ""
echo "📊 Base de données:"
echo "   - Host: mysql (ou localhost:3308)"
echo "   - User: wordpress"
echo "   - Password: wordpress123"
echo "   - Database: wordpress"
echo ""
echo "📋 Commandes utiles:"
echo "   - Voir les logs: docker compose logs -f wordpress"
echo "   - Exécuter WP-CLI: docker exec 404-alert-wordpress wp [commande] --allow-root"
echo "   - Arrêter: docker compose down"
echo "   - Redémarrer: docker compose restart"
echo ""
echo "════════════════════════════════════════════════════════════"
