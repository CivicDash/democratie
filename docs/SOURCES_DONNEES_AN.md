# 📊 Sources de Données - Assemblée Nationale

**Documentation des sources Open Data de l'Assemblée Nationale française**

> Site officiel : https://data.assemblee-nationale.fr/

---

## 🔗 URLs des Sources XML

### Structure des URLs

```
http://data.assemblee-nationale.fr/static/openData/repository/{LEGISLATURE}/{CATEGORIE}/{SOUS_CATEGORIE}/{FICHIER}.xml.zip
```

**Variable `{LEGISLATURE}`** : `16`, `17`, `18`... (numéro de la législature)

---

## 📁 Sources Principales

### 1. Députés et Acteurs

| Source | URL | Description |
|--------|-----|-------------|
| **Députés actifs** | `http://data.assemblee-nationale.fr/static/openData/repository/{LEG}/amo/deputes_actifs_mandats_actifs_organes/AMO10_deputes_actifs_mandats_actifs_organes.xml.zip` | Députés en exercice avec mandats et organes |
| **Tous acteurs historique** | `http://data.assemblee-nationale.fr/static/openData/repository/{LEG}/amo/tous_acteurs_mandats_organes_xi_legislature/AMO30_tous_acteurs_tous_mandats_tous_organes_historique.xml.zip` | Historique complet de tous les acteurs |

### 2. Activité Législative

| Source | URL | Description |
|--------|-----|-------------|
| **Scrutins** | `http://data.assemblee-nationale.fr/static/openData/repository/{LEG}/loi/scrutins/Scrutins.xml.zip` | Votes publics et résultats |
| **Amendements** | `http://data.assemblee-nationale.fr/static/openData/repository/{LEG}/loi/amendements_div_legis/Amendements.xml.zip` | Tous les amendements déposés |
| **Dossiers législatifs** | `http://data.assemblee-nationale.fr/static/openData/repository/{LEG}/loi/dossiers_legislatifs/Dossiers_Legislatifs.xml.zip` | Dossiers et textes de loi |

### 3. Travaux Parlementaires

| Source | URL | Description |
|--------|-----|-------------|
| **Agenda/Réunions** | `http://data.assemblee-nationale.fr/static/openData/repository/{LEG}/vp/reunions/Agenda.xml.zip` | Calendrier des réunions |
| **Syceron (brut)** | `http://data.assemblee-nationale.fr/static/openData/repository/{LEG}/VP/syceronbrut/syseron.xml.zip` | Données brutes du système Syceron |

### 4. Questions au Gouvernement

| Source | URL | Description |
|--------|-----|-------------|
| **Questions gouvernement** | `http://data.assemblee-nationale.fr/static/openData/repository/{LEG}/questions/questions_gouvernement/Questions_gouvernement.xml.zip` | Questions orales et écrites |

---

## 🏗️ Structure des Fichiers XML

### Scrutins (Scrutins.xml.zip)

```xml
<?xml version="1.0" encoding="UTF-8"?>
<scrutins>
  <scrutin>
    <uid>VTANR5L17V3876</uid>
    <numero>3876</numero>
    <organeRef>PO730964</organeRef>
    <legislature>17</legislature>
    <sessionRef>SCR2024-2025</sessionRef>
    <dateScrutin>2024-11-17</dateScrutin>
    <titre>Motion de rejet préalable...</titre>
    <demandeur>
      <texte>Mme Mathilde Panot</texte>
    </demandeur>
    <syntheseVote>
      <nombreVotants>304</nombreVotants>
      <suffragesExprimes>304</suffragesExprimes>
      <nlesAbstentions>88</nlesAbstentions>
      <pour>47</pour>
      <contre>169</contre>
    </syntheseVote>
    <ventilationVotes>
      <organe>
        <organeRef>PO830170</organeRef>
        <nombreMembresGroupe>125</nombreMembresGroupe>
        <vote>
          <positionMajoritaire>contre</positionMajoritaire>
          <decompte>
            <pour>0</pour>
            <contre>95</contre>
            <abstentions>2</abstentions>
          </decompte>
        </vote>
      </organe>
      <!-- ... autres groupes ... -->
    </ventilationVotes>
  </scrutin>
</scrutins>
```

### Députés (AMO10.xml.zip)

