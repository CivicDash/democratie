<script setup>
import { ref, computed } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import MainLayout from '@/Layouts/MainLayout.vue';
import Card from '@/Components/Card.vue';
import Badge from '@/Components/Badge.vue';
import Alert from '@/Components/Alert.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';

const props = defineProps({
    sectors: Array,
    userAllocations: Object,
    averages: Object,
    stats: Object,
});

const allocations = ref({});

// Initialize with user's existing allocations or zeros
props.sectors.forEach(sector => {
    const existing = props.userAllocations?.allocations?.[sector.id];
    allocations.value[sector.id] = existing ? parseFloat(existing) : 0;
});

const totalAllocated = computed(() => {
    return Object.values(allocations.value).reduce((sum, val) => sum + parseFloat(val || 0), 0);
});

const remainingBudget = computed(() => {
    return 100 - totalAllocated.value;
});

const isValid = computed(() => {
    return Math.abs(totalAllocated.value - 100) < 0.01; // Allow for floating point errors
});

const form = useForm({
    allocations: allocations,
});

const submit = () => {
    form.post(route('budget.allocate'), {
        onSuccess: () => {
            // Success handled by flash message
        },
    });
};

const resetAllocations = () => {
    if (confirm('Êtes-vous sûr de vouloir réinitialiser votre allocation ?')) {
        form.delete(route('budget.reset'), {
            onSuccess: () => {
                props.sectors.forEach(sector => {
                    allocations.value[sector.id] = 0;
                });
            },
        });
    }
};

const useAverages = () => {
    if (confirm('Utiliser les allocations moyennes de la communauté ?')) {
        props.sectors.forEach(sector => {
            allocations.value[sector.id] = parseFloat(props.averages[sector.id] || 0);
        });
    }
};

const distributeEqually = () => {
    const perSector = (100 / props.sectors.length).toFixed(2);
    props.sectors.forEach(sector => {
        allocations.value[sector.id] = parseFloat(perSector);
    });
};

const getSectorIcon = (name) => {
    const icons = {
        'Santé': '🏥',
        'Éducation': '🎓',
        'Transport': '🚇',
        'Écologie': '🌱',
        'Sécurité': '👮',
        'Culture': '🎭',
        'Logement': '🏠',
        'Emploi': '💼',
        'Justice': '⚖️',
        'Sport': '⚽',
    };
    return icons[name] || '📊';
};

const getComparisonBadge = (sectorId) => {
    const userAlloc = parseFloat(allocations.value[sectorId] || 0);
    const avgAlloc = parseFloat(props.averages[sectorId] || 0);
    const diff = userAlloc - avgAlloc;
    
    if (Math.abs(diff) < 1) return { variant: 'gray', text: '≈ Moyenne' };
    if (diff > 5) return { variant: 'green', text: `+${diff.toFixed(1)}% vs moy.` };
    if (diff > 0) return { variant: 'blue', text: `+${diff.toFixed(1)}% vs moy.` };
    if (diff < -5) return { variant: 'red', text: `${diff.toFixed(1)}% vs moy.` };
    return { variant: 'yellow', text: `${diff.toFixed(1)}% vs moy.` };
};
</script>

