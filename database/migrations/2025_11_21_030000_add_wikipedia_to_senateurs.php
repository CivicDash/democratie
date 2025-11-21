<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('senateurs', function (Blueprint $table) {
            $table->string('wikipedia_url')->nullable()->after('description_profession');
            $table->string('wikipedia_photo')->nullable()->after('wikipedia_url');
            $table->text('wikipedia_extract')->nullable()->after('wikipedia_photo');
        });
    }

    public function down(): void
    {
        Schema::table('senateurs', function (Blueprint $table) {
            $table->dropColumn(['wikipedia_url', 'wikipedia_photo', 'wikipedia_extract']);
        });
    }
};