```xml
<?xml version="1.0" encoding="UTF-8"?>
<export>
  <acteurs>
    <acteur>
      <uid>PA123456</uid>
      <etatCivil>
        <ident>
          <civ>M.</civ>
          <prenom>Jean</prenom>
          <nom>DUPONT</nom>
        </ident>
        <infoNaissance>
          <dateNais>1970-05-15</dateNais>
          <villeNais>Paris</villeNais>
          <depNais>75</depNais>
        </infoNaissance>
      </etatCivil>
      <profession>
        <libelleCourant>Avocat</libelleCourant>
        <socProcINSEE>
          <catSocPro>34</catSocPro>
        </socProcINSEE>
      </profession>
      <mandats>
        <mandat>
          <uid>PM123456</uid>
          <typeOrgane>ASSEMBLEE</typeOrgane>
          <legislature>17</legislature>
          <dateDebut>2024-07-18</dateDebut>
          <dateFin xsi:nil="true"/>
          <election>
            <lieu>
              <numDepartement>75</numDepartement>
              <numCirco>1</numCirco>
            </lieu>
          </election>
        </mandat>
      </mandats>
    </acteur>
  </acteurs>
  <organes>
    <organe>
      <uid>PO830170</uid>
      <codeType>GP</codeType>
      <libelle>Renaissance</libelle>
      <libelleAbrege>RE</libelleAbrege>
      <couleurAssociee>#FFD700</couleurAssociee>
    </organe>
  </organes>
</export>
```

### Amendements (Amendements.xml.zip)

```xml
<?xml version="1.0" encoding="UTF-8"?>
<amendements>
  <amendement>
    <uid>AMANR5L17PO123456B0001</uid>
    <identifiant>
      <numero>123</numero>
      <legislature>17</legislature>
    </identifiant>
    <texteLegislatifRef>PRJLANR5L17B0001</texteLegislatifRef>
    <signataires>
      <auteur>
        <acteurRef>PA123456</acteurRef>
        <qualite>auteur</qualite>
      </auteur>
    </signataires>
    <corps>
      <dispositif>Après l'article 1, insérer...</dispositif>
      <exposeSommaire>Cet amendement vise à...</exposeSommaire>
    </corps>
    <sort>
      <dateSort>2024-11-15</dateSort>
      <sortEnSeance>Adopté</sortEnSeance>
    </sort>
  </amendement>
</amendements>
```

---

## 🔄 Stratégie d'Import Automatique

### Architecture Proposée

```
app/
├── Console/Commands/
│   ├── Import/
│   │   ├── BaseXmlImportCommand.php      # Classe abstraite
│   │   ├── DownloadAnDataCommand.php     # Téléchargement ZIP
│   │   ├── ImportScrutinsXmlCommand.php  # Import scrutins
│   │   ├── ImportDeputesXmlCommand.php   # Import députés
│   │   ├── ImportAmendementsXmlCommand.php
│   │   └── ImportDossiersXmlCommand.php
│   └── Sync/
│       └── SyncAssembleeNationaleCommand.php  # Orchestrateur
├── Services/
│   └── AssembleeNationale/
│       ├── XmlDownloader.php             # Téléchargement + décompression
│       ├── XmlParser.php                 # Parsing XML
│       └── DataSynchronizer.php          # Logique de sync
└── config/
    └── assemblee-nationale.php           # Configuration des URLs
```

### Configuration (config/assemblee-nationale.php)

```php
<?php

return [
    'legislature' => env('AN_LEGISLATURE', 17),
    
    'base_url' => 'http://data.assemblee-nationale.fr/static/openData/repository',
    
    'sources' => [
        'deputes_actifs' => [
            'path' => '{legislature}/amo/deputes_actifs_mandats_actifs_organes/AMO10_deputes_actifs_mandats_actifs_organes.xml.zip',
            'model' => \App\Models\ActeurAN::class,
            'parser' => 'deputes',
        ],
        'tous_acteurs' => [
            'path' => '{legislature}/amo/tous_acteurs_mandats_organes_xi_legislature/AMO30_tous_acteurs_tous_mandats_tous_organes_historique.xml.zip',
            'model' => \App\Models\ActeurAN::class,
            'parser' => 'acteurs',
        ],
        'scrutins' => [
            'path' => '{legislature}/loi/scrutins/Scrutins.xml.zip',
            'model' => \App\Models\ScrutinAN::class,
            'parser' => 'scrutins',
        ],
        'amendements' => [
            'path' => '{legislature}/loi/amendements_div_legis/Amendements.xml.zip',
            'model' => \App\Models\AmendementAN::class,
            'parser' => 'amendements',
        ],
        'dossiers' => [
            'path' => '{legislature}/loi/dossiers_legislatifs/Dossiers_Legislatifs.xml.zip',
            'model' => \App\Models\DossierLegislatifAN::class,
            'parser' => 'dossiers',
        ],
        'reunions' => [
            'path' => '{legislature}/vp/reunions/Agenda.xml.zip',
            'model' => \App\Models\ReunionAN::class,
            'parser' => 'reunions',
        ],
        'questions' => [
            'path' => '{legislature}/questions/questions_gouvernement/Questions_gouvernement.xml.zip',
            'model' => \App\Models\QuestionAN::class,
            'parser' => 'questions',
        ],
    ],
    
    'storage_path' => storage_path('app/an-data'),
    
    'cache_duration' => 3600, // 1 heure
];
```

