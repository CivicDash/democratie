<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_association_member')->default(false)->after('is_verified_elu');
            $table->string('member_type', 50)->nullable()->after('is_association_member'); // adherent, bienfaiteur, fondateur, honneur
            $table->date('member_since')->nullable()->after('member_type');
            $table->date('member_until')->nullable()->after('member_since'); // NULL = à vie
            $table->string('member_number', 50)->nullable()->after('member_until'); // Numéro d'adhérent
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'is_association_member',
                'member_type',
                'member_since',
                'member_until',
                'member_number',
            ]);
        });
    }
};
