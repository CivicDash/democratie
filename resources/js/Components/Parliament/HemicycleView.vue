<template>
    <div class="relative w-full">
        <div class="bg-white dark:bg-gray-800 rounded-lg p-6 shadow-sm border-l-4"
             :class="chamber === 'assembly' ? 'border-blue-600' : 'border-red-600'">
            <!-- Titre + Sélecteur temporel -->
            <div class="mb-4 flex flex-wrap items-center justify-between gap-4">
                <div>
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 flex items-center gap-2">
                        <span :class="chamber === 'assembly' ? 'text-blue-600' : 'text-red-600'">
                            {{ chamber === 'assembly' ? '🏛️' : '🏛️' }}
                        </span>
                        {{ chamber === 'assembly' ? 'Assemblée Nationale' : 'Sénat' }}
                        <span class="text-sm font-normal text-gray-600 dark:text-gray-400">
                            ({{ totalSeats }} {{ chamber === 'assembly' ? 'députés' : 'sénateurs' }})
                        </span>
                    </h3>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                        {{ chamber === 'assembly' ? 'Élus au suffrage universel direct (5 ans)' : 'Élus au suffrage indirect (6 ans)' }}
                    </p>
                </div>

                <!-- Sélecteur temporel -->
                <div v-if="showTimeComparison" class="flex items-center gap-2">
                    <label class="text-sm font-medium text-gray-700 dark:text-gray-300">
                        Législature :
                    </label>
                    <select
                        v-model="selectedLegislature"
                        class="rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                    >
                        <option value="2024">2024 (Actuelle)</option>
                        <option value="2022">2022-2024</option>
                        <option value="2017">2017-2022</option>
                        <option value="2012">2012-2017</option>
                    </select>
                </div>
            </div>

            <!-- Hémicycle SVG -->
            <div class="flex justify-center">
                <img 
                    :src="svgPath" 
                    :alt="`Composition ${chamber === 'assembly' ? 'de l\'Assemblée Nationale' : 'du Sénat'}`"
                    class="w-full max-w-2xl h-auto"
                    style="max-height: 400px;"
                />
            </div>

            <!-- Légende des groupes parlementaires -->
            <div class="mt-6 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                <button
                    v-for="group in parliamentaryGroups"
                    :key="group.id"
                    @click="handleGroupClick(group)"
                    class="flex items-center gap-2 text-sm p-3 border border-gray-200 dark:border-gray-700 rounded-lg hover:shadow-md hover:border-indigo-500 dark:hover:border-indigo-600 transition text-left"
                    :class="{ 'ring-2 ring-indigo-500 dark:ring-indigo-400': selectedGroup?.id === group.id }"
                >
                    <div 
                        class="w-4 h-4 rounded-full flex-shrink-0"
                        :style="{ backgroundColor: group.color }"
                    ></div>
                    <div class="flex-1 min-w-0">
                        <div class="font-medium text-gray-900 dark:text-gray-100 truncate">
                            {{ group.name }}
                        </div>
                        <div class="text-xs text-gray-600 dark:text-gray-400">
                            {{ group.seats }} {{ chamber === 'assembly' ? 'députés' : 'sénateurs' }}
                            <span class="text-gray-500">
                                ({{ ((group.seats / totalSeats) * 100).toFixed(1) }}%)
                            </span>
                        </div>
                    </div>
                    <div class="text-indigo-600 dark:text-indigo-400">
                        →
                    </div>
                </button>
            </div>

            <!-- Stats additionnelles -->
            <div v-if="showStats" class="mt-6 pt-6 border-t border-gray-200 dark:border-gray-700">
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                    <div class="text-center">
                        <div class="text-2xl font-bold text-indigo-600 dark:text-indigo-400">
                            {{ majoritySeats }}
                        </div>
                        <div class="text-xs text-gray-600 dark:text-gray-400 mt-1">
                            Majorité absolue
                        </div>
                    </div>
                    <div class="text-center">
                        <div class="text-2xl font-bold text-red-600 dark:text-red-400">
                            {{ leftSeats }}
                        </div>
                        <div class="text-xs text-gray-600 dark:text-gray-400 mt-1">
                            Gauche
                        </div>
                    </div>
                    <div class="text-center">
                        <div class="text-2xl font-bold text-yellow-600 dark:text-yellow-400">
                            {{ centerSeats }}
                        </div>
                        <div class="text-xs text-gray-600 dark:text-gray-400 mt-1">
                            Centre
                        </div>
                    </div>
                    <div class="text-center">
                        <div class="text-2xl font-bold text-blue-600 dark:text-blue-400">
                            {{ rightSeats }}
                        </div>
                        <div class="text-xs text-gray-600 dark:text-gray-400 mt-1">
                            Droite
                        </div>
                    </div>
                </div>

                <!-- Évolution temporelle (si activée) -->
                <div v-if="showTimeComparison && selectedLegislature !== '2024'" class="mt-4 p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
                    <div class="flex items-start gap-2">
                        <div class="text-2xl">📊</div>
                        <div class="flex-1 text-sm">
                            <div class="font-semibold text-gray-900 dark:text-gray-100 mb-2">
                                Évolution depuis {{ selectedLegislature }}
                            </div>
                            <div class="grid grid-cols-3 gap-2 text-xs text-gray-700 dark:text-gray-300">
                                <div>
                                    <span class="text-red-600">Gauche:</span> 
                                    <span class="font-semibold">{{ getEvolution('left') }}</span>
                                </div>
                                <div>
                                    <span class="text-yellow-600">Centre:</span> 
                                    <span class="font-semibold">{{ getEvolution('center') }}</span>
                                </div>
                                <div>
                                    <span class="text-blue-600">Droite:</span> 
                                    <span class="font-semibold">{{ getEvolution('right') }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed } from 'vue';
