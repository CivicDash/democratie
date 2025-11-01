<?php

namespace App\Providers;

use App\Models\Vote;
use App\Models\Topic;
use App\Models\Post;
use App\Observers\VoteObserver;
use App\Observers\TopicObserver;
use App\Observers\PostObserver;
use App\Observers\PostHashtagObserver;
use App\Observers\TopicHashtagObserver;
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
        
        // TODO: Enregistrer les observers pour la gamification quand les modèles existent
        // Vote::observe(VoteObserver::class);
        // Topic::observe(TopicObserver::class);
        // Post::observe(PostObserver::class);
    }
}
