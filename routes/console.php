<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
|--------------------------------------------------------------------------
| Tâches Planifiées
|--------------------------------------------------------------------------
| Ces commandes s'exécutent automatiquement via le scheduler Laravel.
| Ajouter au crontab : * * * * * php /var/www/artisan schedule:run >> /dev/null 2>&1
*/

// Recalcul des statistiques du dashboard tous les jours à 4h du matin
Schedule::command('dashboard:calculate-stats --force')
    ->dailyAt('04:00')
    ->description('Recalcul quotidien des statistiques dashboard');

// Recalcul des statistiques parlementaires (taux présence, amendements, etc.)
Schedule::command('calculate:parlementaires-stats --force')
    ->dailyAt('04:30')
    ->description('Recalcul quotidien des statistiques parlementaires pré-calculées');

// Recalcul des statistiques des lois (amendements, scrutins, durée, etc.)
Schedule::command('calculate:lois-stats --force')
    ->dailyAt('04:45')
    ->description('Recalcul quotidien des statistiques lois pré-calculées');

// Recalcul des statistiques globales des élus (page comparaison)
Schedule::command('calculate:elus-global-stats --force')
    ->dailyAt('05:00')
    ->description('Recalcul quotidien des statistiques globales élus (députés/sénateurs/maires)');

// Synchronisation des données parlementaires (si activée)
// Schedule::command('sync:all --quick')
//     ->dailyAt('05:00')
//     ->description('Synchronisation quotidienne des données AN/Sénat');

/*
|--------------------------------------------------------------------------
| Notifications Élus Suivis
|--------------------------------------------------------------------------
*/

// Traitement des nouvelles activités toutes les heures
Schedule::command('elu:process-activities')
    ->hourly()
    ->description('Détection et notification des nouvelles activités des élus suivis');

// Digest quotidien à 8h du matin
Schedule::command('elu:process-activities --digest=daily')
    ->dailyAt('08:00')
    ->description('Envoi des digests quotidiens des activités élus');

// Digest hebdomadaire le lundi à 8h
Schedule::command('elu:process-activities --digest=weekly')
    ->weeklyOn(1, '08:00')
    ->description('Envoi des digests hebdomadaires des activités élus');