import { router } from '@inertiajs/vue3';

const props = defineProps({
    chamber: {
        type: String,
        required: true,
        validator: (value) => ['assembly', 'senate'].includes(value)
    },
    showStats: {
        type: Boolean,
        default: true
    },
    showTimeComparison: {
        type: Boolean,
        default: true
    }
});

const selectedGroup = ref(null);
const selectedLegislature = ref('2024');

// Données Assemblée Nationale par législature
const assemblyData = {
    '2024': [
        { id: 'gdr', name: 'Gauche démocrate et républicaine', color: '#830e21', seats: 17, side: 'left', sigle: 'GDR' },
        { id: 'lfi', name: 'La France insoumise - NFP', color: '#c00d0d', seats: 71, side: 'left', sigle: 'LFI' },
        { id: 'eco', name: 'Écologiste et social', color: '#77aa79', seats: 33, side: 'left', sigle: 'ECO' },
        { id: 'soc', name: 'Socialistes et apparentés', color: '#ff8080', seats: 66, side: 'left', sigle: 'SOC' },
        { id: 'liot', name: 'Libertés, Indépendants, Outre-mer et Territoires', color: '#dddd00', seats: 22, side: 'center', sigle: 'LIOT' },
        { id: 'dem', name: 'Démocrate (MoDem et Indépendants)', color: '#ff9900', seats: 35, side: 'center', sigle: 'DEM' },
        { id: 'ens', name: 'Ensemble pour la République', color: '#ffeb00', seats: 99, side: 'center', sigle: 'ENS' },
        { id: 'hor', name: 'Horizons et apparentés', color: '#0088cc', seats: 34, side: 'center', sigle: 'HOR' },
        { id: 'dr', name: 'Droite républicaine', color: '#0066cc', seats: 47, side: 'right', sigle: 'DR' },
        { id: 'rn', name: 'Rassemblement National', color: '#000055', seats: 143, side: 'right', sigle: 'RN' },
        { id: 'nap', name: 'Non-inscrits', color: '#cccccc', seats: 10, side: 'center', sigle: 'NI' }
    ],
    '2022': [
        { id: 'lfi', name: 'La France insoumise - NUPES', color: '#c00d0d', seats: 75, side: 'left', sigle: 'LFI' },
        { id: 'soc', name: 'Socialistes - NUPES', color: '#ff8080', seats: 31, side: 'left', sigle: 'SOC' },
        { id: 'eco', name: 'Écologiste - NUPES', color: '#77aa79', seats: 23, side: 'left', sigle: 'ECO' },
        { id: 'ens', name: 'Renaissance (Majorité)', color: '#ffeb00', seats: 250, side: 'center', sigle: 'RE' },
        { id: 'lr', name: 'Les Républicains', color: '#0066cc', seats: 64, side: 'right', sigle: 'LR' },
        { id: 'rn', name: 'Rassemblement National', color: '#000055', seats: 89, side: 'right', sigle: 'RN' },
        { id: 'nap', name: 'Non-inscrits', color: '#cccccc', seats: 45, side: 'center', sigle: 'NI' }
    ],
    '2017': [
        { id: 'lfi', name: 'La France insoumise', color: '#c00d0d', seats: 17, side: 'left', sigle: 'LFI' },
        { id: 'soc', name: 'Socialistes', color: '#ff8080', seats: 30, side: 'left', sigle: 'SOC' },
        { id: 'ens', name: 'La République en Marche', color: '#ffeb00', seats: 314, side: 'center', sigle: 'LREM' },
        { id: 'lr', name: 'Les Républicains', color: '#0066cc', seats: 112, side: 'right', sigle: 'LR' },
        { id: 'rn', name: 'Rassemblement National', color: '#000055', seats: 8, side: 'right', sigle: 'RN' },
        { id: 'nap', name: 'Non-inscrits', color: '#cccccc', seats: 96, side: 'center', sigle: 'NI' }
    ],
    '2012': [
        { id: 'soc', name: 'Socialiste', color: '#ff8080', seats: 280, side: 'left', sigle: 'SOC' },
        { id: 'eco', name: 'Écologiste', color: '#77aa79', seats: 17, side: 'left', sigle: 'ECO' },
        { id: 'udi', name: 'UDI', color: '#ff9900', seats: 30, side: 'center', sigle: 'UDI' },
        { id: 'lr', name: 'UMP (Les Républicains)', color: '#0066cc', seats: 194, side: 'right', sigle: 'UMP' },
        { id: 'fn', name: 'Front National', color: '#000055', seats: 2, side: 'right', sigle: 'FN' },
        { id: 'nap', name: 'Non-inscrits', color: '#cccccc', seats: 54, side: 'center', sigle: 'NI' }
    ]
};

