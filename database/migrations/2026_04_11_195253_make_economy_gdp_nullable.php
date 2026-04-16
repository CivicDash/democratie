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
        Schema::table('france_economy', function (Blueprint $table) {
            $table->decimal('gdp_billions_euros', 10, 2)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('france_economy', function (Blueprint $table) {
            $table->decimal('gdp_billions_euros', 10, 2)->nullable(false)->default(0)->change();
        });
    }
};
