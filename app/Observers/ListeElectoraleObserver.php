<?php

namespace App\Observers;

use App\Models\ListeElectorale;
use App\Notifications\CandidatureNotification;

/**
 * Observer pour déclencher les notifications sur les listes électorales
 */
class ListeElectoraleObserver
{
    /**
     * Après création d'une liste
     */
    public function created(ListeElectorale $liste): void
    {
        if ($liste->createur) {
            $liste->createur->notify(new CandidatureNotification(
                $liste,
                'liste_creee'
            ));
        }
    }

    /**
     * Après mise à jour d'une liste
     */
    public function updated(ListeElectorale $liste): void
    {
        // Si le statut a changé, notifier
        if ($liste->isDirty('statut')) {
            $this->handleStatusChange($liste);
        }
    }

    /**
     * Gérer les changements de statut
     */
    private function handleStatusChange(ListeElectorale $liste): void
    {
        $createur = $liste->createur;
        if (!$createur) {
            return;
        }

        $notification = match($liste->statut) {
            'en_attente' => new CandidatureNotification($liste, 'liste_soumise'),
            'valide' => new CandidatureNotification($liste, 'liste_validee'),
            'rejete' => new CandidatureNotification($liste, 'liste_rejetee', $liste->motif_rejet),
            'documents_requis' => new CandidatureNotification(
                $liste,
                'documents_demandes',
                $liste->moderationLogs()->latest()->first()?->commentaire
            ),
            default => null,
        };

        if ($notification) {
            $createur->notify($notification);
        }
    }
}
