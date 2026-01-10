<?php

namespace App\Providers;

use App\Listeners\LogSentEmail;
use App\Models\Vote;
use App\Models\Topic;
use App\Models\Post;
use App\Models\ListeElectorale;
use App\Observers\VoteObserver;
use App\Observers\TopicObserver;
use App\Observers\PostObserver;
use App\Observers\PostHashtagObserver;
use App\Observers\TopicHashtagObserver;
use App\Observers\ListeElectoraleObserver;
use Illuminate\Mail\Events\MessageSent;
use Illuminate\Support\Facades\Event;
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
        
        // Observers hashtags (auto-extraction)
        Post::observe(PostHashtagObserver::class);
        Topic::observe(TopicHashtagObserver::class);
        
        // Observer pour les notifications de candidatures municipales
        ListeElectorale::observe(ListeElectoraleObserver::class);
        
        // Listener pour logger les emails envoyés
        Event::listen(MessageSent::class, LogSentEmail::class);
        
        // TODO: Enregistrer les observers pour la gamification quand les modèles existent
        // Vote::observe(VoteObserver::class);
        // Topic::observe(TopicObserver::class);
        // Post::observe(PostObserver::class);
    }
}
