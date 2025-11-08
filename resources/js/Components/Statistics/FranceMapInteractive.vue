<template>
    <div class="relative w-full bg-white dark:bg-gray-800 rounded-lg p-4">
        <!-- Légende + Filtres -->
        <div class="mb-4 space-y-3">
            <div class="flex flex-wrap gap-2 items-center justify-between">
                <div class="text-sm font-medium text-gray-700 dark:text-gray-300">
                    Carte de France interactive
                </div>
                <div v-if="selectedDepartment" class="text-sm text-gray-600 dark:text-gray-400">
                    <span class="font-semibold">{{ selectedDepartment.name }}</span>
                    <button
                        @click="selectedDepartment = null"
                        class="ml-2 text-indigo-600 dark:text-indigo-400 hover:underline"
                    >
                        ✕ Fermer
                    </button>
                </div>
            </div>

            <!-- Filtres par région -->
            <div class="flex flex-wrap gap-2 items-center">
                <span class="text-xs font-medium text-gray-600 dark:text-gray-400">
                    Filtrer par région :
                </span>
                <button
                    @click="selectedRegionFilter = null"
                    class="px-3 py-1 text-xs rounded-full transition"
                    :class="selectedRegionFilter === null 
                        ? 'bg-indigo-600 text-white' 
                        : 'bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-300 dark:hover:bg-gray-600'"
                >
                    Toutes
                </button>
                <button
                    v-for="region in regions"
                    :key="region.code"
                    @click="selectedRegionFilter = region.code"
                    class="px-3 py-1 text-xs rounded-full transition"
                    :class="selectedRegionFilter === region.code 
                        ? 'bg-indigo-600 text-white' 
                        : 'bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-300 dark:hover:bg-gray-600'"
                >
                    {{ region.name }}
                </button>
            </div>
        </div>

        <!-- Carte SVG -->
        <div class="relative" style="max-width: 100%; margin: 0 auto;">
            <svg 
                xmlns="http://www.w3.org/2000/svg" 
                viewBox="0 0 907 1000"
                class="w-full h-auto"
                style="max-height: 600px;"
            >
                <!-- Ligne de séparation Corse -->
                <g fill="none" stroke="#86aae0" stroke-width="1.5">
                    <path d="M783 1000v-130l119-78"/>
                </g>

                <!-- Départements -->
                <g>
                    <path
                        v-for="dept in visibleDepartments"
                        :key="dept.id"
                        :id="dept.id"
                        :d="dept.path"
                        :fill="getDepartmentColor(dept.id)"
                        stroke="#fff"
                        stroke-width="0.5"
                        class="department-path"
                        @click="handleDepartmentClick(dept)"
                        @mouseenter="hoveredDepartment = dept.id"
                        @mouseleave="hoveredDepartment = null"
                        :style="{
                            cursor: 'pointer',
                            opacity: hoveredDepartment === dept.id ? 0.8 : 1,
                            transition: 'all 0.2s ease'
                        }"
                    />
                </g>
            </svg>

            <!-- Tooltip hover -->
            <div
                v-if="hoveredDepartment"
                class="absolute bg-gray-900 dark:bg-gray-700 text-white text-sm px-3 py-2 rounded shadow-lg pointer-events-none z-10"
                style="top: 20px; left: 50%; transform: translateX(-50%);"
            >
                {{ getDepartmentName(hoveredDepartment) }}
            </div>
        </div>

        <!-- Statistiques de filtrage -->
        <div v-if="selectedRegionFilter" class="mt-4 p-3 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
            <div class="flex items-center gap-2 text-sm">
                <div class="text-blue-600 dark:text-blue-400 text-lg">🗺️</div>
                <div>
                    <span class="font-semibold text-gray-900 dark:text-gray-100">
                        {{ regions.find(r => r.code === selectedRegionFilter)?.name }}
                    </span>
                    <span class="text-gray-600 dark:text-gray-400 ml-2">
                        {{ visibleDepartments.length }} département(s) affiché(s)
                    </span>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed } from 'vue';

const props = defineProps({
    regionalData: {
        type: Array,
        default: () => []
    },
    heatmapMetric: {
        type: String,
        default: 'unemployment_rate'
    }
});

const emit = defineEmits(['department-selected']);

const selectedDepartment = ref(null);
const hoveredDepartment = ref(null);
const selectedRegionFilter = ref(null);

