<?php

namespace App\Http\Middleware;

use App\Services\Presidentielle\GardeIntegrite;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

/**
 * Refuse toute écriture du back-office présidentiel qui ferait échouer l'export.
 *
 * Posé sur le groupe de routes entier plutôt que dans chaque action : c'est la seule
 * façon d'être sûr que la prochaine route d'écriture en bénéficie aussi. Voir
 * GardeIntegrite pour les gestes qui figeaient le site sans être refusés.
 *
 * La requête s'exécute dans une transaction. Si elle échoue, tout est annulé — une
 * requête d'administration est ainsi atomique, ce qu'elle n'était pas. Si elle réussit
 * mais introduit une violation d'intégrité, tout est annulé aussi, et l'écran dit
 * laquelle.
 *
 * Sous Octane, un worker sert plusieurs requêtes avec la même connexion : chaque chemin
 * de sortie ferme donc la transaction, sans quoi la requête suivante hériterait d'une
 * transaction ouverte.
 */
class GardeIntegriteExport
{
    public function __construct(private GardeIntegrite $garde) {}

    public function handle(Request $request, Closure $next): Response
    {
        if ($request->isMethodSafe()) {
            return $next($request);
        }

        $avant = $this->garde->empreintes();

        DB::beginTransaction();
        try {
            $response = $next($request);

            if ($this->aEchoue($request, $response)) {
                DB::rollBack();

                return $response;
            }

            $nouvelles = $this->garde->nouvelles($avant);
            if ($nouvelles) {
                DB::rollBack();

                return $this->refus($request, $nouvelles);
            }

            DB::commit();

            return $response;
        } catch (Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Erreur HTTP, ou erreur de validation rendue en redirection : l'exception a déjà été
     * convertie en réponse par le pipeline de routage quand elle arrive ici. Une requête
     * refusée ne doit rien laisser derrière elle — et si le contrôleur a intercepté une
     * erreur SQL (un doublon, par exemple), PostgreSQL tient la transaction pour avortée :
     * la moindre requête d'analyse échouerait.
     */
    private function aEchoue(Request $request, Response $response): bool
    {
        if ($response->getStatusCode() >= 400) {
            return true;
        }

        return $request->hasSession()
            && in_array('errors', (array) $request->session()->get('_flash.new', []), true);
    }

    /** @param list<string> $nouvelles */
    private function refus(Request $request, array $nouvelles): Response
    {
        $message = 'Modification annulée : elle ferait échouer la mise à jour d\'objectif2027.fr, '
            .'qui resterait figé. '.implode(' ; ', array_slice($nouvelles, 0, 5))
            .(count($nouvelles) > 5 ? ' ; et '.(count($nouvelles) - 5).' autre(s).' : '');

        // Le contrôleur a pu annoncer un succès : il n'a pas eu lieu.
        if ($request->hasSession()) {
            $request->session()->forget(['success', 'echecs_lot']);
        }

        if ($request->expectsJson() && ! $request->header('X-Inertia')) {
            return response()->json(['message' => $message, 'errors' => ['integrite' => [$message]]], 422);
        }

        return back()->withErrors(['integrite' => $message]);
    }
}
