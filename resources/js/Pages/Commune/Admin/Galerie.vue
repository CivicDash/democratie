<script setup>
import { Link, router, useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { ref } from 'vue';

const props = defineProps({
    ville: Object,
    images: Array,
});

const uploadForm = useForm({
    images: [],
    legende: '',
});

const fileInput = ref(null);
const dragOver = ref(false);

const onFilesSelected = (e) => {
    uploadForm.images = Array.from(e.target.files);
    submitUpload();
};

const onDrop = (e) => {
    dragOver.value = false;
    uploadForm.images = Array.from(e.dataTransfer.files).filter(f => f.type.startsWith('image/'));
    if (uploadForm.images.length) submitUpload();
};

const submitUpload = () => {
    uploadForm.post(route('commune.admin.galerie.upload', props.ville.code_insee), {
        forceFormData: true,
        onSuccess: () => {
            uploadForm.reset();
            if (fileInput.value) fileInput.value.value = '';
        },
    });
};

const toggleVisibility = (img) => {
    router.put(route('commune.admin.galerie.update', [props.ville.code_insee, img.id]), {
        visible: !img.visible,
    }, { preserveScroll: true });
};

const deleteImage = (img) => {
    if (!confirm('Supprimer cette image ?')) return;
    router.delete(route('commune.admin.galerie.delete', [props.ville.code_insee, img.id]), {
        preserveScroll: true,
    });
};
</script>

<template>
    <AuthenticatedLayout>
        <div class="max-w-7xl mx-auto px-4 sm:px-6 py-8">
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h1 class="text-2xl font-bold text-slate-900 dark:text-white">Galerie - {{ ville.nom }}</h1>
                    <Link :href="route('commune.admin.dashboard', ville.code_insee)" class="text-sm text-blue-600 hover:text-blue-700 dark:text-blue-400">
                        &larr; Retour au dashboard
                    </Link>
                </div>
            </div>

            <!-- Upload zone -->
            <div
                @dragover.prevent="dragOver = true"
                @dragleave="dragOver = false"
                @drop.prevent="onDrop"
                class="border-2 border-dashed rounded-2xl p-8 text-center mb-8 transition-colors cursor-pointer"
                :class="dragOver ? 'border-blue-500 bg-blue-50 dark:bg-blue-900/20' : 'border-slate-300 dark:border-slate-600 hover:border-blue-400'"
                @click="fileInput?.click()"
            >
                <input ref="fileInput" type="file" accept="image/*" multiple class="hidden" @change="onFilesSelected" />
                <div class="text-4xl mb-2">📸</div>
                <p class="text-slate-600 dark:text-slate-300 font-medium">
                    {{ uploadForm.processing ? 'Envoi en cours...' : 'Glissez des images ici ou cliquez pour uploader' }}
                </p>
                <p class="text-xs text-slate-400 mt-1">JPEG, PNG, WebP - 4 Mo max par image - 10 images max</p>
                <p v-if="uploadForm.errors.images" class="text-sm text-red-500 mt-2">{{ uploadForm.errors.images }}</p>
            </div>

            <!-- Grille images -->
            <div v-if="images?.length" class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4">
                <div
                    v-for="img in images"
                    :key="img.id"
                    class="group relative bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 overflow-hidden"
                    :class="{ 'opacity-40': !img.visible }"
                >
                    <div class="aspect-[4/3] bg-slate-100 dark:bg-slate-700">
                        <img :src="img.image_url" :alt="img.legende" class="w-full h-full object-cover" />
                    </div>

                    <!-- Overlay actions -->
                    <div class="absolute inset-0 bg-black/0 group-hover:bg-black/40 transition-all flex items-center justify-center gap-2 opacity-0 group-hover:opacity-100">
                        <button
                            @click.stop="toggleVisibility(img)"
                            class="p-2 bg-white rounded-lg text-sm hover:bg-slate-100 transition-colors"
                            :title="img.visible ? 'Masquer' : 'Afficher'"
                        >
                            {{ img.visible ? '👁️' : '🚫' }}
                        </button>
                        <button
                            @click.stop="deleteImage(img)"
                            class="p-2 bg-white rounded-lg text-sm text-red-600 hover:bg-red-50 transition-colors"
                            title="Supprimer"
                        >
                            🗑️
                        </button>
                    </div>

                    <!-- Info bar -->
                    <div class="p-2.5">
                        <div class="flex items-center justify-between">
                            <p v-if="img.legende" class="text-xs text-slate-600 dark:text-slate-400 truncate">{{ img.legende }}</p>
                            <span class="text-xs px-1.5 py-0.5 rounded-full" :class="img.source === 'wikimedia' ? 'bg-purple-100 text-purple-600 dark:bg-purple-900/30 dark:text-purple-300' : 'bg-blue-100 text-blue-600 dark:bg-blue-900/30 dark:text-blue-300'">
                                {{ img.source === 'wikimedia' ? 'Wiki' : 'Upload' }}
                            </span>
                        </div>
                        <p v-if="img.credit" class="text-xs text-slate-400 mt-0.5">{{ img.credit }}</p>
                    </div>
                </div>
            </div>

            <div v-else class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 p-12 text-center">
                <div class="text-4xl mb-3">🖼️</div>
                <p class="text-slate-500 dark:text-slate-400 mb-2">Aucune image dans la galerie.</p>
                <p class="text-sm text-slate-400">Uploadez des photos ou lancez l'import Wikimedia depuis le terminal.</p>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
