<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('email_logs', function (Blueprint $table) {
            $table->id();
            $table->string('to_email');
            $table->string('to_name')->nullable();
            $table->string('subject', 500);
            $table->string('mailable_class')->nullable();
            $table->string('status', 50)->default('sent'); // sent, failed, queued
            $table->string('message_id')->nullable();
            $table->text('error_message')->nullable();
            $table->jsonb('metadata')->nullable(); // Données additionnelles
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();

            // Index pour les requêtes
            $table->index('to_email');
            $table->index('status');
            $table->index('mailable_class');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_logs');
    }
};
