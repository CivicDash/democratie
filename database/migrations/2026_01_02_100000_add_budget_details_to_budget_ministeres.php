<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ajoute les colonnes pour les différents types de budget (PLF)
     */
    public function up(): void
    {
        Schema::table('budget_ministeres', function (Blueprint $table) {
            // Budget général (en milliards €)
            $table->decimal('budget_general', 18, 2)->nullable()->after('budget_cp');
            
            // Budgets annexes
            $table->decimal('budgets_annexes', 18, 2)->nullable()->after('budget_general');
            
            // Comptes d'affectation spéciale
            $table->decimal('comptes_affectation_speciale', 18, 2)->nullable()->after('budgets_annexes');
            
            // Comptes de concours financiers
            $table->decimal('comptes_concours_financiers', 18, 2)->nullable()->after('comptes_affectation_speciale');
            
            // Total calculé
            $table->decimal('budget_total', 18, 2)->nullable()->after('comptes_concours_financiers');
            
            // Source des données
            $table->string('source', 100)->nullable()->after('couleur');
        });
    }

    public function down(): void
    {
        Schema::table('budget_ministeres', function (Blueprint $table) {
            $table->dropColumn([
                'budget_general',
                'budgets_annexes',
                'comptes_affectation_speciale',
                'comptes_concours_financiers',
                'budget_total',
                'source',
            ]);
        });
    }
};
