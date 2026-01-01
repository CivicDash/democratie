<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('maires', function (Blueprint $table) {
            if (!Schema::hasColumn('maires', 'photo_url')) {
                $table->text('photo_url')->nullable()->after('en_exercice');
            }
            if (!Schema::hasColumn('maires', 'photo_wikipedia_url')) {
                $table->text('photo_wikipedia_url')->nullable()->after('photo_url');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('maires', function (Blueprint $table) {
            $table->dropColumn(['photo_url', 'photo_wikipedia_url']);
        });
    }
};
