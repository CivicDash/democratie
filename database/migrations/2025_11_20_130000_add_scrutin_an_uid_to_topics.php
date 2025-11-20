<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('topics', function (Blueprint $table) {
            $table->string('scrutin_an_uid', 30)->nullable()->after('ballot_type');
            $table->index('scrutin_an_uid');
        });
    }

    public function down(): void
    {
        Schema::table('topics', function (Blueprint $table) {
            $table->dropIndex(['scrutin_an_uid']);
            $table->dropColumn('scrutin_an_uid');
        });
    }
};

