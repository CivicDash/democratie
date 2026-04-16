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
        Schema::table('users', function (Blueprint $table) {
            // Liaison avec un élu (député, sénateur, maire)
            $table->string('elu_type', 20)->nullable()->after('password');
            $table->string('elu_ref', 50)->nullable()->after('elu_type');

            // Vérification élu
            $table->boolean('is_verified_elu')->default(false)->after('elu_ref');
            $table->timestamp('verified_at')->nullable()->after('is_verified_elu');

            // Profil public élu
            $table->boolean('is_public_profile')->default(false)->after('verified_at');
            $table->text('elu_bio')->nullable()->after('is_public_profile');
            $table->string('twitter_handle', 100)->nullable()->after('elu_bio');
            $table->string('facebook_url', 500)->nullable()->after('twitter_handle');
            $table->string('website_url', 500)->nullable()->after('facebook_url');

            // Index pour recherche
            $table->index(['elu_type', 'elu_ref']);
            $table->index('is_verified_elu');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['elu_type', 'elu_ref']);
            $table->dropIndex(['is_verified_elu']);

            $table->dropColumn([
                'elu_type',
                'elu_ref',
                'is_verified_elu',
                'verified_at',
                'is_public_profile',
                'elu_bio',
                'twitter_handle',
                'facebook_url',
                'website_url',
            ]);
        });
    }
};
