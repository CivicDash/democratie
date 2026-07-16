<?php

namespace App\Mail;

use App\Models\PresidentielleSignalement;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Notifie le staff (secrétaire / président) d'un nouveau signalement citoyen
 * « Signaler une erreur ». Mise en file (Horizon) : n'impacte pas la réponse HTTP.
 */
class SignalementPresidentielleMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public PresidentielleSignalement $signalement) {}

    public function envelope(): Envelope
    {
        $type = PresidentielleSignalement::TYPES_INCIDENT[$this->signalement->type_incident]
            ?? $this->signalement->type_incident;

        return new Envelope(subject: "🚩 Nouveau signalement — {$type}");
    }

    public function content(): Content
    {
        $s = $this->signalement;

        return new Content(
            markdown: 'emails.presidentielle-signalement',
            with: [
                'typeLibelle' => PresidentielleSignalement::TYPES_INCIDENT[$s->type_incident] ?? $s->type_incident,
                'signalement' => $s,
                'boUrl' => route('admin.presidentielle.signalements'),
            ],
        );
    }
}
