<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import TextInput from '@/Components/TextInput.vue';
import InputLabel from '@/Components/InputLabel.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';

const props = defineProps({
    stats: Object,
    canLogin: Boolean,
    canRegister: Boolean,
    status: String,
});

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const submit = () => {
    form.post(route('login'), {
        onFinish: () => form.reset('password'),
    });
};

// Remplir avec compte démo citoyen
const fillDemoAccount = () => {
    form.email = 'demo@civicdash.fr';
    form.password = 'Demo2026!';
};

// Remplir avec compte démo élu
const fillDemoEluAccount = () => {
    form.email = 'demo-elu@civicdash.fr';
    form.password = 'DemoElu2026!';
};
</script>

<template>
    <Head title="Bienvenue - CivicDash" />
    
    <div class="min-h-screen bg-gradient-to-br from-slate-900 via-slate-800 to-indigo-900 flex">
        <!-- Partie gauche : Explication du projet -->
        <div class="hidden lg:flex lg:w-1/2 xl:w-3/5 flex-col justify-center px-12 xl:px-20">
            <!-- Logo et titre -->
            <div class="mb-12">
                <div class="flex items-center gap-4 mb-6">
                    <div class="w-16 h-16 rounded-2xl bg-gradient-to-br from-amber-400 to-orange-500 flex items-center justify-center text-3xl shadow-lg">
                        🏛️
                    </div>
                    <div>
                        <h1 class="text-4xl font-bold text-white">CivicDash</h1>
                        <p class="text-slate-400">Plateforme citoyenne</p>
                    </div>
                </div>
                
                <h2 class="text-3xl xl:text-4xl font-bold text-white leading-tight mb-4">
                    Comprenez et participez à la 
                    <span class="bg-gradient-to-r from-amber-400 to-orange-500 bg-clip-text text-transparent">
                        démocratie française
                    </span>
                </h2>
                
                <p class="text-xl text-slate-300 leading-relaxed">
                    Suivez vos représentants, explorez les lois en cours, interpellez vos élus et participez au débat citoyen.
                </p>
            </div>
            
            <!-- Stats clés -->
            <div class="grid grid-cols-2 xl:grid-cols-4 gap-4 mb-12">
                <div class="bg-white/10 backdrop-blur-sm rounded-xl p-4 border border-white/10">
                    <div class="text-3xl font-bold text-white">{{ stats?.deputes || 577 }}</div>
                    <div class="text-slate-400 text-sm">Députés</div>
                </div>
                <div class="bg-white/10 backdrop-blur-sm rounded-xl p-4 border border-white/10">
                    <div class="text-3xl font-bold text-white">{{ stats?.senateurs || 348 }}</div>
                    <div class="text-slate-400 text-sm">Sénateurs</div>
                </div>
                <div class="bg-white/10 backdrop-blur-sm rounded-xl p-4 border border-white/10">
                    <div class="text-3xl font-bold text-white">{{ stats?.maires || '35K+' }}</div>
                    <div class="text-slate-400 text-sm">Maires</div>
                </div>
                <div class="bg-white/10 backdrop-blur-sm rounded-xl p-4 border border-white/10">
                    <div class="text-3xl font-bold text-white">{{ stats?.lois_en_cours || '100+' }}</div>
                    <div class="text-slate-400 text-sm">Lois en cours</div>
                </div>
            </div>
            
            <!-- Fonctionnalités -->
            <div class="space-y-4">
                <div class="flex items-start gap-4">
                    <div class="w-10 h-10 rounded-lg bg-sky-500/20 flex items-center justify-center text-xl flex-shrink-0">
                        👥
                    </div>
                    <div>
                        <h3 class="text-white font-semibold">Suivez vos représentants</h3>
                        <p class="text-slate-400 text-sm">Consultez les votes, amendements et activités de vos élus.</p>
                    </div>
                </div>
                <div class="flex items-start gap-4">
                    <div class="w-10 h-10 rounded-lg bg-amber-500/20 flex items-center justify-center text-xl flex-shrink-0">
                        📜
                    </div>
                    <div>
                        <h3 class="text-white font-semibold">Explorez les lois</h3>
                        <p class="text-slate-400 text-sm">Suivez le parcours législatif et votez sur les textes.</p>
                    </div>
                </div>
                <div class="flex items-start gap-4">
                    <div class="w-10 h-10 rounded-lg bg-emerald-500/20 flex items-center justify-center text-xl flex-shrink-0">
                        💡
                    </div>
                    <div>
                        <h3 class="text-white font-semibold">Participez au débat</h3>
                        <p class="text-slate-400 text-sm">Proposez des idées et interpellez vos élus.</p>
                    </div>
                </div>
            </div>
            
            <!-- Lien vers le site vitrine -->
            <div class="mt-12 pt-8 border-t border-white/10">
                <p class="text-slate-400 text-sm">
                    En savoir plus sur le projet ? Visitez 
                    <a href="https://objectif2027.fr" target="_blank" class="text-amber-400 hover:text-amber-300 underline">
                        objectif2027.fr
                    </a>
                </p>
            </div>
        </div>
        
        <!-- Partie droite : Formulaire de connexion -->
        <div class="w-full lg:w-1/2 xl:w-2/5 flex items-center justify-center p-6 lg:p-12">
            <div class="w-full max-w-md">
                <!-- Logo mobile -->
                <div class="lg:hidden text-center mb-8">
                    <div class="inline-flex items-center gap-3 mb-4">
                        <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-amber-400 to-orange-500 flex items-center justify-center text-2xl">
                            🏛️
                        </div>
                        <span class="text-2xl font-bold text-white">CivicDash</span>
                    </div>
                    <p class="text-slate-400">Plateforme citoyenne de transparence démocratique</p>
                </div>
                
                <!-- Card de connexion -->
                <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl p-8">
                    <div class="text-center mb-6">
                        <h2 class="text-2xl font-bold text-gray-900 dark:text-white">
                            Connexion
                        </h2>
                        <p class="text-gray-600 dark:text-gray-400 mt-2">
                            Accédez à votre espace citoyen
                        </p>
                    </div>
                    
                    <!-- Status message -->
                    <div v-if="status" class="mb-4 p-3 bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-200 dark:border-emerald-800 rounded-lg text-sm text-emerald-600 dark:text-emerald-400">
                        {{ status }}
                    </div>
                    
                    <form @submit.prevent="submit" class="space-y-4">
                        <div>
                            <InputLabel for="email" value="Email" />
                            <TextInput
                                id="email"
                                type="email"
                                class="mt-1 block w-full"
                                v-model="form.email"
                                required
                                autofocus
                                autocomplete="username"
                            />
                            <InputError class="mt-2" :message="form.errors.email" />
                        </div>

                        <div>
                            <InputLabel for="password" value="Mot de passe" />
                            <TextInput
                                id="password"
                                type="password"
                                class="mt-1 block w-full"
                                v-model="form.password"
                                required
                                autocomplete="current-password"
                            />
                            <InputError class="mt-2" :message="form.errors.password" />
                        </div>

                        <div class="flex items-center justify-between">
                            <label class="flex items-center">
                                <input
                                    type="checkbox"
                                    class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500"
                                    v-model="form.remember"
                                />
                                <span class="ml-2 text-sm text-gray-600 dark:text-gray-400">Se souvenir de moi</span>
                            </label>
                            
                            <Link
                                :href="route('password.request')"
                                class="text-sm text-indigo-600 hover:text-indigo-500 dark:text-indigo-400"
                            >
                                Mot de passe oublié ?
                            </Link>
                        </div>

                        <PrimaryButton
                            class="w-full justify-center py-3"
                            :class="{ 'opacity-25': form.processing }"
                            :disabled="form.processing"
                        >
                            Se connecter
                        </PrimaryButton>
                    </form>
                    
                    <!-- Séparateur -->
                    <div class="relative my-6">
                        <div class="absolute inset-0 flex items-center">
                            <div class="w-full border-t border-gray-300 dark:border-gray-600"></div>
                        </div>
                        <div class="relative flex justify-center text-sm">
                            <span class="px-2 bg-white dark:bg-gray-800 text-gray-500">ou essayez</span>
                        </div>
                    </div>
                    
                    <!-- Boutons démo -->
                    <div class="space-y-3">
                        <button
                            type="button"
                            @click="fillDemoAccount"
                            class="w-full flex items-center justify-center gap-2 px-4 py-3 bg-gradient-to-r from-emerald-500 to-teal-600 hover:from-emerald-600 hover:to-teal-700 text-white font-semibold rounded-lg transition-all"
                        >
                            <span>👤</span>
                            Compte démo citoyen
                        </button>
                        <button
                            type="button"
                            @click="fillDemoEluAccount"
                            class="w-full flex items-center justify-center gap-2 px-4 py-3 bg-gradient-to-r from-purple-600 to-violet-600 hover:from-purple-700 hover:to-violet-700 text-white font-semibold rounded-lg transition-all"
                        >
                            <span>🏛️</span>
                            Compte démo élu
                        </button>
                    </div>
                    
                    <!-- Lien inscription -->
                    <p v-if="canRegister" class="mt-6 text-center text-sm text-gray-600 dark:text-gray-400">
                        Pas encore de compte ?
                        <Link :href="route('register')" class="text-indigo-600 hover:text-indigo-500 dark:text-indigo-400 font-medium">
                            Créer un compte
                        </Link>
                    </p>
                </div>
                
                <!-- Mention RGPD -->
                <p class="mt-6 text-center text-xs text-slate-400">
                    En vous connectant, vous acceptez nos 
                    <a href="/terms" class="underline hover:no-underline">conditions d'utilisation</a>
                    et notre 
                    <a href="/privacy" class="underline hover:no-underline">politique de confidentialité</a>.
                </p>
            </div>
        </div>
    </div>
</template>