// Liste des régions françaises
const regions = [
    { code: '84', name: 'Auvergne-Rhône-Alpes' },
    { code: '27', name: 'Bourgogne-Franche-Comté' },
    { code: '53', name: 'Bretagne' },
    { code: '24', name: 'Centre-Val de Loire' },
    { code: '94', name: 'Corse' },
    { code: '44', name: 'Grand Est' },
    { code: '32', name: 'Hauts-de-France' },
    { code: '11', name: 'Île-de-France' },
    { code: '28', name: 'Normandie' },
    { code: '75', name: 'Nouvelle-Aquitaine' },
    { code: '76', name: 'Occitanie' },
    { code: '52', name: 'Pays de la Loire' },
    { code: '93', name: 'Provence-Alpes-Côte d\'Azur' }
];

// Mapping départements -> régions
const departmentToRegion = {
    '01': '84', '03': '84', '07': '84', '15': '84', '26': '84', '38': '84', '42': '84', '43': '84', '63': '84', '69': '84', '73': '84', '74': '84',
    '21': '27', '25': '27', '39': '27', '58': '27', '70': '27', '71': '27', '89': '27', '90': '27',
    '22': '53', '29': '53', '35': '53', '56': '53',
    '18': '24', '28': '24', '36': '24', '37': '24', '41': '24', '45': '24',
    '2A': '94', '2B': '94',
    '08': '44', '10': '44', '51': '44', '52': '44', '54': '44', '55': '44', '57': '44', '67': '44', '68': '44', '88': '44',
    '02': '32', '59': '32', '60': '32', '62': '32', '80': '32',
    '75': '11', '77': '11', '78': '11', '91': '11', '92': '11', '93': '11', '94': '11', '95': '11',
    '14': '28', '27': '28', '50': '28', '61': '28', '76': '28',
    '16': '75', '17': '75', '19': '75', '23': '75', '24': '75', '33': '75', '40': '75', '47': '75', '64': '75', '79': '75', '86': '75', '87': '75',
    '09': '76', '11': '76', '12': '76', '30': '76', '31': '76', '32': '76', '34': '76', '46': '76', '48': '76', '65': '76', '66': '76', '81': '76', '82': '76',
    '44': '52', '49': '52', '53': '52', '72': '52', '85': '52',
    '04': '93', '05': '93', '06': '93', '13': '93', '83': '93', '84': '93'
};

