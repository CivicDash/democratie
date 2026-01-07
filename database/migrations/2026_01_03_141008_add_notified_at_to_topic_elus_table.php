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
        Schema::table('topic_elus', function (Blueprint $table) {
            // Date de notification à l'élu
            $table->timestamp('notified_at')->nullable()->after('response_status');
            
            // Email envoyé
            $table->timestamp('email_sent_at')->nullable()->after('notified_at');
            
            // Date de première consultation par l'élu
            $table->timestamp('viewed_at')->nullable()->after('email_sent_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('topic_elus', function (Blueprint $table) {
            $table->dropColumn(['notified_at', 'email_sent_at', 'viewed_at']);
        });
    }
};
