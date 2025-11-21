# Commandes de déploiement - Fixes Sénateurs

## Sur le serveur :

```bash
cd /opt/civicdash

# 1. Supprimer les tables alias qui bloquent
docker compose exec -T app php artisan tinker --execute="
DB::statement('DROP TABLE IF EXISTS votes_senat CASCADE');
DB::statement('DROP TABLE IF EXISTS scrutins_senat CASCADE');
echo '✅ Tables alias supprimées\n';
"

# 2. Pull les modifications
git pull origin main

# 3. Relancer deploy complet
./deploy.sh

# 4. Vérifier les sénateurs
docker compose exec app php artisan tinker --execute="
echo 'Sénateurs actifs : ' . App\Models\Senateur::where('etat', 'ACTIF')->count() . '\n';
\$sen = App\Models\Senateur::where('etat', 'ACTIF')->first();
if (\$sen) {
    echo 'Exemple : ' . \$sen->nom_complet . '\n';
    echo 'Groupe : ' . \$sen->groupe_politique . '\n';
    echo 'Commission : ' . \$sen->commission_permanente . '\n';
}
"

# 5. Tester sur le site
# https://demoscratos.fr/representants/senateurs
```

## Modifications apportées :

1. ✅ **Correction vue `senateurs`** : Ajout colonne `id` mappée à `senmat`
2. ✅ **Correction model `Senateur`** : PK changée de `matricule` vers `id`
3. ✅ **Correction controller** : `photo_url` → `wikipedia_photo`, `profession` → `description_profession`
4. ✅ **Nettoyage** : Méthodes obsolètes commentées dans `RepresentantController`
5. ✅ **Menu** : Déjà correct (vérifié)

## Si ça ne marche toujours pas :

```bash
# Vider tous les caches
docker compose exec -T app php artisan cache:clear
docker compose exec -T app php artisan config:clear
docker compose exec -T app php artisan view:clear
docker compose exec -T app php artisan route:clear
sudo systemctl restart php8.2-fpm
sudo systemctl reload nginx

# Recompiler les assets
npm run build
```

