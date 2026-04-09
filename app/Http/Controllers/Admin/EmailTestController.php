<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\DigestMail;
use App\Mail\EluActivityDigestMail;
use App\Mail\EluResponseMail;
use App\Mail\InterpellationNotificationMail;
use App\Mail\InvitationAssociationMail;
use App\Mail\InvitationEluMail;
use App\Mail\MentionNotificationMail;
use App\Mail\VoteResultMail;
use App\Mail\WelcomeMail;
use App\Models\Topic;
use App\Models\TopicElu;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Inertia\Inertia;

class EmailTestController extends Controller
{
    /**
     * Liste des templates disponibles pour le test
     */
    private const TEMPLATES = [
        'welcome' => [
            'name' => 'Bienvenue',
            'description' => 'Email envoyé aux nouveaux inscrits',
            'icon' => '🎉',
        ],
        'invitation-elu' => [
            'name' => 'Invitation Élu',
            'description' => 'Invitation pour un élu à rejoindre la plateforme',
            'icon' => '🏛️',
        ],
        'invitation-association' => [
            'name' => 'Invitation Association',
            'description' => 'Invitation pour un membre d\'association',
            'icon' => '🤝',
        ],
        'interpellation' => [
            'name' => 'Interpellation',
            'description' => 'Notification d\'interpellation pour un élu',
            'icon' => '📢',
        ],
        'elu-response' => [
            'name' => 'Réponse d\'élu',
            'description' => 'Notification quand un élu répond',
            'icon' => '💬',
        ],
        'mention' => [
            'name' => 'Mention',
            'description' => 'Notification de mention @utilisateur',
            'icon' => '@',
        ],
        'vote-result' => [
            'name' => 'Résultat de vote',
            'description' => 'Notification de résultat de scrutin',
            'icon' => '🗳️',
        ],
        'digest' => [
            'name' => 'Digest',
            'description' => 'Récapitulatif quotidien ou hebdomadaire',
            'icon' => '📰',
        ],
        'elu-activity' => [
            'name' => 'Activité Élu Suivi',
            'description' => 'Notification d\'activité d\'un élu suivi',
            'icon' => '🔔',
        ],
    ];

    /**
     * Afficher la page de test email
     */
    public function index()
    {
        return Inertia::render('Admin/EmailTest', [
            'templates' => self::TEMPLATES,
            'mailConfig' => [
                'driver' => config('mail.default'),
                'host' => config('mail.mailers.smtp.host'),
                'port' => config('mail.mailers.smtp.port'),
                'from' => config('mail.from.address'),
                'fromName' => config('mail.from.name'),
            ],
        ]);
    }

