<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;

class EluActivityDigestMail extends Mailable
{
    use Queueable, SerializesModels;

    public User $user;

    public Collection $activities;

    public string $frequency;

    public string $periodLabel;

    public function __construct(User $user, Collection $activities, string $frequency = 'daily')
    {
        $this->user = $user;
        $this->activities = $activities;
        $this->frequency = $frequency;
        $this->periodLabel = match ($frequency) {
            'instant' => 'Nouvelle activité',
            'daily' => 'Résumé quotidien',
            'weekly' => 'Résumé hebdomadaire',
            default => 'Activités',
        };
    }

    public function envelope(): Envelope
    {
        $elusCount = $this->activities->unique('elu_id')->count();
        $activitiesCount = $this->activities->count();

        $subject = match ($this->frequency) {
            'instant' => "🔔 {$this->activities->first()['elu_nom']} - Nouvelle activité",
            'daily' => "📊 Résumé quotidien - {$activitiesCount} activité(s) de vos élus",
            'weekly' => "📅 Résumé hebdomadaire - {$activitiesCount} activité(s) de vos élus",
            default => '🔔 Activités de vos élus suivis',
        };

        return new Envelope(subject: $subject);
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.elu-activity-digest',
            with: [
                'user' => $this->user,
                'activities' => $this->activities,
                'frequency' => $this->frequency,
                'periodLabel' => $this->periodLabel,
                'groupedActivities' => $this->activities->groupBy('elu_nom'),
                'preferencesUrl' => route('profile.elus-suivis'),
            ]
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
