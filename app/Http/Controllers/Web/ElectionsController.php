<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Contrôleur pour les pages Élections
 */
class ElectionsController extends Controller
{
    /**
     * Page Hub Élections
     */
    public function hub(): Response
    {
        return Inertia::render('Elections/Hub', [
            'prochaines_elections' => $this->getProchainseElections(),
            'statistiques' => [
                'communes' => 34914,
                'circonscriptions' => 577,
                'senateurs' => 348,
            ],
        ]);
    }

    /**
     * Page Élections Municipales
     */
    public function municipales(): Response
    {
        return Inertia::render('Elections/Municipales');
    }

    /**
     * Page Élections Législatives
     */
    public function legislatives(): Response
    {
        return Inertia::render('Elections/Legislatives');
    }

    /**
     * Page Élections Sénatoriales
     */
    public function senatoriales(): Response
    {
        return Inertia::render('Elections/Senatoriales');
    }

    /**
     * Page Élection Présidentielle
     */
    public function presidentielle(): Response
    {
        return Inertia::render('Elections/Presidentielle');
    }

    /**
     * Récupérer les prochaines élections
     */
    private function getProchainseElections(): array
    {
        return [
            [
                'type' => 'municipales',
                'titre' => 'Élections Municipales',
                'date' => '2026-03-15',
                'date_formatee' => 'Mars 2026',
            ],
            [
                'type' => 'senatoriales',
                'titre' => 'Élections Sénatoriales',
                'date' => '2026-09-27',
                'date_formatee' => 'Septembre 2026',
            ],
            [
                'type' => 'presidentielle',
                'titre' => 'Élection Présidentielle',
                'date' => '2027-04-10',
                'date_formatee' => 'Avril 2027',
            ],
            [
                'type' => 'legislatives',
                'titre' => 'Élections Législatives',
                'date' => '2027-06-12',
                'date_formatee' => 'Juin 2027',
            ],
        ];
    }
}