    /**
     * Envoyer un email de test
     */
    public function send(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'template' => 'required|string|in:'.implode(',', array_keys(self::TEMPLATES)),
        ]);

        $email = $request->input('email');
        $template = $request->input('template');

        try {
            $mailable = $this->buildTestMailable($template, $email);
            Mail::to($email)->send($mailable);

            return back()->with('success', "Email de test « {$template} » envoyé à {$email}");
        } catch (\Exception $e) {
            return back()->with('error', "Erreur lors de l'envoi : ".$e->getMessage());
        }
    }

    /**
     * Prévisualiser un template (rendu HTML)
     */
    public function preview(Request $request, string $template)
    {
        if (! isset(self::TEMPLATES[$template])) {
            abort(404, 'Template non trouvé');
        }

        $mailable = $this->buildTestMailable($template, 'preview@example.com');

        return $mailable->render();
    }

    /**
     * Construire un Mailable de test avec des données fictives
     */
    private function buildTestMailable(string $template, string $recipientEmail): \Illuminate\Mail\Mailable
    {
        // Créer un utilisateur fictif pour les tests
        $testUser = new User([
            'id' => 1,
            'name' => 'Jean Dupont',
            'email' => $recipientEmail,
        ]);

        switch ($template) {
            case 'welcome':
                return new WelcomeMail($testUser);

            case 'invitation-elu':
                return new InvitationEluMail(
                    eluName: 'Marie Martin',
                    eluFunction: 'Députée de la 3ème circonscription du Jura',
                    inviterName: 'L\'équipe CivicDash',
                    registerUrl: route('register'),
                    personalMessage: 'Nous serions ravis de vous accueillir sur notre plateforme pour dialoguer avec vos administrés.'
                );

            case 'invitation-association':
                return new InvitationAssociationMail(
                    memberName: 'Pierre Durand',
                    associationName: 'Association Citoyens Engagés',
                    inviterName: 'Marie Martin',
                    registerUrl: route('register'),
                    personalMessage: 'Rejoignez notre association sur CivicDash pour participer aux débats citoyens !'
                );

            case 'interpellation':
                // Créer des objets fictifs pour le test
                $topic = new Topic([
                    'id' => 1,
                    'title' => 'Question sur la rénovation énergétique des bâtiments publics',
                    'description' => 'Madame la Députée, je souhaiterais connaître votre position sur les aides à la rénovation énergétique des bâtiments publics dans notre département...',
                    'idea_type' => 'question',
                    'slug' => 'question-renovation-energetique',
                    'created_at' => now(),
                ]);
                $topicElu = new TopicElu(['id' => 1]);

                return new InterpellationNotificationMail(
                    $topic,
                    $topicElu,
                    $testUser,
                    'Marie Martin, Députée'
                );

            case 'elu-response':
                $topic = new Topic([
                    'id' => 1,
                    'title' => 'Question sur la rénovation énergétique',
                    'slug' => 'question-renovation-energetique',
                    'created_at' => now()->subDays(3),
                ]);
                $topicElu = new TopicElu([
                    'id' => 1,
                    'response' => 'Je vous remercie pour cette question importante. La rénovation énergétique est effectivement une priorité...',
                ]);

                return new EluResponseMail(
                    $topic,
                    $topicElu,
                    $testUser,
                    'Marie Martin',
                    'Députée du Jura',
                    'Je vous remercie pour cette question importante. La rénovation énergétique est effectivement une priorité de notre action au niveau national...'
                );

            case 'mention':
                return new MentionNotificationMail(
                    mentionedUser: $testUser,
                    author: new User(['id' => 2, 'name' => 'Sophie Bernard']),
                    contentType: 'discussion',
                    contentTitle: 'Débat sur les transports en commun',
                    contentExcerpt: '@Jean Dupont, que pensez-vous de la proposition de gratuité des transports pour les moins de 25 ans ?',
                    contentUrl: url('/participation/ideas/debat-transports')
                );

            case 'vote-result':
                return new VoteResultMail(
                    user: $testUser,
                    voteTitle: 'Proposition de loi pour la protection de l\'environnement',
                    voteType: 'scrutin_an',
                    result: 'adopté',
                    votesFor: 342,
                    votesAgainst: 187,
                    abstentions: 28,
                    voteUrl: url('/parlement/assemblee/scrutins/1234'),
                    eluPosition: 'Marie Martin (Députée du Jura) a voté POUR'
                );

            case 'digest':
                return new DigestMail(
                    user: $testUser,
                    period: 'weekly',
                    startDate: Carbon::now()->subDays(7),
                    endDate: Carbon::now(),
                    newVotes: [
                        ['title' => 'Loi de finances 2026', 'result' => '✅ Adopté', 'date' => '03/01'],
                        ['title' => 'Proposition sur les retraites', 'result' => '❌ Rejeté', 'date' => '02/01'],
                    ],
                    newInterpellations: [],
                    eluResponses: [
                        ['elu_name' => 'Marie Martin', 'topic_title' => 'Question sur la rénovation énergétique'],
                    ],
                    popularTopics: [
                        ['title' => 'Débat sur les transports', 'votes' => 156, 'comments' => 43],
                        ['title' => 'Réforme des retraites', 'votes' => 234, 'comments' => 89],
                    ],
                    totalNotifications: 12
                );

            case 'elu-activity':
                return new EluActivityDigestMail(
                    user: $testUser,
                    activities: collect([
                        [
                            'elu_type' => 'depute',
                            'elu_id' => 'PA12345',
                            'elu_nom' => 'Marie Martin',
                            'activity_type' => 'votes',
                            'activity_id' => 1234,
                            'activity_date' => now()->subHours(2),
                            'activity_title' => 'Projet de loi relatif à la transition énergétique',
                            'activity_detail' => '✅ A voté Pour',
                            'activity_icon' => '🗳️',
                            'activity_url' => url('/parlement/assemblee/scrutins/1234'),
                        ],
                        [
                            'elu_type' => 'depute',
                            'elu_id' => 'PA12345',
                            'elu_nom' => 'Marie Martin',
                            'activity_type' => 'votes',
                            'activity_id' => 1235,
                            'activity_date' => now()->subHours(3),
                            'activity_title' => 'Motion de censure',
                            'activity_detail' => '❌ A voté Contre',
                            'activity_icon' => '🗳️',
                            'activity_url' => url('/parlement/assemblee/scrutins/1235'),
                        ],
                        [
                            'elu_type' => 'depute',
                            'elu_id' => 'PA67890',
                            'elu_nom' => 'Jean Dupont',
                            'activity_type' => 'votes',
                            'activity_id' => 1234,
                            'activity_date' => now()->subHours(2),
                            'activity_title' => 'Projet de loi relatif à la transition énergétique',
                            'activity_detail' => '⚪ S\'est abstenu',
                            'activity_icon' => '🗳️',
                            'activity_url' => url('/parlement/assemblee/scrutins/1234'),
                        ],
                    ]),
                    frequency: 'daily'
                );

            default:
                throw new \InvalidArgumentException("Template inconnu : {$template}");
        }
    }
}