---

## 📋 Commandes Artisan Proposées

### Téléchargement

```bash
# Télécharger toutes les sources pour la législature 17
php artisan an:download --legislature=17

# Télécharger une source spécifique
php artisan an:download scrutins --legislature=17

# Télécharger avec force (même si cache valide)
php artisan an:download --force
```

### Import

```bash
# Synchroniser toutes les données
php artisan an:sync --legislature=17

# Importer une source spécifique
php artisan an:import scrutins --legislature=17

# Mode incrémental (seulement les nouveaux)
php artisan an:sync --incremental

# Mode complet (tout réimporter)
php artisan an:sync --fresh
```

### Planification (Scheduler)

```php
// app/Console/Kernel.php
protected function schedule(Schedule $schedule): void
{
    // Synchronisation quotidienne à 3h du matin
    $schedule->command('an:sync --incremental')
        ->dailyAt('03:00')
        ->withoutOverlapping()
        ->appendOutputTo(storage_path('logs/an-sync.log'));
    
    // Synchronisation complète hebdomadaire
    $schedule->command('an:sync --fresh')
        ->weekly()
        ->sundays()
        ->at('04:00');
}
```

---

## 🔮 Consultations Citoyennes

> Source : https://data.assemblee-nationale.fr/autres/consultations-citoyennes

### Description

Les consultations citoyennes permettent aux citoyens de participer au processus législatif en :
- Donnant leur avis sur des projets de loi
- Proposant des amendements citoyens
- Votant sur des propositions

### Sources de Données

| Source | URL | Description |
|--------|-----|-------------|
| **Consultations** | `https://data.assemblee-nationale.fr/static/openData/repository/autres/consultations/consultations.json` | Liste des consultations |
| **Contributions** | `https://data.assemblee-nationale.fr/static/openData/repository/autres/consultations/{ID}/contributions.json` | Contributions par consultation |

### Structure JSON (Consultations)

```json
{
  "consultations": [
    {
      "uid": "CONS123",
      "titre": "Consultation sur le projet de loi X",
      "description": "Description de la consultation...",
      "dossierLegislatifRef": "DLR5L17N12345",
      "dateDebut": "2024-01-15",
      "dateFin": "2024-02-15",
      "statut": "TERMINEE",
      "nbContributions": 1234,
      "nbVotes": 5678,
      "thematiques": ["environnement", "energie"]
    }
  ]
}
```

### Intégration Proposée

