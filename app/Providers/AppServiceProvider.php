<?php

namespace App\Providers;

use App\Listeners\LogSentEmail;
use App\Models\ListeElectorale;
use App\Models\Post;
use App\Models\Topic;
use App\Models\Vote;
use App\Observers\ListeElectoraleObserver;
use App\Observers\PostHashtagObserver;
use App\Observers\PostObserver;
use App\Observers\TopicHashtagObserver;
use App\Observers\TopicObserver;
use App\Observers\VoteObserver;
use Illuminate\Console\Events\ScheduledTaskFailed;
use Illuminate\Console\Events\ScheduledTaskFinished;
use Illuminate\Console\Events\ScheduledTaskStarting;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Mail\Events\MessageSent;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Vite::prefetch(concurrency: 3);

        $this->configurerModeStrictEloquent();

        // Observers hashtags (auto-extraction)
        Post::observe(PostHashtagObserver::class);
        Topic::observe(TopicHashtagObserver::class);

        // Observer pour les notifications de candidatures municipales
        ListeElectorale::observe(ListeElectoraleObserver::class);

        // Listener pour logger les emails envoyés
        Event::listen(MessageSent::class, LogSentEmail::class);

        // Logger scheduler : historique des imports planifiés
        Event::listen(ScheduledTaskStarting::class, [\App\Listeners\SchedulerTaskLogger::class, 'starting']);
        Event::listen(ScheduledTaskFinished::class, [\App\Listeners\SchedulerTaskLogger::class, 'finished']);
        Event::listen(ScheduledTaskFailed::class, [\App\Listeners\SchedulerTaskLogger::class, 'failed']);

        // TODO: Enregistrer les observers pour la gamification quand les modèles existent
        // Vote::observe(VoteObserver::class);
        // Topic::observe(TopicObserver::class);
        // Post::observe(PostObserver::class);
    }

    /**
     * Rend bruyants les échecs d'écriture qu'Eloquent avale par défaut.
     *
     * L'audit de l'administration a montré que le vrai défaut n'était pas tel ou tel
     * champ oublié, mais le fait qu'un champ oublié ne se voie pas : fill() jette
     * silencieusement les clés hors $fillable, et le contrôleur affiche « succès »
     * quand même. Douze champs d'adhésion, cinq champs de suspension et sept écrans
     * de statistiques ont dérivé ainsi, sans qu'aucun message n'alerte jamais.
     *
     * Hors production, ces violations lèvent : la CI et le poste de développement les
     * attrapent avant la mise en ligne. En production elles sont journalisées et non
     * levées — on ne casse pas l'écran d'un bénévole en pleine session de modération,
     * mais on sait, avec l'URL exacte, que quelque chose n'a pas été écrit.
     */
    private function configurerModeStrictEloquent(): void
    {
        $strict = ! $this->app->isProduction();

        Model::preventSilentlyDiscardingAttributes($strict);
        Model::preventAccessingMissingAttributes($strict);

        // preventLazyLoading reste opt-in : la base de code compte encore des N+1
        // connus (voir docs/AUDIT_ADMINISTRATION.md, lot 7). L'activer d'emblée
        // noierait le signal des deux gardes ci-dessus sous des centaines d'échecs.
        Model::preventLazyLoading((bool) env('ELOQUENT_STRICT_LAZY', false));

        // Un handler enregistré REMPLACE la levée d'exception : ne les brancher qu'en
        // production, sinon le mode strict ne ferait plus échouer la CI.
        if (! $strict) {
            $this->journaliserViolationsEnProduction();
        }
    }

    private function journaliserViolationsEnProduction(): void
    {
        Model::handleDiscardedAttributeViolationUsing(
            fn (Model $model, array $cles) => Log::channel('audit')->critical(
                'Écriture ignorée : attribut hors $fillable',
                $this->contexteViolation($model, ['attributs' => $cles]),
            )
        );

        Model::handleMissingAttributeViolationUsing(
            fn (Model $model, string $cle) => Log::channel('audit')->warning(
                'Lecture d\'un attribut absent du modèle',
                $this->contexteViolation($model, ['attribut' => $cle]),
            )
        );

        Model::handleLazyLoadingViolationUsing(
            fn (Model $model, string $relation) => Log::channel('audit')->info(
                'Chargement différé (N+1 probable)',
                $this->contexteViolation($model, ['relation' => $relation]),
            )
        );
    }

    private function contexteViolation(Model $model, array $extra): array
    {
        return $extra + [
            'modele' => $model::class,
            'url' => request()?->fullUrl(),
            'route' => request()?->route()?->getName(),
            'utilisateur' => auth()->id(),
        ];
    }
}
