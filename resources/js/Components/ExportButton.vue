<template>
    <div class="inline-flex">
        <button
            @click="handleExport"
            :disabled="loading"
            class="inline-flex items-center gap-2 px-4 py-2 bg-white border border-gray-300 rounded-lg shadow-sm text-sm font-medium text-gray-700 hover:bg-gray-50 hover:border-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 transition-all disabled:opacity-50 disabled:cursor-not-allowed"
            :class="{ 'animate-pulse': loading }"
        >
            <!-- Icône -->
            <svg
                v-if="!loading"
                class="w-5 h-5"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
            >
                <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"
                />
            </svg>
            
            <!-- Spinner pendant chargement -->
            <svg
                v-else
                class="w-5 h-5 animate-spin"
                fill="none"
                viewBox="0 0 24 24"
            >
                <circle
                    class="opacity-25"
                    cx="12"
                    cy="12"
                    r="10"
                    stroke="currentColor"
                    stroke-width="4"
                ></circle>
                <path
                    class="opacity-75"
                    fill="currentColor"
                    d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
                ></path>
            </svg>

            <span>{{ loading ? 'Génération...' : label }}</span>
        </button>
    </div>
</template>

<script setup>
import { ref } from 'vue';

const props = defineProps({
    type: {
        type: String,
        required: true,
        validator: (value) => ['groupe', 'thematique', 'proposition', 'statistiques', 'comparaison'].includes(value),
    },
    id: {
        type: [String, Number],
        default: null,
    },
    label: {
        type: String,
        default: 'Exporter en PDF',
    },
    params: {
        type: Object,
        default: () => ({}),
    },
});

const emit = defineEmits(['export-start', 'export-success', 'export-error']);

const loading = ref(false);

const handleExport = async () => {
    loading.value = true;
    emit('export-start');

    try {
        let url = `/api/export/${props.type}`;
        
        // Construire l'URL selon le type
        if (props.id && props.type !== 'statistiques' && props.type !== 'comparaison') {
            url += `/${props.id}`;
        }

        // Ajouter les paramètres
        const params = new URLSearchParams(props.params);
        if (params.toString()) {
            url += `?${params.toString()}`;
        }

        // Télécharger le PDF
        const response = await fetch(url, {
            method: props.type === 'comparaison' ? 'POST' : 'GET',
            headers: {
                'Accept': 'application/pdf',
            },
        });

        if (!response.ok) {
            throw new Error('Erreur lors de la génération du PDF');
        }

        // Créer un blob et télécharger
        const blob = await response.blob();
        const downloadUrl = window.URL.createObjectURL(blob);
        const link = document.createElement('a');
        link.href = downloadUrl;
        link.download = getFilename();
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        window.URL.revokeObjectURL(downloadUrl);

        emit('export-success');
    } catch (error) {
        console.error('Erreur export PDF:', error);
        emit('export-error', error);
        alert('Erreur lors de l\'export PDF. Veuillez réessayer.');
    } finally {
        loading.value = false;
    }
};

const getFilename = () => {
    const timestamp = new Date().toISOString().split('T')[0];
    return `${props.type}-${props.id || 'export'}-${timestamp}.pdf`;
};
</script>

<style scoped>
/* Styles additionnels si nécessaire */
</style>

