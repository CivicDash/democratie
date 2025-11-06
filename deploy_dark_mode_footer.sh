#!/bin/bash
# Déploiement Dark Mode + Footer

echo "🚀 Déploiement Dark Mode & Footer"
echo "=================================="
echo ""

cd /opt/civicdash

# 1. Git pull
echo "📥 1/4 - Git pull..."
git pull
echo "✅ Code mis à jour"
echo ""

# 2. Rebuild frontend
echo "🎨 2/4 - Rebuild frontend..."
docker compose exec -u root app npm run build
echo "✅ Frontend rebuilt"
echo ""

# 3. Clear caches
echo "🧹 3/4 - Clear caches..."
docker compose exec app php artisan config:clear 2>&1 | grep "INFO" || true
docker compose exec app php artisan view:clear 2>&1 | grep "INFO" || true
echo "✅ Caches cleared"
echo ""

# 4. Redémarrer
echo "🔄 4/4 - Redémarrage..."
docker compose restart app nginx
echo "✅ Services redémarrés"
echo ""

echo "=================================="
echo "✅ Déploiement terminé !"
echo ""
echo "🧪 Tests à faire :"
echo "   1. Clique sur le bouton soleil/lune dans le header"
echo "   2. Vérifie que le mode change instantanément"
echo "   3. Scroll en bas pour voir le footer"
echo "   4. Clique sur les liens du footer"
echo ""
echo "📝 Fonctionnalités ajoutées :"
echo "   • 🌓 Switch Dark/Light mode (header desktop + mobile)"
echo "   • 🦶 Footer complet avec liens vers objectif2027.fr et civis-consilium.fr"
echo "   • 🔗 Liens GitHub, Discord, Documentation, Roadmap"
echo "   • 🎨 Design responsive et dark mode compatible"
