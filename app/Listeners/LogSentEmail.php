<?php

namespace App\Listeners;

use App\Models\EmailLog;
use App\Models\User;
use Illuminate\Mail\Events\MessageSent;
use Illuminate\Support\Facades\Log;

class LogSentEmail
{
    /**
     * Handle the event.
     */
    public function handle(MessageSent $event): void
    {
        try {
            $message = $event->message;
            $to = $message->getTo();
            $toEmail = '';
            $toName = null;

            // Extraire l'email et le nom du destinataire
            foreach ($to as $address) {
                $toEmail = $address->getAddress();
                $toName = $address->getName();
                break; // Premier destinataire seulement
            }

            // Trouver l'utilisateur par email
            $user = User::where('email', $toEmail)->first();

            // Récupérer la classe du Mailable si disponible
            $mailableClass = null;
            if (isset($event->data['__laravel_notification'])) {
                $mailableClass = get_class($event->data['__laravel_notification']);
            } elseif (isset($event->data['__mailable'])) {
                $mailableClass = get_class($event->data['__mailable']);
            }

            EmailLog::create([
                'to_email' => $toEmail,
                'to_name' => $toName,
                'subject' => $message->getSubject() ?? 'Sans sujet',
                'mailable_class' => $mailableClass,
                'status' => 'sent',
                'message_id' => $message->getHeaders()->get('Message-ID')?->getBodyAsString(),
                'user_id' => $user?->id,
                'sent_at' => now(),
                'metadata' => [
                    'cc' => collect($message->getCc())->map(fn($a) => $a->getAddress())->toArray(),
                    'bcc' => collect($message->getBcc())->map(fn($a) => $a->getAddress())->toArray(),
                ],
            ]);
        } catch (\Throwable $e) {
            // Ne pas bloquer l'envoi si le log échoue
            Log::error('Erreur log email', [
                'error' => $e->getMessage(),
            ]);
        }
    }
}
