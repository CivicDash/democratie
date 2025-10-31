<?php

namespace App\Providers;

use App\Models\Vote;
use App\Models\Topic;
use App\Models\Post;
use App\Observers\VoteObserver;
use App\Observers\TopicObserver;
use App\Observers\PostObserver;
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
        
        // TODO: Enregistrer les observers pour la gamification quand les modèles existent
        // Vote::observe(VoteObserver::class);
        // Topic::observe(TopicObserver::class);
        // Post::observe(PostObserver::class);
    }
}
