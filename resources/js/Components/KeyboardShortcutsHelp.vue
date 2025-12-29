<script setup>
defineProps({
    modelValue: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(["update:modelValue"]);

const close = () => {
    emit("update:modelValue", false);
};

const shortcutGroups = [
    {
        title: "Navigation Générale",
        shortcuts: [
            { keys: ["⌘/Ctrl", "K"], description: "Ouvrir la recherche rapide" },
            { keys: ["/"], description: "Focus sur la recherche" },
            { keys: ["Échap"], description: "Fermer les modales" },
            { keys: ["?"], description: "Afficher cette aide" },
        ],
    },
    {
        title: "Navigation Rapide",
        description: "Appuyez sur G puis la lettre",
        shortcuts: [
            { keys: ["G", "H"], description: "Aller à l'accueil" },
            { keys: ["G", "D"], description: "Aller aux députés" },
            { keys: ["G", "S"], description: "Aller aux sénateurs" },
            { keys: ["G", "L"], description: "Aller à la législation" },
        ],
    },
];
</script>

<template>
    <Teleport to="body">
        <Transition
            enter-active-class="duration-200 ease-out"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="duration-150 ease-in"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div v-if="modelValue" class="fixed inset-0 z-50 overflow-y-auto p-4 sm:p-6 md:p-20">
                <!-- Backdrop -->
                <div class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm" @click="close" />

                <!-- Modal -->
                <div class="relative mx-auto max-w-lg transform overflow-hidden rounded-2xl bg-white dark:bg-gray-800 shadow-2xl ring-1 ring-black/5 dark:ring-white/10">
                    <!-- Header -->
                    <div class="flex items-center justify-between border-b border-gray-200 dark:border-gray-700 px-6 py-4">
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100 flex items-center gap-2">
                            <span>⌨️</span>
                            Raccourcis Clavier
                        </h2>
                        <button
                            @click="close"
                            class="rounded-full p-1 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 transition"
                        >
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <!-- Content -->
                    <div class="px-6 py-4 space-y-6 max-h-96 overflow-y-auto">
                        <div v-for="group in shortcutGroups" :key="group.title">
                            <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100 mb-2">
                                {{ group.title }}
                            </h3>
                            <p v-if="group.description" class="text-xs text-gray-500 dark:text-gray-400 mb-3">
                                {{ group.description }}
                            </p>
                            <div class="space-y-2">
                                <div
                                    v-for="shortcut in group.shortcuts"
                                    :key="shortcut.description"
                                    class="flex items-center justify-between py-1.5"
                                >
                                    <span class="text-sm text-gray-600 dark:text-gray-400">
                                        {{ shortcut.description }}
                                    </span>
                                    <div class="flex items-center gap-1">
                                        <kbd
                                            v-for="(key, idx) in shortcut.keys"
                                            :key="idx"
                                            class="inline-flex items-center justify-center min-w-[24px] px-2 py-1 text-xs font-medium rounded-md bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 border border-gray-200 dark:border-gray-600 shadow-sm"
                                        >
                                            {{ key }}
                                        </kbd>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Footer -->
                    <div class="border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/50 px-6 py-3">
                        <p class="text-xs text-gray-500 dark:text-gray-400 text-center">
                            Appuyez sur <kbd class="mx-1 rounded bg-gray-200 dark:bg-gray-700 px-1.5 py-0.5 font-medium">Échap</kbd> pour fermer
                        </p>
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>

