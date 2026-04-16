<?php

namespace App\Jobs;

use App\Models\CommuneAbonnement;
use App\Models\CommunePage;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Notification;

class BroadcastCommuneNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 600;

    public function __construct(
        private int $communePageId,
        private string $sujet,
        private string $contenu,
        private string $type,
        private string $cible,
        private int $envoyeurId,
    ) {}

    public function handle(): void
    {
        $page = CommunePage::with('ville')->findOrFail($this->communePageId);

        $query = CommuneAbonnement::where('commune_code_insee', $page->code_insee);

        $userIds = $query->pluck('user_id');

        $users = User::whereIn('id', $userIds)->get();

        $communeNom = $page->ville?->nom ?? $page->code_insee;

        $notification = new \App\Notifications\CommuneBroadcastNotification(
            communePage: $page,
            sujet: $this->sujet,
            contenu: $this->contenu,
            type: $this->type,
            cible: $this->cible,
        );

        foreach ($users->chunk(100) as $chunk) {
            Notification::send($chunk, $notification);
        }
    }
}
