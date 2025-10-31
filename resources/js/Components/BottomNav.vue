<template>
    <nav class="bottom-nav">
        <Link 
            :href="route('dashboard')" 
            class="nav-item"
            :class="{ 'active': route().current('dashboard') }"
        >
            <div class="nav-icon">🏛️</div>
            <span class="nav-label">Accueil</span>
        </Link>

        <Link 
            :href="route('legislation.index')" 
            class="nav-item"
            :class="{ 'active': route().current('legislation.*') }"
        >
            <div class="nav-icon">📜</div>
            <span class="nav-label">Lois</span>
        </Link>

        <Link 
            :href="route('topics.index')" 
            class="nav-item"
            :class="{ 'active': route().current('topics.*') }"
        >
            <div class="nav-icon">💬</div>
            <span class="nav-label">Débats</span>
        </Link>

        <Link 
            :href="route('search.results')" 
            class="nav-item"
            :class="{ 'active': route().current('search.*') }"
        >
            <div class="nav-icon">🔍</div>
            <span class="nav-label">Recherche</span>
        </Link>

        <Link 
            :href="route('profile.edit')" 
            class="nav-item"
            :class="{ 'active': route().current('profile.*') }"
        >
            <div class="nav-icon">👤</div>
            <span class="nav-label">Profil</span>
        </Link>
    </nav>
</template>

<script setup>
import { Link } from '@inertiajs/vue3';
</script>

<style scoped>
.bottom-nav {
    position: fixed;
    bottom: 0;
    left: 0;
    right: 0;
    z-index: 50;
    display: none; /* Hidden by default, shown on mobile */
    background: white;
    border-top: 1px solid #E5E7EB;
    padding: 8px 0 max(8px, env(safe-area-inset-bottom));
    box-shadow: 0 -2px 10px rgba(0, 0, 0, 0.05);
}

@media (max-width: 768px) {
    .bottom-nav {
        display: flex;
        justify-content: space-around;
        align-items: center;
    }
}

.nav-item {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    flex: 1;
    padding: 8px 4px;
    text-decoration: none;
    color: #6B7280;
    transition: all 0.2s;
    border-radius: 12px;
    margin: 0 4px;
    min-width: 60px;
    -webkit-tap-highlight-color: transparent;
}

.nav-item:active {
    transform: scale(0.95);
    background: #F3F4F6;
}

.nav-item.active {
    color: #4F46E5;
}

.nav-item.active .nav-icon {
    transform: scale(1.1);
}

.nav-icon {
    font-size: 24px;
    margin-bottom: 4px;
    transition: transform 0.2s;
}

.nav-item.active .nav-icon {
    animation: bounce 0.5s;
}

@keyframes bounce {
    0%, 100% { transform: scale(1); }
    50% { transform: scale(1.2); }
}

.nav-label {
    font-size: 11px;
    font-weight: 600;
    white-space: nowrap;
}

/* Dark mode */
@media (prefers-color-scheme: dark) {
    .bottom-nav {
        background: #1F2937;
        border-top-color: #374151;
    }
    
    .nav-item {
        color: #9CA3AF;
    }
    
    .nav-item.active {
        color: #818CF8;
    }
    
    .nav-item:active {
        background: #374151;
    }
}

/* Add padding to body when bottom nav is visible */
@media (max-width: 768px) {
    :global(body) {
        padding-bottom: 72px;
    }
}
</style>

