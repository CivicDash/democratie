<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('personnes_politiques', function (Blueprint $table) {
            $table->string('wikidata_id', 20)->nullable()->after('wikipedia_extract');
            $table->timestamp('wikipedia_last_sync')->nullable()->after('wikidata_id');
            $table->string('url_hatvp')->nullable()->after('site_web');
            $table->string('hatvp_type_mandat', 50)->nullable()->after('url_hatvp');

            $table->index('wikidata_id');
        });
    }

    public function down(): void
    {
        Schema::table('personnes_politiques', function (Blueprint $table) {
            $table->dropIndex(['wikidata_id']);
            $table->dropColumn(['wikidata_id', 'wikipedia_last_sync', 'url_hatvp', 'hatvp_type_mandat']);
        });
    }
};
