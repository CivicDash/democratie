<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('topics', function (Blueprint $table) {
            $table->string('commune_code_insee', 5)->nullable()->after('department_id');
            $table->index('commune_code_insee');
        });

        Schema::table('profiles', function (Blueprint $table) {
            $table->string('commune_code_insee', 5)->nullable()->after('department_id');
            $table->index('commune_code_insee');
        });
    }

    public function down(): void
    {
        Schema::table('topics', function (Blueprint $table) {
            $table->dropIndex(['commune_code_insee']);
            $table->dropColumn('commune_code_insee');
        });

        Schema::table('profiles', function (Blueprint $table) {
            $table->dropIndex(['commune_code_insee']);
            $table->dropColumn('commune_code_insee');
        });
    }
};
