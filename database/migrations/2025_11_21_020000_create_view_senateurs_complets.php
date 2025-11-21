<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Vue pour les sénateurs complets
     * Map les tables SQL natives vers une structure compatible avec notre modèle Senateur
     */
    public function up(): void
    {
        DB::statement("
            CREATE OR REPLACE VIEW v_senateurs_complets AS
            SELECT 
                -- Identité
                sen.id::text AS matricule,
                CASE 
                    WHEN sen.civilite = 'M.' THEN 'M.'
                    WHEN sen.civilite = 'Mme' THEN 'Mme'
                    ELSE sen.civilite
                END AS civilite,
                COALESCE(sennom.nom, '') AS nom_usuel,
                COALESCE(sennom.prenom, '') AS prenom_usuel,
                
                -- État
                CASE 
                    WHEN sen.etat = 'ACTIF' THEN 'ACTIF'::text
                    ELSE 'ANCIEN'::text
                END AS etat,
                
                -- Dates
                sen.sendatnai::date AS date_naissance,
                sen.sendatdec::date AS date_deces,
                
                -- Groupe politique actuel (sous-requête)
                (
                    SELECT libgrp.libelle
                    FROM memgrpsen msg
                    JOIN grpsenami grp ON msg.groupe_id = grp.id
                    LEFT JOIN libgrpsen libgrp ON grp.id = libgrp.groupe_id 
                        AND libgrp.libgrpsendatfin IS NULL
                    WHERE msg.senateur_id = sen.id
                    AND msg.memgrpsendatsor IS NULL
                    ORDER BY msg.memgrpsendatent DESC
                    LIMIT 1
                ) AS groupe_politique,
                
                -- Type appartenance groupe
                (
                    SELECT CASE 
                        WHEN msg.type_appartenance = 'M' THEN 'Membre'
                        WHEN msg.type_appartenance = 'R' THEN 'Rattaché'
                        ELSE msg.type_appartenance
                    END
                    FROM memgrpsen msg
                    WHERE msg.senateur_id = sen.id
                    AND msg.memgrpsendatsor IS NULL
                    ORDER BY msg.memgrpsendatent DESC
                    LIMIT 1
                ) AS type_appartenance_groupe,
                
                -- Commission permanente actuelle
                (
                    SELECT libcom.libelle
                    FROM memcom mc
                    JOIN com ON mc.commission_id = com.id
                    LEFT JOIN libcom ON com.id = libcom.commission_id
                        AND libcom.libcomdatfin IS NULL
                    WHERE mc.senateur_id = sen.id
                    AND mc.memcomdatfin IS NULL
                    AND com.typorg = 'COMPER'  -- Commission permanente
                    ORDER BY mc.memcomdatdeb DESC
                    LIMIT 1
                ) AS commission_permanente,
                
                -- Circonscription (département pour les sénateurs)
                (
                    SELECT dpt.libelle
                    FROM elusen es
                    JOIN dpt ON es.departement_id = dpt.id
                    WHERE es.senateur_id = sen.id
                    AND es.eludatfin IS NULL
                    ORDER BY es.eludatdeb DESC
                    LIMIT 1
                ) AS circonscription,
                
                -- Fonction au bureau du Sénat
                (
                    SELECT fonbur.libelle
                    FROM senbur sb
                    JOIN bur ON sb.fonction_id = bur.id
                    WHERE sb.senateur_id = sen.id
                    AND sb.senburdatfin IS NULL
                    ORDER BY sb.senburdatdeb DESC
                    LIMIT 1
                ) AS fonction_bureau_senat,
                
                -- Email (si disponible dans la base)
                mel.email AS email,
                
                -- Profession
                pcs.code AS pcs_insee,
                csp.libelle AS categorie_socio_pro,
                actpro.description AS description_profession,
                
                -- Timestamps (utiliser syscredat/sysmajdat)
                sen.syscredat AS created_at,
                sen.sysmajdat AS updated_at
                
            FROM sen
            LEFT JOIN sennom ON sen.id = sennom.senateur_id 
                AND sennom.sennomdatfin IS NULL
            LEFT JOIN mel ON sen.id = mel.senateur_id
            LEFT JOIN actpro ON sen.id = actpro.senateur_id
            LEFT JOIN pcs ON actpro.pcs_id = pcs.id
            LEFT JOIN csp ON pcs.csp_id = csp.id
        ");
        
        $this->info('✅ Vue v_senateurs_complets créée');
    }

    public function down(): void
    {
        DB::statement("DROP VIEW IF EXISTS v_senateurs_complets");
    }
};