// Données des départements (extrait)
const departments = [
    { id: 'dep_01', code: '01', name: 'Ain', path: 'M693 478l-4 0-2 5-7 26-1 3-1 8-2 3v12l-1 3 7 4 3 0 4 4 1 6 5-1 7 2v-1h4l5 4 4-2 3-7 2-3 3 1 3 2 2 5 14 18 5-3 0-7 5 0v-12l3-2 0-11 1 1v-4l-2-4 1-10 4 2 2-3 3-1 4-3h-4v-7l4-3 7 0 0-4-2-1 5-7-1-2-6-4-15 17h-10v-5l-6-2-7 7-5 1v-5l-5-2-7-10-7-3-2-5-3 0-4 2-3 1-4-3z' },
    { id: 'dep_02', code: '02', name: 'Aisne', path: 'M683 150l-4 7-8 0-3 3-6 7 6 1 8-2 7 2 4 5 4 12 3 4 5 1 10 6 1 5 6 8 12 0 2 3 4 1 9-1 7 9 3 0 2-3-3-3 3-4v-5l3-1 6-6-3-5 2-4 9-3 4 1 6 2 4-2-1-3-6-6-2-6-8 1h-4l1-6-11-7-2-5-2-2-7-1-8-4-6-5-1-3 3-2-2-7-4 0-5-3-7-1-9 1-2 1-1 4-4 2-2 3z' },
    { id: 'dep_13', code: '13', name: 'Bouches-du-Rhône', path: 'M687 750l-10 5-3 20-11-2-3 8 3 4-12 7-3 8 11 0 15 1 3 3h-5l-4 6 16 3 12-2-7-6 5-4 7 3 3 7 20 1 6-3 1 4-6 5 8 0-1 4-2 2h17l9 3 1 1 0-7 3-3 3-2 0-2-3-2h-3l-2-2 3-3v-1l-3-1v-3l7 0 2-1-6-6 0-7-4-3 3-7 8-5-6-4-4 3-10 3-7-1-15-6-8 0-7-3-3-4-5-6-13-5h-1z' },
    { id: 'dep_75', code: '75', name: 'Paris', path: 'M552 198l-2 3-3 4-1 2 1 3-1 2 3 2 3 0 3 2 1-2 2-1 2 1 1-2-2-2-1-3 2-2-2-3-3-1-3 2z' },
    { id: 'dep_69', code: '69', name: 'Rhône', path: 'M672 501l-4 0-3 4-2-3-4 3-4-3h-4l-1 1-1 4 2 2v3l2 4-1 2h-8l0 7-3 3 2 6v3l3 3v5l5 4v5l-3 4 3 1v3l-2 2v3l10 9 12 2 1 4-4 4 4 2 3-1 5 1 7-4-1-3-5-5 8-2 10-3 8-10-5-4 0-4-7-2-5 1-1-6-4-4-3 0-7-4 1-3v-12l2-3 1-8-1 2-3 0-1-7-2-6z' },
    { id: 'dep_33', code: '33', name: 'Gironde', path: 'M246 575l-6 9-2 30-4 30-4 24 0 6 3-8 5-6 7 6 1 2 2 3-9 1-2-3-3 2-1 5-4 6v8h3l11-6 7 2-1 4-2 4 3 1 6-2 4 3 3 0 3-4 7-1 2 2 2 2v2l-1 2 6 2 3 2 0 3 4 0 4 5 2 8 6 2 6 0v-2l0-6 2 0 3 5 6-1 3-3-1-3-2-2 1-4h3l4-2-2-4-1-4 3-5 5-8 4-4 3-1 0-4-3 0-2-4 1-3 5-1 3-1 4-1-1-7 4-2-5-3-4 5-11 0-1-2-4-2 3-3v-4l-1-2v-1l3-3 1-5 2-6-2-3h-4l-2-2-1 4-4-2-5 2-4-1-9-8-5 0-1-12-10-1 0-5-2 1h-10l0 3 3 10 0 11-1 2-2-8-5-20-19-17 1-7-4-1z' },
    { id: 'dep_03', code: '03', name: 'Allier', path: 'M542 451l-5 6-3 0-3 4-3-4-10 9v6l2 2 0 2-5 4-5-1-9 2-4 5-2 4 4 6v4l3 4 2-4 3 5 4 2 4 9v3l6 4 3-1 2-6 2 0v-3l4-1 1 2 5-5h5l1 2-2 3 3 5 1 2 9 5 12 2 3 0 5 1 4-3 3 2 1 4 4 1 6 0 1 4 5 2 0-2 9 0-1-21-2-5 1-4 6-1 8-6 0-14-3-4h-5l-2-3h-7l-1-2v-5l-8-14-3-2-7 9-3 1-1-5-3-2-2 3h-5l-1-3-4 2-3 2-5-4-6-3 0-5-9 0z' },
];

const visibleDepartments = computed(() => {
    if (!selectedRegionFilter.value) {
        return departments;
    }
    
    return departments.filter(dept => {
        const deptCode = dept.code;
        const regionCode = departmentToRegion[deptCode];
        return regionCode === selectedRegionFilter.value;
    });
});

const getDepartmentName = (depId) => {
    const dept = departments.find(d => d.id === depId);
    return dept ? dept.name : depId;
};

const getDepartmentColor = (depId) => {
    const depNumber = depId.replace('dep_', '');
    
    if (props.regionalData && props.regionalData.length > 0) {
        const regionCode = departmentToRegion[depNumber];
        const regionData = props.regionalData.find(r => r.region_code === regionCode);
        
        if (regionData && regionData[props.heatmapMetric]) {
            return getColorForValue(regionData[props.heatmapMetric]);
        }
    }
    
    return hoveredDepartment.value === depId ? '#6366f1' : '#8ad';
};

const getColorForValue = (value) => {
    if (value < 5) return '#10b981';
    if (value < 8) return '#f59e0b';
    return '#ef4444';
};

const handleDepartmentClick = (department) => {
    selectedDepartment.value = department;
    emit('department-selected', {
        id: department.id,
        name: department.name,
        code: department.code
    });
};
</script>

<style scoped>
.department-path {
    transition: all 0.2s ease;
}

.department-path:hover {
    filter: brightness(0.9);
}
</style>