```php
// Migration
Schema::create('consultations_citoyennes', function (Blueprint $table) {
    $table->string('uid')->primary();
    $table->string('titre');
    $table->text('description')->nullable();
    $table->string('dossier_legislatif_ref')->nullable();
    $table->date('date_debut');
    $table->date('date_fin')->nullable();
    $table->string('statut'); // OUVERTE, TERMINEE, ANNULEE
    $table->integer('nb_contributions')->default(0);
    $table->integer('nb_votes')->default(0);
    $table->jsonb('thematiques')->nullable();
    $table->timestamps();
});

Schema::create('contributions_citoyennes', function (Blueprint $table) {
    $table->id();
    $table->string('consultation_uid');
    $table->string('auteur_pseudo')->nullable();
    $table->text('contenu');
    $table->string('article_ref')->nullable();
    $table->integer('votes_pour')->default(0);
    $table->integer('votes_contre')->default(0);
    $table->string('statut'); // PUBLIEE, MODEREE, REJETEE
    $table->timestamp('date_publication');
    $table->timestamps();
    
    $table->foreign('consultation_uid')
        ->references('uid')
        ->on('consultations_citoyennes');
});

// Modèle
class ConsultationCitoyenne extends Model
{
    protected $primaryKey = 'uid';
    public $incrementing = false;
    protected $keyType = 'string';
    
    protected $fillable = [
        'uid', 'titre', 'description', 'dossier_legislatif_ref',
        'date_debut', 'date_fin', 'statut',
        'nb_contributions', 'nb_votes', 'thematiques',
    ];

    protected $casts = [
        'date_debut' => 'date',
        'date_fin' => 'date',
        'thematiques' => 'array',
    ];

    public function contributions()
    {
        return $this->hasMany(ContributionCitoyenne::class, 'consultation_uid', 'uid');
    }

    public function dossierLegislatif()
    {
        return $this->belongsTo(DossierLegislatifAN::class, 'dossier_legislatif_ref', 'uid');
    }
}

class ContributionCitoyenne extends Model
{
    protected $fillable = [
        'consultation_uid', 'auteur_pseudo', 'contenu',
        'article_ref', 'votes_pour', 'votes_contre',
        'statut', 'date_publication',
    ];

    public function consultation()
    {
        return $this->belongsTo(ConsultationCitoyenne::class, 'consultation_uid', 'uid');
    }
    
    public function getTauxApprobationAttribute(): float
    {
        $total = $this->votes_pour + $this->votes_contre;
        return $total > 0 ? round(($this->votes_pour / $total) * 100, 1) : 0;
    }
}
```

### Cas d'Usage

1. **Page dédiée** : Liste des consultations ouvertes/terminées
2. **Intégration dossiers** : Lien vers la consultation sur la page du dossier législatif
3. **Statistiques** : Taux de participation, thématiques populaires
4. **Alertes** : Notification des nouvelles consultations ouvertes

---

## 📊 Volumétrie Estimée

| Source | Fichiers | Taille ZIP | Taille décompressée |
|--------|----------|------------|---------------------|
| Scrutins L17 | ~4000 | ~15 Mo | ~80 Mo |
| Amendements L17 | ~50000 | ~100 Mo | ~500 Mo |
| Députés actifs | ~580 | ~2 Mo | ~10 Mo |
| Tous acteurs | ~5000 | ~20 Mo | ~100 Mo |
| Dossiers | ~1000 | ~10 Mo | ~50 Mo |

**Total estimé** : ~150 Mo ZIP, ~750 Mo décompressé

---

## 🚀 Plan d'Implémentation

### Phase 1 : Infrastructure (1-2 jours)
- [ ] Créer `config/assemblee-nationale.php`
- [ ] Créer service `XmlDownloader`
- [ ] Créer commande `an:download`
- [ ] Tests de téléchargement

### Phase 2 : Parsers XML (2-3 jours)
- [ ] Parser générique XML
- [ ] Parser scrutins
- [ ] Parser députés/acteurs
- [ ] Parser amendements

### Phase 3 : Import (2-3 jours)
- [ ] Commande `an:import`
- [ ] Gestion incrémentale
- [ ] Logs et monitoring
- [ ] Gestion des erreurs

### Phase 4 : Orchestration (1 jour)
- [ ] Commande `an:sync`
- [ ] Planification scheduler
- [ ] Notifications

### Phase 5 : Consultations Citoyennes (2-3 jours)
- [ ] Analyse du format
- [ ] Modèles et migrations
- [ ] Import et affichage

---

## 📝 Notes Techniques

### Différences XML vs JSON

| Aspect | JSON (actuel) | XML (proposé) |
|--------|---------------|---------------|
| Format | Fichiers individuels | Archive ZIP unique |
| Taille | ~1 Ko/fichier | ~15 Mo/archive |
| Parsing | `json_decode()` | `SimpleXML` / `XMLReader` |
| Mémoire | Faible | Attention aux gros fichiers |
| Mise à jour | Manuelle | Automatique via URL |

### Recommandations

1. **Utiliser XMLReader** pour les gros fichiers (amendements) plutôt que SimpleXML
2. **Chunking** : Traiter par lots de 1000 éléments
3. **Cache** : Stocker le hash du fichier pour éviter les re-téléchargements inutiles
4. **Queue** : Utiliser les jobs Laravel pour les imports longs

