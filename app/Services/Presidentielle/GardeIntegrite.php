<?php

namespace App\Services\Presidentielle;

/**
 * Ce qui empêche une écriture d'administration de figer objectif2027.fr.
 *
 * Une violation d'intégrité fait refuser `presidentielle:export`, donc tout le rebuild :
 * le site reste figé sur son dernier état jusqu'à correction. Or la plupart des gestes
 * qui en produisent une ne portent pas sur l'élément fautif, mais sur ce dont il dépend :
 *  - dépublier une mesure adossée à une option de quiz publiée ;
 *  - dépublier le seul argument « contre » d'une mesure, ou publier une première liaison
 *    « pour » sur une mesure déjà publiée ;
 *  - détacher une mesure d'une question publiée ;
 *  - retirer le crédit d'une photo de candidat.
 * Aucune de ces écritures n'était refusée, et l'effet n'apparaissait qu'au passage cron
 * suivant, sans rapport visible avec le geste.
 *
 * Plutôt que d'ajouter une vérification à chacun de ces points — il y en aurait toujours
 * un de plus —, on compare les violations avant et après l'écriture. Seules les
 * violations NOUVELLES bloquent : un défaut déjà présent ne doit pas empêcher un geste
 * sans rapport avec lui.
 */
class GardeIntegrite
{
    public function __construct(private IntegriteChecker $checker) {}

    /**
     * Empreinte des violations courantes : type et message suffisent à reconnaître une
     * violation d'une mesure à l'autre, le message nommant l'élément en cause.
     *
     * @return array<string, string> empreinte => message
     */
    public function empreintes(string $election = '2027'): array
    {
        $empreintes = [];
        foreach ($this->checker->analyser($election)['violations'] as $v) {
            $empreintes[$v['type'].'|'.$v['message']] = $v['message'];
        }

        return $empreintes;
    }

    /**
     * Violations présentes maintenant et absentes de l'empreinte donnée.
     *
     * @param  array<string, string>  $avant
     * @return list<string>
     */
    public function nouvelles(array $avant, string $election = '2027'): array
    {
        return array_values(array_diff_key($this->empreintes($election), $avant));
    }
}
