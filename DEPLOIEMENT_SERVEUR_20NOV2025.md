# 🚀 COMMANDES SERVEUR - Déploiement 20 Nov 2025

## 📦 Étape 1 : Pull & Migrations

```bash
cd /opt/civicdash
git pull
php artisan migrate
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
docker compose restart app
```

---

## 🧪 Étape 2 : Tests Amendements AN

### Test 1 : Vérifier les stats globales
```bash
docker compose exec app php artisan tinker
```

Puis dans tinker :
```php
// Compter les amendements par sort_code
\App\Models\AmendementAN::where('sort_code', 'ADO')->count();  // Adoptés
\App\Models\AmendementAN::where('sort_code', 'REJ')->count();  // Rejetés
\App\Models\AmendementAN::where('sort_code', 'TOM')->count();  // Tombés
\App\Models\AmendementAN::where('sort_code', 'RET')->count();  // Retirés

// Vérifier le total
\App\Models\AmendementAN::count();

exit
```

### Test 2 : Vérifier les stats d'un député
```bash
docker compose exec app php artisan tinker
```

Puis dans tinker :
```php
// Prendre un député au hasard
$depute = \App\Models\ActeurAN::inRandomOrder()->first();
echo $depute->nom_complet . "\n";

// Ses amendements
$depute->amendementsAuteur()->count();
$depute->amendementsAuteur()->adoptes()->count();
$depute->amendementsAuteur()->rejetes()->count();

// Son taux d'adoption
$total = $depute->amendementsAuteur()->count();
$adoptes = $depute->amendementsAuteur()->adoptes()->count();
$taux = $total > 0 ? round(($adoptes / $total) * 100, 1) : 0;
echo "Taux adoption: {$taux}%\n";

exit
```

---

## 🔍 Étape 3 : Tests Recherche

### Test 1 : Recherche globale
```bash
# Recherche de députés
curl "http://localhost/api/search?q=Macron&types[]=deputes"

# Recherche de sénateurs
curl "http://localhost/api/search?q=Larcher&types[]=senateurs"

# Recherche d'amendements
curl "http://localhost/api/search?q=climat&types[]=amendements"

# Recherche globale
curl "http://localhost/api/search?q=écologie"
```

### Test 2 : Recherche codes postaux
```bash
# Par code postal
curl "http://localhost/api/representants/search?postal_code=75001"

# Par ville
curl "http://localhost/api/representants/search?q=Paris"

# Par code INSEE
curl "http://localhost/api/representants/search?insee_code=75101"
```

---

## 📊 Étape 4 : Import Amendements Sénat (Optionnel)

### Test d'abord avec une petite limite
```bash
docker compose exec app php artisan import:amendements-senat --legislature=2024 --limit=100
```

### Si le test fonctionne, import complet
```bash
docker compose exec app php artisan import:amendements-senat --legislature=2024 --fresh
```

### Vérifier les stats
```bash
docker compose exec app php artisan tinker
```

Puis dans tinker :
```php
\App\Models\AmendementSenat::count();
\App\Models\AmendementSenat::where('sort_code', 'ADOPTE')->count();
\App\Models\AmendementSenat::where('sort_code', 'REJETE')->count();

exit
```

---

## 🔧 Étape 5 : Rebuild Frontend (Si modifs Vue)

```bash
cd /opt/civicdash
npm run build
docker compose restart app
```

---

## ✅ Étape 6 : Vérification Finale

### Checklist à tester manuellement :
- [ ] **Page profil député** → Statistiques amendements affichées
- [ ] **Page /deputes/{uid}/amendements** → Liste des amendements avec badges colorés
- [ ] **Page /deputes/{uid}/activite** → Graphiques amendements corrects
- [ ] **Recherche globale** → Résultats pour députés, sénateurs, amendements
- [ ] **Recherche code postal** → Député + Sénateurs trouvés
- [ ] **Page /legislation/amendements/{uid}** → Détail amendement complet

### Exemples d'URLs à tester :
```
http://votre-domaine.com/representants/deputes
http://votre-domaine.com/representants/deputes/{uid}
http://votre-domaine.com/representants/deputes/{uid}/amendements
http://votre-domaine.com/representants/deputes/{uid}/activite
http://votre-domaine.com/api/search?q=climat
http://votre-domaine.com/api/representants/search?postal_code=75001
```

---

## 🐛 Dépannage

### Si les stats amendements sont toujours à 0 :
```bash
# Vérifier que les scopes fonctionnent
docker compose exec app php artisan tinker
>>> \App\Models\AmendementAN::adoptes()->count()
>>> exit

# Si toujours 0, réimporter les amendements
docker compose exec app php artisan import:amendements-an --legislature=17 --fresh
```

### Si la recherche ne retourne rien :
```bash
# Vérifier les colonnes des sénateurs
docker compose exec app php artisan tinker
>>> \App\Models\Senateur::first()->toArray()
>>> exit

# Vérifier les logs
tail -f storage/logs/laravel.log
```

### Si erreur "Undefined column" :
```bash
# Vider tous les caches
php artisan optimize:clear
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Redémarrer PHP-FPM
docker compose restart app
```

---

## 📞 Support

En cas de problème, vérifier :
1. Les logs Laravel : `storage/logs/laravel.log`
2. Les logs Docker : `docker compose logs app`
3. Les logs PostgreSQL : `docker compose logs db`

---

**Document créé le** : 20 novembre 2025, 23:50  
**Durée estimée** : 10-15 minutes  
**Niveau** : Intermédiaire