---

## 🎬 Portail Video AN (videos.assemblee-nationale.fr)

> Recherche effectuee le 2026-02-18 via `php artisan an:research-video`

### Architecture

Le portail video utilise la plateforme **Vodalys** :
- **Hebergement video** : `anorigin.vodalys.com` (CDN via Akamai en backup)
- **Streaming** : HLS (M3U8), RTMP via `vipwowza.yacast.net`, MP4 direct
- **Player** : RequireJS + modules Vodalys proprietaires
- **Synchro** : Socket.IO v0.9 via `node-event.assemblee-nationale.fr`

### Endpoint `data.nvs` (XML)

**URL** : `https://videos.assemblee-nationale.fr/Datas/an/{video_id}/content/data.nvs`

Le fichier `data.nvs` est un XML riche accessible publiquement pour chaque video. Il contient :

| Donnee | Description | Exemple |
|--------|-------------|---------|
| **Chapitres** | Hierarchie complete avec type (Question, Reponse, Article, Adt...) et themes | `<chapter id="..." label="Pesticides"><theme key="1" value="Agriculture"/>` |
| **Orateurs** | Noms + UID AN (sans prefixe PA) | `<speaker id="..."><name>M. Jean-Noel Barrot</name><url>721836</url>` |
| **Fichiers video** | URLs MP4, M3U8, RTMP, MP3 audio | `serverfiles://..., http://anorigin.vodalys.com/...` |
| **Metadonnees** | Date, legislature, session, type (QG/SP), lieu | `<metadata name="video_type" value="QG"/>` |
| **Thumbnails** | Storyboard toutes les 60 secondes | `<thumbnail url="./files/storyboard/120.jpg"/>` |

### Format du video_id

`{numeric_id}_{hash}` extrait de l'URL : `video.18339196_69960cf6d5a3c.slug-titre`

### Types de chapitres (QAG)

| Type key | Type | Usage |
|----------|------|-------|
| 36 | QUESTIONS AU GOUVERNEMENT | Racine |
| 5 | Titre question | Theme de la question |
| 4 | Question | Intervention du depute |
| 6 | Reponse | Intervention du ministre |
| 48 | Presidente de l'Assemblee | Annonces presidentielles |
| 40 | HOMMAGE | Hommage |

### Types de chapitres (Seance publique)

| Type key | Type | Usage |
|----------|------|-------|
| 23 | Texte | Racine du texte en discussion |
| 1 | Discussion des articles | Section discussion |
| 2 | Article | Numero d'article |
| 25 | Adt | Amendement(s) |
| 7 | Intervention | Prise de parole |
| 42 | President de seance | Annonces |

### Correspondance UID Orateurs

Le champ `<url>` dans `<speaker>` contient l'UID AN **sans prefixe PA**.
Exemple : `<url>721836</url>` → `PA721836` → correspond a `acteurs_an.uid = 'PA721836'`

### Timecodes (synchros)

Les timecodes (mapping chapitre → seconde video) sont servis via **Socket.IO v0.9** :
- Serveur : `http://node-event.assemblee-nationale.fr`
- Protocole : `xhr-polling`, namespace `/vodalys_nodejs_interface`
- Subscription : event `subscribe` avec `interface_uid` depuis data.nvs
- **Statut** : Les synchros n'etaient pas disponibles pour les videos du 18/02/2026 (possiblement ajoutees avec un delai apres la seance)

### URLs timecodes

Le portail supporte les URLs timecodes :
```
https://videos.assemblee-nationale.fr/video.{video_id}.{slug}?timecode={secondes}
```

### Commande de recherche

```bash
# Analyse complete d'une page video (JS, endpoints, SRT)
php artisan an:research-video "https://videos.assemblee-nationale.fr/video.{id}"

# Parser les data.nvs (chapitres, orateurs, metadonnees)
php artisan an:research-video --fetch-nvs

# Avec une URL specifique
php artisan an:research-video "https://..." --fetch-nvs
```

---

## 🔗 Ressources

- [Documentation officielle AN](https://data.assemblee-nationale.fr/static/openData/doc/doc_opendata.pdf)
- [API REST AN](https://www.assemblee-nationale.fr/dyn/opendata/index)
- [Format des UIDs](https://data.assemblee-nationale.fr/static/openData/doc/format_uid.pdf)

