#!/bin/bash

# Script pour initialiser WordPress avec 404-alert et le Plugin Checker

set -e

echo "🚀 Démarrage de WordPress local avec 404-alert et Plugin Checker..."

# Vérifier que Docker est disponible
if ! command -v docker &> /dev/null; then
    echo "❌ Docker n'est pas installé. Veuillez installer Docker avant de continuer."
    exit 1
fi

# Démarrer les conteneurs
echo "📦 Démarrage des conteneurs Docker..."
docker compose up -d

# Attendre que WordPress soit prêt
echo "⏳ Attente du démarrage de WordPress..."
sleep 10

# Vérifier que WordPress est accessible
max_attempts=30
attempt=0
while [ $attempt -lt $max_attempts ]; do
    if curl -s http://localhost:8080 > /dev/null; then
        echo "✅ WordPress est accessible"
        break
    fi
    echo "⏳ Tentative $((attempt+1))/$max_attempts..."
    sleep 2
    ((attempt++))
done

if [ $attempt -eq $max_attempts ]; then
    echo "❌ WordPress n'a pas démarré correctement. Vérifiez les logs:"
    docker compose logs
    exit 1
fi

# Installer le Plugin Checker via WP-CLI
echo "📥 Installation du Plugin Checker..."
docker exec 404-alert-wordpress wp plugin install plugin-check --activate --allow-root 2>/dev/null || echo "⚠️  Plugin Checker non disponible en libre-service, vérifiez manuellement"

# Activer 404-alert
echo "🔌 Activation de 404-alert..."
docker exec 404-alert-wordpress wp plugin activate 404-alert --allow-root 2>/dev/null || echo "⚠️  404-alert non trouvé automatiquement"

# Afficher l'URL d'accès
echo ""
echo "✨ WordPress est maintenant accessible!"
echo "🌐 URL: http://localhost:8080"
echo ""
echo "📊 Identifiants WordPress (à configurer lors du premier accès):"
echo "   - Accédez à http://localhost:8080 pour configurer votre site"
echo ""
echo "🗄️  Base de données:"
echo "   - Host: mysql"
echo "   - User: wordpress"
echo "   - Password: wordpress123"
echo "   - Database: wordpress"
echo ""
echo "📋 Vérifier les logs:"
echo "   docker compose logs -f wordpress"
echo ""
echo "🛑 Arrêter l'environnement:"
echo "   docker compose down"