// Données Sénat (actuelle)
const senateData = {
    '2024': [
        { id: 'crce', name: 'Communiste Républicain Citoyen et Écologiste', color: '#dd0000', seats: 16, side: 'left', sigle: 'CRCE' },
        { id: 'sos', name: 'Socialiste, Écologiste et Républicain', color: '#ff8080', seats: 65, side: 'left', sigle: 'SER' },
        { id: 'rdse', name: 'RDSE', color: '#ffdd99', seats: 14, side: 'center', sigle: 'RDSE' },
        { id: 'rdpi', name: 'RDPI', color: '#0088cc', seats: 13, side: 'center', sigle: 'RDPI' },
        { id: 'uc', name: 'Union Centriste', color: '#00ccff', seats: 57, side: 'center', sigle: 'UC' },
        { id: 'lr', name: 'Les Républicains', color: '#0066cc', seats: 145, side: 'right', sigle: 'LR' },
        { id: 'ras', name: 'Rassemblement des démocrates', color: '#6699ff', seats: 23, side: 'center', sigle: 'RADEM' },
        { id: 'nap', name: 'Non-inscrits', color: '#cccccc', seats: 15, side: 'center', sigle: 'NI' }
    ]
};

const parliamentaryGroups = computed(() => {
    const data = props.chamber === 'assembly' ? assemblyData : senateData;
    return data[selectedLegislature.value] || data['2024'];
});

const totalSeats = computed(() => {
    return parliamentaryGroups.value.reduce((sum, group) => sum + group.seats, 0);
});

const majoritySeats = computed(() => {
    return Math.floor(totalSeats.value / 2) + 1;
});

const leftSeats = computed(() => {
    return parliamentaryGroups.value
        .filter(g => g.side === 'left')
        .reduce((sum, group) => sum + group.seats, 0);
});

const centerSeats = computed(() => {
    return parliamentaryGroups.value
        .filter(g => g.side === 'center')
        .reduce((sum, group) => sum + group.seats, 0);
});

const rightSeats = computed(() => {
    return parliamentaryGroups.value
        .filter(g => g.side === 'right')
        .reduce((sum, group) => sum + group.seats, 0);
});

const svgPath = computed(() => {
    return props.chamber === 'assembly' 
        ? '/images/Composition_de_l\'Assemblée_nationale.svg'
        : '/images/Sénat_français_2023.svg';
});

const getEvolution = (side) => {
    const current = props.chamber === 'assembly' ? assemblyData['2024'] : senateData['2024'];
    const previous = props.chamber === 'assembly' 
        ? assemblyData[selectedLegislature.value] 
        : senateData['2024'];
    
    const currentSeats = current.filter(g => g.side === side).reduce((sum, g) => sum + g.seats, 0);
    const previousSeats = previous.filter(g => g.side === side).reduce((sum, g) => sum + g.seats, 0);
    
    const diff = currentSeats - previousSeats;
    return diff > 0 ? `+${diff}` : diff.toString();
};

const handleGroupClick = (group) => {
    selectedGroup.value = selectedGroup.value?.id === group.id ? null : group;
    
    // Rediriger vers la liste des députés/sénateurs du groupe
    const source = props.chamber === 'assembly' ? 'assemblee' : 'senat';
    const url = route('representants.deputes.index', {
        groupe: group.sigle,
        source: source
    });
    
    router.visit(url);
};
</script>