<template>
    <Head title="Budget Participatif" />

    <MainLayout title="Budget Participatif">
        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <!-- Header -->
                <div class="mb-8">
                    <h1 class="text-3xl font-bold text-gray-900 dark:text-gray-100 mb-2">
                        💰 Budget Participatif
                    </h1>
                    <p class="text-gray-600 dark:text-gray-400">
                        Répartissez 100% du budget entre les différents secteurs selon vos priorités
                    </p>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <!-- Main Allocation Form -->
                    <div class="lg:col-span-2 space-y-6">
                        <!-- Status Alert -->
                        <Alert v-if="!isValid && totalAllocated > 0" type="warning">
                            <strong>⚠️ Allocation invalide</strong><br>
                            Total alloué: <strong>{{ totalAllocated.toFixed(2) }}%</strong><br>
                            Vous devez allouer exactement 100%.
                        </Alert>

                        <Alert v-else-if="isValid" type="success">
                            ✅ Allocation valide (100%)
                        </Alert>

                        <!-- Sectors List -->
                        <Card>
                            <div class="mb-4 flex justify-between items-center">
                                <h2 class="text-xl font-semibold text-gray-900 dark:text-gray-100">
                                    Secteurs Budgétaires
                                </h2>
                                <div class="flex gap-2">
                                    <SecondaryButton @click="distributeEqually" type="button" class="text-xs">
                                        ⚖️ Égaliser
                                    </SecondaryButton>
                                    <SecondaryButton @click="useAverages" type="button" class="text-xs">
                                        📊 Moyennes
                                    </SecondaryButton>
                                </div>
                            </div>

                            <form @submit.prevent="submit" class="space-y-4">
                                <div v-for="sector in sectors" :key="sector.id" 
                                    class="p-4 border border-gray-200 dark:border-gray-700 rounded-lg">
                                    <div class="flex items-center justify-between mb-3">
                                        <div class="flex-1">
                                            <div class="flex items-center gap-2 mb-1">
                                                <span class="text-2xl">{{ getSectorIcon(sector.name) }}</span>
                                                <h3 class="font-semibold text-gray-900 dark:text-gray-100">
                                                    {{ sector.name }}
                                                </h3>
                                                <Badge v-if="averages[sector.id]" :variant="getComparisonBadge(sector.id).variant" size="sm">
                                                    {{ getComparisonBadge(sector.id).text }}
                                                </Badge>
                                            </div>
                                            <p class="text-sm text-gray-600 dark:text-gray-400">
                                                {{ sector.description }}
                                            </p>
                                        </div>
                                        <div class="flex items-center gap-3 ml-4">
                                            <input 
                                                type="number"
                                                v-model="allocations[sector.id]"
                                                step="0.1"
                                                min="0"
                                                max="100"
                                                class="w-20 text-right border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm"
                                            >
                                            <span class="text-gray-600 dark:text-gray-400 font-medium">%</span>
                                        </div>
                                    </div>
                                    <div class="h-2 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden">
                                        <div class="h-full bg-indigo-500 transition-all duration-300" 
                                            :style="{ width: `${allocations[sector.id]}%` }">
                                        </div>
                                    </div>
                                </div>

                                <div class="pt-4 border-t border-gray-200 dark:border-gray-700 flex justify-between items-center">
                                    <div>
                                        <PrimaryButton type="submit" :disabled="form.processing || !isValid">
                                            {{ form.processing ? 'Enregistrement...' : '💾 Enregistrer mon allocation' }}
                                        </PrimaryButton>
                                        <SecondaryButton v-if="userAllocations" @click="resetAllocations" type="button" class="ml-3">
                                            🔄 Réinitialiser
                                        </SecondaryButton>
                                    </div>
                                    <div class="text-right">
                                        <div class="text-sm text-gray-600 dark:text-gray-400">Total alloué</div>
                                        <div class="text-2xl font-bold" :class="isValid ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400'">
                                            {{ totalAllocated.toFixed(2) }}%
                                        </div>
                                    </div>
                                </div>
                            </form>
                        </Card>
                    </div>

                    <!-- Sidebar Stats -->
                    <div class="space-y-6">
                        <!-- Budget Remaining -->
                        <Card>
                            <h3 class="font-semibold text-gray-900 dark:text-gray-100 mb-3">
                                📊 État de l'allocation
                            </h3>
                            <div class="space-y-3">
                                <div>
                                    <div class="text-sm text-gray-600 dark:text-gray-400 mb-1">Alloué</div>
                                    <div class="text-2xl font-bold text-gray-900 dark:text-gray-100">
                                        {{ totalAllocated.toFixed(2) }}%
                                    </div>
                                </div>
                                <div>
                                    <div class="text-sm text-gray-600 dark:text-gray-400 mb-1">Restant</div>
                                    <div class="text-2xl font-bold" :class="remainingBudget >= 0 ? 'text-gray-900 dark:text-gray-100' : 'text-red-600 dark:text-red-400'">
                                        {{ remainingBudget.toFixed(2) }}%
                                    </div>
                                </div>
                            </div>
                        </Card>

                        <!-- Community Stats -->
                        <Card v-if="stats">
                            <h3 class="font-semibold text-gray-900 dark:text-gray-100 mb-3">
                                👥 Statistiques Communauté
                            </h3>
                            <div class="space-y-3 text-sm">
                                <div class="flex justify-between">
                                    <span class="text-gray-600 dark:text-gray-400">Participants</span>
                                    <span class="font-semibold text-gray-900 dark:text-gray-100">
                                        {{ stats.total_participants || 0 }}
                                    </span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600 dark:text-gray-400">Dernière maj.</span>
                                    <span class="font-semibold text-gray-900 dark:text-gray-100">
                                        {{ stats.last_updated ? new Date(stats.last_updated).toLocaleDateString('fr-FR') : 'N/A' }}
                                    </span>
                                </div>
                            </div>
                        </Card>

                        <!-- Info -->
                        <Alert type="info">
                            <strong>ℹ️ Comment ça marche ?</strong><br>
                            <ul class="mt-2 space-y-1 text-sm">
                                <li>• Répartissez exactement 100%</li>
                                <li>• Vos choix restent anonymes</li>
                                <li>• Influence les priorités</li>
                                <li>• Modifiable à tout moment</li>
                            </ul>
                        </Alert>
                    </div>
                </div>
            </div>
        </div>
    </MainLayout>
</template>

