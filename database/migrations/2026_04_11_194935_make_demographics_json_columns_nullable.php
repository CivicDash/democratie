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
        Schema::table('france_demographics', function (Blueprint $table) {
            $table->jsonb('population_by_age_group')->nullable()->change();
            $table->jsonb('population_by_gender')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('france_demographics', function (Blueprint $table) {
            $table->jsonb('population_by_age_group')->nullable(false)->default('{}')->change();
            $table->jsonb('population_by_gender')->nullable(false)->default('{}')->change();
        });
    }
};
