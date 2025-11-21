# Commandes à exécuter sur le serveur PROD

## 1. Se connecter au serveur
```bash
ssh civicdash@ns3153447.ip-51-210-35.eu
cd /opt/civicdash
```

## 2. Tester les migrations actuelles
```bash
docker compose exec -T app php artisan migrate
```

Si erreur sur `amendements_senat`, c'est normal, on vient de corriger.

## 3. Pull des dernières modifications
```bash
git pull origin main
```

## 4. Re-tester les migrations
```bash
docker compose exec -T app php artisan migrate
```

## 5. Diagnostic des colonnes DOSLEG (si erreur)
```bash
docker compose exec app php artisan tinker --execute="
\$columns = DB::select(\"SELECT column_name FROM information_schema.columns WHERE table_name = 'senat_dosleg_doc' ORDER BY ordinal_position LIMIT 30\");
echo 'Colonnes senat_dosleg_doc :\n';
foreach (\$columns as \$col) {
    echo '  - ' . \$col->column_name . '\n';
}
"
```

## 6. Vérifier les vues créées
```bash
docker compose exec app php artisan tinker --execute="
\$views = DB::select(\"SELECT table_name FROM information_schema.views WHERE table_schema = 'public' AND table_name LIKE '%senat%' ORDER BY table_name\");
echo 'Vues Sénat créées :\n';
foreach (\$views as \$v) {
    echo '  ✅ ' . \$v->table_name . '\n';
}
"
```

## 7. Compter les enregistrements
```bash
docker compose exec app php artisan tinker --execute="
echo 'Sénateurs : ' . DB::table('senateurs')->count() . '\n';
echo 'Mandats sénateurs : ' . DB::table('senateurs_mandats')->count() . '\n';
echo 'Commissions sénateurs : ' . DB::table('senateurs_commissions')->count() . '\n';
echo 'Historique groupes : ' . DB::table('senateurs_historique_groupes')->count() . '\n';
echo 'Votes sénateurs : ' . DB::table('senateurs_votes')->count() . '\n';
echo 'Scrutins Sénat : ' . DB::table('senateurs_scrutins')->count() . '\n';
echo 'Amendements Sénat : ' . DB::table('amendements_senat')->count() . '\n';
echo 'Dossiers Sénat : ' . DB::table('dossiers_legislatifs_senat')->count() . '\n';
"
```

## 8. Tester un sénateur complet
```bash
docker compose exec app php artisan tinker --execute="
\$sen = DB::table('senateurs')->first();
echo 'Test sénateur :\n';
echo '  Nom : ' . \$sen->nom_usuel . ' ' . \$sen->prenom_usuel . '\n';
echo '  Groupe : ' . \$sen->groupe_politique . '\n';
echo '  Commission : ' . \$sen->commission_permanente . '\n';
echo '  Email : ' . \$sen->email . '\n';
"
```

## 9. Si tout fonctionne, redémarrer PHP-FPM
```bash
sudo systemctl restart php8.2-fpm
sudo systemctl reload nginx
```

## 10. Vérifier sur le site
- https://demoscratos.fr/representants/senateurs
- Cliquer sur un sénateur pour voir son profil
- Vérifier que les données s'affichent correctement

---

## En cas d'erreur sur une vue

### Si `dossiers_legislatifs_senat` plante :
```bash
docker compose exec app php artisan tinker --execute="
\$columns = DB::select(\"SELECT column_name FROM information_schema.columns WHERE table_name = 'senat_dosleg_doc' ORDER BY ordinal_position\");
foreach (\$columns as \$col) {
    echo \$col->column_name . '\n';
}
"
```

Ensuite adapter la migration et renouveler le commit/push.

