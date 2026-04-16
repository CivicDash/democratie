<?php

/**
 * Script pour marquer manuellement la migration comme exécutée
 * À exécuter sur le serveur : php fix_migrations_command.php
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

try {
    // Marquer la migration des maires comme exécutée
    $result = DB::table('migrations')->insertOrIgnore([
        'migration' => '2025_11_08_141000_create_maires_table',
        'batch' => 1,
    ]);

    echo "✅ Migration marquée comme exécutée\n";
    echo '📊 Lignes affectées : '.($result ? '1 (nouvelle insertion)' : '0 (déjà existante)')."\n";
    echo "\n";
    echo "🚀 Vous pouvez maintenant relancer ./deploy.sh\n";

} catch (Exception $e) {
    echo '❌ Erreur : '.$e->getMessage()."\n";
    exit(1);
}
