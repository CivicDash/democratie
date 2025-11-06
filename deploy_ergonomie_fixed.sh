#!/bin/bash
# Script de déploiement complet - Ergonomie Topics + Fix Permissions
# À exécuter EN TANT QUE ROOT sur le serveur

set -e # Arrêter en cas d'erreur

echo "🚀 Déploiement CivicDash - Ergonomie Topics"
echo "============================================"
echo ""

cd /opt/civicdash

# 1. Git pull
echo "📥 1/8 - Git pull..."
git pull
echo "✅ Code mis à jour"
echo ""

# 2. Fix permissions logs (DÉFINITIF) - AVANT TOUT
echo "🔧 2/8 - Fix permissions logs..."
docker compose exec -u root app bash -c "rm -f /var/www/storage/logs/laravel.log && touch /var/www/storage/logs/laravel.log && chown www-data:www-data /var/www/storage/logs/laravel.log && chmod 664 /var/www/storage/logs/laravel.log"
echo "✅ Logs fixés"
echo ""

# 3. Fix permissions storage
echo "🔧 3/8 - Fix permissions storage..."
docker compose exec -u root app bash -c "chown -R www-data:www-data /var/www/storage && chmod -R 775 /var/www/storage && find /var/www/storage -type f -exec chmod 664 {} + && find /var/www/storage -type d -exec chmod 775 {} +"
echo "✅ Storage fixé"
echo ""

# 4. Fix permissions bootstrap/cache
echo "🔧 4/8 - Fix permissions bootstrap/cache..."
docker compose exec -u root app bash -c "chown -R www-data:www-data /var/www/bootstrap/cache && chmod -R 775 /var/www/bootstrap/cache"
echo "✅ Bootstrap/cache fixé"
echo ""

# 5. Clear caches Laravel
echo "🧹 5/8 - Clear caches..."
docker compose exec app php artisan config:clear 2>&1 | grep -v "UnexpectedValueException" | grep -v "telescope_entries" || true
docker compose exec app php artisan cache:clear 2>&1 | grep -v "UnexpectedValueException" | grep -v "telescope_entries" || true
docker compose exec app php artisan route:clear 2>&1 | grep -v "UnexpectedValueException" | grep -v "telescope_entries" || true
docker compose exec app php artisan view:clear 2>&1 | grep -v "UnexpectedValueException" | grep -v "telescope_entries" || true
echo "✅ Caches cleared"
echo ""

# 6. Rebuild frontend
echo "🎨 6/8 - Rebuild frontend (npm run build)..."
docker compose exec -u root app npm run build
echo "✅ Frontend rebuilt"
echo ""

# 7. Vérifier les codes postaux
echo "📮 7/8 - Vérification codes postaux..."
POSTAL_COUNT=$(docker compose exec app php artisan tinker --execute="use App\Models\FrenchPostalCode; echo FrenchPostalCode::count();" 2>/dev/null | tail -1 | tr -d '\r\n' || echo "0")
echo "   Codes postaux en base : $POSTAL_COUNT"
if [ "$POSTAL_COUNT" -lt "1000" ]; then
    echo "   ⚠️  Peu de codes postaux, import peut-être en cours..."
    echo "   Pour vérifier : docker compose logs app | grep -i postal"
fi
echo ""

# 8. Redémarrer les services
echo "🔄 8/8 - Redémarrage services..."
docker compose restart app nginx queue
echo "✅ Services redémarrés"
echo ""

echo "============================================"
echo "✅ Déploiement terminé !"
echo ""
echo "🧪 Tests à faire :"
echo "   1. Aller sur un topic/débat"
echo "   2. Vérifier que le formulaire est en haut"
echo "   3. Ajouter une réponse"
echo "   4. Cliquer sur 'Répondre' sur un commentaire"
echo "   5. Voter sur des commentaires"
echo "   6. Vérifier le scrutin associé (si présent)"
echo ""
echo "📊 Logs en temps réel :"
echo "   docker compose logs -f app"
