<?php

namespace App\Console\Commands;

use App\Mail\DigestMail;
use App\Mail\EluResponseMail;
use App\Mail\InterpellationNotificationMail;
use App\Mail\InvitationAssociationMail;
use App\Mail\InvitationEluMail;
use App\Mail\UserMentionMail;
use App\Mail\WelcomeMail;
use App\Models\Topic;
use App\Models\TopicElu;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Mail;

class PreviewEmail extends Command
{
    protected $signature = 'email:preview 
                            {template : Template à prévisualiser (welcome, invitation-elu, invitation-association, mention, elu-response, interpellation, digest)}
                            {--send= : Envoyer à cette adresse email}
                            {--save : Sauvegarder le HTML dans storage/app/email-previews/}';

    protected $description = 'Prévisualiser ou tester les templates email';

    public function handle(): int
    {
        $template = $this->argument('template');
        $sendTo = $this->option('send');
        $save = $this->option('save');

        $this->info("📧 Prévisualisation du template: {$template}");

        try {
            $mailable = $this->buildMailable($template);

            if ($sendTo) {
                $this->info("📤 Envoi à {$sendTo}...");
                Mail::to($sendTo)->send($mailable);
                $this->info('✅ Email envoyé !');
            }

            if ($save) {
                $html = $mailable->render();
                $filename = "email-previews/{$template}-".now()->format('Y-m-d-His').'.html';
                \Storage::put($filename, $html);
                $this->info("💾 Sauvegardé dans storage/app/{$filename}");
            }

            if (! $sendTo && ! $save) {
                // Afficher un aperçu dans la console
                $this->newLine();
                $this->info('📋 Sujet: '.$mailable->envelope()->subject);
                $this->newLine();
                $this->line('Pour voir le rendu HTML, utilisez --save ou --send=email@example.com');
            }

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error('❌ Erreur: '.$e->getMessage());

            return Command::FAILURE;
        }
    }

    private function buildMailable(string $template): \Illuminate\Mail\Mailable
    {
        // Créer des données de test
        $testUser = $this->getTestUser();

        return match ($template) {
            'welcome' => new WelcomeMail($testUser),

            'invitation-elu' => new InvitationEluMail(
                eluName: 'Jean Dupont',
                eluType: 'depute',
                inviterName: 'Admin CivicDash',
                registerUrl: config('app.url').'/register?invitation=test123',
                personalMessage: 'Bonjour Monsieur le Député, nous serions honorés de vous compter parmi les élus actifs sur CivicDash.'
            ),

            'invitation-association' => new InvitationAssociationMail(
                recipientName: 'Marie Martin',
                associationName: 'Association des Citoyens Engagés',
                inviterName: 'Pierre Durand',
                registerUrl: config('app.url').'/register?invitation=assoc456',
                personalMessage: 'Rejoins-nous pour suivre ensemble l\'actualité politique !',
                role: 'membre'
            ),

            'mention' => new UserMentionMail(
                mentionedUser: $testUser,
                author: $this->getTestUser('Auteur Test'),
                contentType: 'topic',
                contentTitle: 'Débat sur la transition énergétique',
                contentExcerpt: 'Comme le suggère @utilisateur, nous devrions envisager une approche plus progressive pour la transition énergétique. Les enjeux sont multiples et concernent aussi bien les particuliers que les entreprises...',
                contentUrl: config('app.url').'/participation/ideas/test-topic'
            ),

            'elu-response' => new EluResponseMail(
                citizen: $testUser,
                eluName: 'Marie Leblanc',
                eluType: 'depute',
                topic: $this->getTestTopic(),
                responseExcerpt: 'Merci pour votre interpellation pertinente. Je suis pleinement engagée sur ce sujet et travaille actuellement sur une proposition de loi visant à améliorer la situation. Je vous invite à suivre mes prochaines interventions à l\'Assemblée Nationale.',
                topicUrl: config('app.url').'/participation/ideas/test-interpellation'
            ),

            'interpellation' => $this->buildInterpellationMail(),

            'digest' => new DigestMail(
                user: $testUser,
                period: 'daily',
                notifications: $this->getTestNotifications(),
                stats: [
                    'Nouvelles idées' => 12,
                    'Votes reçus' => 45,
                    'Réponses d\'élus' => 3,
                ]
            ),

            default => throw new \InvalidArgumentException("Template inconnu: {$template}"),
        };
    }

    private function getTestUser(string $name = 'Utilisateur Test'): User
    {
        return User::first() ?? new User([
            'id' => 1,
            'name' => $name,
            'email' => 'test@example.com',
            'username' => 'testuser',
        ]);
    }

    private function getTestTopic(): Topic
    {
        return Topic::first() ?? new Topic([
            'id' => 1,
            'title' => 'Amélioration des transports en commun',
            'description' => 'Proposition pour améliorer les transports en commun dans notre ville.',
            'created_at' => now(),
        ]);
    }

    private function getTestNotifications(): Collection
    {
        return collect([
            (object) [
                'category' => 'response',
                'title' => 'Réponse de Marie Leblanc',
                'message' => 'La députée a répondu à votre interpellation sur les transports.',
            ],
            (object) [
                'category' => 'vote',
                'title' => 'Votre proposition a atteint 100 votes',
                'message' => 'Félicitations ! Votre idée sur l\'écologie a atteint le seuil des 100 votes.',
            ],
            (object) [
                'category' => 'mention',
                'title' => 'Pierre vous a mentionné',
                'message' => 'Dans le débat sur l\'éducation.',
            ],
            (object) [
                'category' => 'comment',
                'title' => 'Nouveau commentaire',
                'message' => 'Sophie a commenté votre proposition.',
            ],
            (object) [
                'category' => 'system',
                'title' => 'Mise à jour CivicDash',
                'message' => 'Nouvelles fonctionnalités disponibles !',
            ],
        ]);
    }

    private function buildInterpellationMail(): InterpellationNotificationMail
    {
        $topic = $this->getTestTopic();
        $user = $this->getTestUser();

        $topicElu = TopicElu::first() ?? new TopicElu([
            'id' => 1,
            'topic_id' => 1,
            'elu_type' => 'depute',
        ]);

        return new InterpellationNotificationMail(
            $topic,
            $topicElu,
            $user,
            'Jean-Pierre Martin'
        );
    }
}
