#!/bin/bash

# Script de diagnostic via PHP Artisan Tinker (fonctionne partout)
# À lancer depuis /opt/civicdash/scripts/

cd /opt/civicdash || exit 1

echo "🔍 Diagnostic Tables Sénat - Amendements & Votes (via Tinker)"
echo "=============================================================="
echo ""

# Créer un fichier PHP temporaire pour Tinker
cat > /tmp/diagnostic_senat.php << 'EOF'
<?php

use Illuminate\Support\Facades\DB;

echo "\n📋 Structure de senat_ameli_amdsen (amendements auteurs) :\n";
echo "==========================================================\n";
$cols = DB::select("SELECT column_name, data_type FROM information_schema.columns WHERE table_name = 'senat_ameli_amdsen' ORDER BY ordinal_position LIMIT 15");
foreach ($cols as $col) {
    echo sprintf("  - %-30s %s\n", $col->column_name, $col->data_type);
}

echo "\n📋 Colonnes senid/senmat dans senat_senateurs_sen :\n";
echo "====================================================\n";
$cols = DB::select("SELECT column_name, data_type FROM information_schema.columns WHERE table_name = 'senat_senateurs_sen' AND (column_name LIKE '%senid%' OR column_name LIKE '%senmat%')");
foreach ($cols as $col) {
    echo sprintf("  - %-30s %s\n", $col->column_name, $col->data_type);
}

echo "\n🔍 Vérifier si senid existe dans senat_senateurs_sen :\n";
echo "======================================================\n";
$senidExists = DB::select("SELECT COUNT(*) as count FROM information_schema.columns WHERE table_name = 'senat_senateurs_sen' AND column_name = 'senid'");
if ($senidExists[0]->count > 0) {
    echo "  ✅ Colonne senid EXISTE dans senat_senateurs_sen\n";
} else {
    echo "  ❌ Colonne senid N'EXISTE PAS dans senat_senateurs_sen\n";
}

echo "\n📊 Exemple de données senat_senateurs_sen (3 premiers) :\n";
echo "=========================================================\n";
$sens = DB::select("SELECT senmat, sennomuse, senprenomuse FROM senat_senateurs_sen LIMIT 3");
foreach ($sens as $sen) {
    echo sprintf("  - Matricule: %-10s Nom: %s %s\n", $sen->senmat, $sen->senprenomuse, $sen->sennomuse);
}

echo "\n📊 Exemple de données senat_ameli_amdsen (3 premiers) :\n";
echo "========================================================\n";
$amds = DB::select("SELECT amdid, senid, nomuse, prenomuse, rng FROM senat_ameli_amdsen LIMIT 3");
foreach ($amds as $amd) {
    echo sprintf("  - AMD ID: %-6s SEN ID: %-6s Nom: %s %s (rang: %s)\n", $amd->amdid, $amd->senid, $amd->prenomuse, $amd->nomuse, $amd->rng);
}

echo "\n🔗 Tester la jointure senid → senmat (5 premiers) :\n";
echo "====================================================\n";
try {
    $joins = DB::select("
        SELECT 
            amdsen.senid,
            sen.senmat,
            sen.sennomuse AS nom_sen_table,
            amdsen.nomuse AS nom_amd_table
        FROM senat_ameli_amdsen amdsen
        LEFT JOIN senat_senateurs_sen sen ON amdsen.senid = sen.senid
        LIMIT 5
    ");
    foreach ($joins as $join) {
        echo sprintf("  - SEN ID: %-6s → Matricule: %-10s | Nom (SEN): %-20s | Nom (AMD): %s\n", 
            $join->senid ?? 'NULL', 
            $join->senmat ?? 'NULL', 
            $join->nom_sen_table ?? 'NULL',
            $join->nom_amd_table ?? 'NULL'
        );
    }
} catch (\Exception $e) {
    echo "  ❌ Erreur jointure: " . $e->getMessage() . "\n";
}

echo "\n📊 Exemple amendements_senat (vue actuelle) :\n";
echo "==============================================\n";
$amds_vue = DB::select("SELECT id, senateur_matricule, numero, auteur_nom, sort_libelle FROM amendements_senat LIMIT 3");
foreach ($amds_vue as $amd) {
    echo sprintf("  - ID: %-6s Matricule: %-10s Numéro: %-6s Auteur: %s\n", 
        $amd->id, 
        $amd->senateur_matricule ?? 'NULL', 
        $amd->numero ?? 'NULL',
        $amd->auteur_nom ?? 'NULL'
    );
}

echo "\n🔢 Total amendements dans la vue :\n";
echo "==================================\n";
$count_amds = DB::select("SELECT COUNT(*) as total FROM amendements_senat");
echo sprintf("  Total: %d amendements\n", $count_amds[0]->total);

echo "\n🔢 Total votes dans la vue :\n";
echo "============================\n";
$count_votes = DB::select("SELECT COUNT(*) as total FROM senateurs_votes");
echo sprintf("  Total: %d votes\n", $count_votes[0]->total);

echo "\n🔍 Chercher un sénateur spécifique (matricule 19565D) :\n";
echo "========================================================\n";
$sen_test = DB::select("SELECT senmat, sennomuse, senprenomuse FROM senat_senateurs_sen WHERE senmat = '19565D'");
if (count($sen_test) > 0) {
    echo sprintf("  ✅ Trouvé: %s %s (matricule: %s)\n", $sen_test[0]->senprenomuse, $sen_test[0]->sennomuse, $sen_test[0]->senmat);
    
    // Chercher ses amendements
    $amds_test = DB::select("SELECT COUNT(*) as total FROM amendements_senat WHERE senateur_matricule = '19565D'");
    echo sprintf("  📝 Amendements dans la vue: %d\n", $amds_test[0]->total);
    
    // Chercher ses votes
    $votes_test = DB::select("SELECT COUNT(*) as total FROM senateurs_votes WHERE senateur_matricule = '19565D'");
    echo sprintf("  🗳️  Votes dans la vue: %d\n", $votes_test[0]->total);
} else {
    echo "  ❌ Sénateur 19565D non trouvé\n";
}

echo "\n✅ Diagnostic terminé !\n";
EOF

# Lancer le diagnostic via Tinker
php artisan tinker < /tmp/diagnostic_senat.php

# Nettoyer
rm -f /tmp/diagnostic_senat.php

echo ""
echo "💡 Si la jointure senid fonctionne, la vue doit être corrigée !"

