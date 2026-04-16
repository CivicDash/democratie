<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Table des effectifs de la fonction publique
 * Source : Rapport annuel DGAFP sur l'état de la fonction publique
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fonction_publique_effectifs', function (Blueprint $table) {
            $table->id();
            $table->integer('annee')->index();
            $table->string('versant', 50); // etat, territoriale, hospitaliere
            $table->string('versant_libelle', 100);
            $table->integer('effectif_total');
            $table->integer('titulaires')->nullable();
            $table->integer('contractuels')->nullable();
            $table->integer('autres')->nullable(); // militaires, ouvriers d'État, etc.
            $table->decimal('masse_salariale_md', 10, 2)->nullable(); // en milliards €
            $table->string('source', 100)->default('DGAFP');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['annee', 'versant']);
        });

        // Insérer les données connues (source : Rapport DGAFP 2023)
        $this->seedData();
    }

    private function seedData(): void
    {
        $data = [
            // 2022 (dernières données consolidées)
            ['annee' => 2022, 'versant' => 'etat', 'versant_libelle' => 'Fonction publique de l\'État',
                'effectif_total' => 2527000, 'titulaires' => 1754000, 'contractuels' => 439000, 'autres' => 334000,
                'masse_salariale_md' => 135.5, 'notes' => 'Inclut militaires (304k) et enseignants'],
            ['annee' => 2022, 'versant' => 'territoriale', 'versant_libelle' => 'Fonction publique territoriale',
                'effectif_total' => 1949000, 'titulaires' => 1478000, 'contractuels' => 421000, 'autres' => 50000,
                'masse_salariale_md' => 76.3, 'notes' => 'Communes, départements, régions, EPCI'],
            ['annee' => 2022, 'versant' => 'hospitaliere', 'versant_libelle' => 'Fonction publique hospitalière',
                'effectif_total' => 1216000, 'titulaires' => 878000, 'contractuels' => 294000, 'autres' => 44000,
                'masse_salariale_md' => 52.8, 'notes' => 'Hôpitaux publics, EHPAD publics'],

            // 2021
            ['annee' => 2021, 'versant' => 'etat', 'versant_libelle' => 'Fonction publique de l\'État',
                'effectif_total' => 2513000, 'titulaires' => 1762000, 'contractuels' => 418000, 'autres' => 333000,
                'masse_salariale_md' => 132.1],
            ['annee' => 2021, 'versant' => 'territoriale', 'versant_libelle' => 'Fonction publique territoriale',
                'effectif_total' => 1927000, 'titulaires' => 1471000, 'contractuels' => 406000, 'autres' => 50000,
                'masse_salariale_md' => 74.5],
            ['annee' => 2021, 'versant' => 'hospitaliere', 'versant_libelle' => 'Fonction publique hospitalière',
                'effectif_total' => 1201000, 'titulaires' => 871000, 'contractuels' => 286000, 'autres' => 44000,
                'masse_salariale_md' => 51.2],

            // 2020
            ['annee' => 2020, 'versant' => 'etat', 'versant_libelle' => 'Fonction publique de l\'État',
                'effectif_total' => 2491000, 'titulaires' => 1766000, 'contractuels' => 392000, 'autres' => 333000,
                'masse_salariale_md' => 127.8],
            ['annee' => 2020, 'versant' => 'territoriale', 'versant_libelle' => 'Fonction publique territoriale',
                'effectif_total' => 1914000, 'titulaires' => 1472000, 'contractuels' => 392000, 'autres' => 50000,
                'masse_salariale_md' => 72.1],
            ['annee' => 2020, 'versant' => 'hospitaliere', 'versant_libelle' => 'Fonction publique hospitalière',
                'effectif_total' => 1179000, 'titulaires' => 859000, 'contractuels' => 276000, 'autres' => 44000,
                'masse_salariale_md' => 48.9],

            // 2023 (estimations)
            ['annee' => 2023, 'versant' => 'etat', 'versant_libelle' => 'Fonction publique de l\'État',
                'effectif_total' => 2540000, 'titulaires' => 1750000, 'contractuels' => 455000, 'autres' => 335000,
                'masse_salariale_md' => 140.2, 'notes' => 'Estimation'],
            ['annee' => 2023, 'versant' => 'territoriale', 'versant_libelle' => 'Fonction publique territoriale',
                'effectif_total' => 1965000, 'titulaires' => 1480000, 'contractuels' => 435000, 'autres' => 50000,
                'masse_salariale_md' => 79.1, 'notes' => 'Estimation'],
            ['annee' => 2023, 'versant' => 'hospitaliere', 'versant_libelle' => 'Fonction publique hospitalière',
                'effectif_total' => 1230000, 'titulaires' => 880000, 'contractuels' => 306000, 'autres' => 44000,
                'masse_salariale_md' => 55.3, 'notes' => 'Estimation'],
        ];

        foreach ($data as $row) {
            DB::table('fonction_publique_effectifs')->insert(array_merge($row, [
                'source' => 'DGAFP - Rapport annuel sur l\'état de la fonction publique',
                'created_at' => now(),
                'updated_at' => now(),
            ]));
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('fonction_publique_effectifs');
    }
};
