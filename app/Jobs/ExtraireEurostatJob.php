<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Artisan;

/**
 * Extraction Eurostat lancée depuis le back-office (bouton « Extraire maintenant »).
 * Une trentaine de requêtes à l'API : trop long pour une requête web, d'où la file.
 */
class ExtraireEurostatJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public int $timeout = 900;

    public int $tries = 1;

    public function handle(): void
    {
        Artisan::call('presidentielle:eurostat-extraire');
    }
}
