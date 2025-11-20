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
        Schema::table('territories_regions', function (Blueprint $table) {
            $table->string('code', 3)->change()->comment('Code INSEE région (ex: 11, 93, 01 pour DOM-COM)');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('territories_regions', function (Blueprint $table) {
            $table->string('code', 2)->change()->comment('Code INSEE région (ex: 11, 93)');
        });
    }
};

