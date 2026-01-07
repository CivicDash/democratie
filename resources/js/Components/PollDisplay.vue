<script setup>
import { ref, computed } from 'vue';
import { router, usePage } from '@inertiajs/vue3';

const props = defineProps({
    topicId: { type: [Number, String], required: true },
    options: { type: Array, default: () => [] },
    pollType: { type: String, default: 'single' },
    pollEndsAt: { type: String, default: null },
    userVotes: { type: Array, default: () => [] },
    totalVotes: { type: Number, default: 0 },
    isActive: { type: Boolean, default: true },
});

const emit = defineEmits(['voted']);
const page = usePage();
const isAuthenticated = computed(() => !!page.props.auth?.user);

const selectedOptions = ref([...props.userVotes]);
const isSubmitting = ref(false);
const hasVoted = computed(() => props.userVotes.length > 0);
const showResults = computed(() => hasVoted.value || !props.isActive);

function getPercentage(votesCount) {
    if (props.totalVotes === 0) return 0;
    return Math.round((votesCount / props.totalVotes) * 100);
}

function toggleOption(optionId) {
    if (!props.isActive || hasVoted.value) return;
    
    if (props.pollType === 'single') {
        selectedOptions.value = [optionId];
    } else {
        const index = selectedOptions.value.indexOf(optionId);
        if (index > -1) {
            selectedOptions.value.splice(index, 1);
        } else {
            selectedOptions.value.push(optionId);
        }
    }
}

async function submitVote() {
    if (!isAuthenticated.value || selectedOptions.value.length === 0) return;
    
    isSubmitting.value = true;
    
    router.post(`/api/topics/${props.topicId}/poll/vote`, {
        option_ids: selectedOptions.value,
    }, {
        preserveScroll: true,
        onSuccess: () => emit('voted', selectedOptions.value),
        onFinish: () => isSubmitting.value = false,
    });
}

const colors = ['bg-emerald-500', 'bg-blue-500', 'bg-amber-500', 'bg-rose-500', 'bg-violet-500'];
const getColor = (i) => colors[i % colors.length];

const endsAtFormatted = computed(() => {
    if (!props.pollEndsAt) return null;
    const date = new Date(props.pollEndsAt);
    return date.toLocaleDateString('fr-FR', { day: 'numeric', month: 'long', year: 'numeric' });
});
</script>

<template>
    <div class="poll-display rounded-2xl border border-gray-200 dark:border-gray-700 overflow-hidden shadow-lg">
        <!-- En-tête -->
        <div class="px-6 py-4 bg-gradient-to-r from-indigo-500 to-purple-600 text-white flex justify-between items-center">
            <div class="flex items-center gap-2">
                <span class="text-2xl">📊</span>
                <span class="font-semibold">Sondage</span>
                <span v-if="!isActive" class="ml-2 text-xs bg-white/20 px-2 py-0.5 rounded">🔒 Terminé</span>
            </div>
            <div class="text-right">
                <div class="text-sm opacity-90">{{ totalVotes }} vote{{ totalVotes !== 1 ? 's' : '' }}</div>
                <div v-if="endsAtFormatted && isActive" class="text-xs opacity-70">Jusqu'au {{ endsAtFormatted }}</div>
            </div>
        </div>

        <!-- Options -->
        <div class="p-6 space-y-3">
            <div
                v-for="(option, index) in options"
                :key="option.id"
                @click="toggleOption(option.id)"
                class="relative rounded-xl border-2 transition-all overflow-hidden"
                :class="[
                    selectedOptions.includes(option.id) ? 'border-indigo-500 bg-indigo-50 dark:bg-indigo-900/20' : 'border-gray-200 dark:border-gray-700 hover:border-gray-300',
                    (isActive && !hasVoted) ? 'cursor-pointer' : 'cursor-default'
                ]"
            >
                <!-- Barre de progression -->
                <div v-if="showResults" class="absolute inset-0 transition-all duration-500" :class="getColor(index)" :style="{ width: getPercentage(option.votes_count) + '%', opacity: 0.15 }"></div>
                
                <div class="relative flex items-center gap-4 p-4">
                    <!-- Checkbox -->
                    <div class="w-6 h-6 rounded-full border-2 flex items-center justify-center transition-colors" :class="selectedOptions.includes(option.id) ? 'border-indigo-500 bg-indigo-500' : 'border-gray-300 dark:border-gray-600'">
                        <svg v-if="selectedOptions.includes(option.id)" class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
                        </svg>
                    </div>
                    
                    <!-- Label -->
                    <div class="flex-1 flex items-center gap-2">
                        <span v-if="option.icon" class="text-xl">{{ option.icon }}</span>
                        <span class="font-medium text-gray-800 dark:text-gray-200">{{ option.label }}</span>
                    </div>
                    
                    <!-- Résultats -->
                    <div v-if="showResults" class="flex items-center gap-2">
                        <span class="text-sm text-gray-500">{{ option.votes_count }}</span>
                        <span class="font-bold" :class="getColor(index).replace('bg-', 'text-')">{{ getPercentage(option.votes_count) }}%</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Actions -->
        <div class="px-6 pb-6">
            <button v-if="!hasVoted && isActive && isAuthenticated" @click="submitVote" :disabled="selectedOptions.length === 0 || isSubmitting" class="w-full py-3 rounded-xl font-semibold text-white transition-all" :class="selectedOptions.length > 0 ? 'bg-indigo-600 hover:bg-indigo-700' : 'bg-gray-300 cursor-not-allowed'">
                {{ isSubmitting ? '⏳ Envoi...' : (selectedOptions.length === 0 ? 'Sélectionnez une option' : '✅ Valider mon vote') }}
            </button>
            
            <div v-else-if="hasVoted" class="text-center">
                <span class="inline-flex items-center gap-2 px-4 py-2 bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-400 rounded-xl">✅ Vous avez voté</span>
            </div>
            
            <button v-else-if="!isAuthenticated && isActive" @click="router.visit('/login')" class="w-full py-3 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl font-semibold">
                🔐 Connectez-vous pour voter
            </button>
            
            <p class="mt-3 text-center text-xs text-gray-500">{{ pollType === 'multiple' ? 'Choix multiple' : 'Choix unique' }}</p>
        </div>
    </div>
</template>
