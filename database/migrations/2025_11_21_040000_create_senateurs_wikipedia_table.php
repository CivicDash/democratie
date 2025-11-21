<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Créer la table annexe pour stocker les données Wikipedia
        Schema::create('senateurs_wikipedia', function (Blueprint $table) {
            $table->string('senateur_matricule', 20)->primary();
            $table->string('wikipedia_url', 500)->nullable();
            $table->string('photo_wikipedia_url', 500)->nullable();
            $table->text('wikipedia_extract')->nullable();
            $table->timestamp('wikipedia_last_sync')->nullable();
            $table->timestamps();
            
            // Foreign key vers la table raw (optionnel, car c'est une vue)
            // $table->foreign('senateur_matricule')->references('senmat')->on('senat_senateurs_sen')->onDelete('cascade');
        });

        // 2. Recréer la vue 'senateurs' en incluant les données Wikipedia
        DB::statement("DROP VIEW IF EXISTS senateurs");
        
        DB::statement("
            CREATE VIEW senateurs AS
            SELECT 
                -- Identifiant
                sen.senmat AS id,
                sen.senmat AS matricule,
                
                -- Civilité
                qua.qualib AS civilite,
                
                -- Identité
                sen.sennomuse AS nom_usuel,
                sen.senprenomuse AS prenom_usuel,
                sen.sennompatnai AS nom_patronymique_naissance,
                sen.sennomuse || ' ' || sen.senprenomuse AS nom_complet,
                
                -- Profil
                sen.sendaiurl AS url_profil,
                
                -- État (déjà transformé dans la table)
                sen.etasencod AS etat,
                
                -- Dates
                sen.sendatnai::date AS date_naissance,
                sen.sendatdec::date AS date_deces,
                
                -- Groupe politique actuel
                COALESCE(sen.sengrppolliccou, 'Non inscrit') AS groupe_politique,
                sen.sengrppolcodcou AS groupe_politique_code,
                sen.sentypappcou AS type_appartenance_groupe,
                
                -- Commission permanente actuelle
                sen.sencomliccou AS commission_permanente,
                sen.sencomcodcou AS commission_permanente_code,
                
                -- Circonscription
                LPAD(sen.sencirnumcou::text, 2, '0') AS departement_code,
                sen.sencircou AS circonscription,
                
                -- Fonction au bureau du Sénat
                sen.senburliccou AS fonction_bureau_senat,
                
                -- Contact
                sen.senema AS email,
                
                -- Profession
                sen.pcscod AS pcs_insee,
                sen.catprocod AS categorie_socio_pro,
                sen.sendespro AS description_profession,
                
                -- Champs Wikipedia (depuis la table annexe)
                wiki.wikipedia_url,
                wiki.photo_wikipedia_url,
                wiki.wikipedia_extract,
                wiki.wikipedia_last_sync,
                
                -- Champs Laravel
                COALESCE(sen.syscredat, NOW()) AS created_at,
                COALESCE(sen.sysmajdat, NOW()) AS updated_at
                
            FROM senat_senateurs_sen sen
            LEFT JOIN senat_senateurs_qua qua ON sen.quacod = qua.quacod
            LEFT JOIN senateurs_wikipedia wiki ON sen.senmat = wiki.senateur_matricule
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Recréer la vue sans Wikipedia
        DB::statement("DROP VIEW IF EXISTS senateurs");
        
        DB::statement("
            CREATE VIEW senateurs AS
            SELECT 
                sen.senmat AS id,
                sen.senmat AS matricule,
                qua.qualib AS civilite,
                sen.sennomuse AS nom_usuel,
                sen.senprenomuse AS prenom_usuel,
                sen.sennompatnai AS nom_patronymique_naissance,
                sen.sennomuse || ' ' || sen.senprenomuse AS nom_complet,
                sen.sendaiurl AS url_profil,
                sen.etasencod AS etat,
                sen.sendatnai::date AS date_naissance,
                sen.sendatdec::date AS date_deces,
                COALESCE(sen.sengrppolliccou, 'Non inscrit') AS groupe_politique,
                sen.sengrppolcodcou AS groupe_politique_code,
                sen.sentypappcou AS type_appartenance_groupe,
                sen.sencomliccou AS commission_permanente,
                sen.sencomcodcou AS commission_permanente_code,
                LPAD(sen.sencirnumcou::text, 2, '0') AS departement_code,
                sen.sencircou AS circonscription,
                sen.senburliccou AS fonction_bureau_senat,
                sen.senema AS email,
                sen.pcscod AS pcs_insee,
                sen.catprocod AS categorie_socio_pro,
                sen.sendespro AS description_profession,
                NULL::text AS wikipedia_url,
                NULL::text AS photo_wikipedia_url,
                NULL::text AS wikipedia_extract,
                NULL::timestamp AS wikipedia_last_sync,
                COALESCE(sen.syscredat, NOW()) AS created_at,
                COALESCE(sen.sysmajdat, NOW()) AS updated_at
            FROM senat_senateurs_sen sen
            LEFT JOIN senat_senateurs_qua qua ON sen.quacod = qua.quacod
        ");
        
        // Supprimer la table annexe
        Schema::dropIfExists('senateurs_wikipedia');
    }
};

