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
            // 2FA Secret (encrypted)
            $table->text('two_factor_secret')->nullable()->after('password');
            
            // Recovery codes (JSON array, encrypted)
            $table->text('two_factor_recovery_codes')->nullable()->after('two_factor_secret');
            
            // 2FA confirmed at timestamp
            $table->timestamp('two_factor_confirmed_at')->nullable()->after('two_factor_recovery_codes');
            
            // Whether 2FA is enabled
            $table->boolean('two_factor_enabled')->default(false)->after('two_factor_confirmed_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'two_factor_secret',
                'two_factor_recovery_codes',
                'two_factor_confirmed_at',
                'two_factor_enabled',
            ]);
        });
    }
};
