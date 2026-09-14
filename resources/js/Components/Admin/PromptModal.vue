<template>
    <Modal :show="show" max-width="md" @close="annuler">
        <div class="p-6">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">{{ titre }}</h2>
            <p v-if="message" class="mt-2 text-sm text-gray-600 dark:text-gray-400">{{ message }}</p>

            <div class="mt-4">
                <label :for="idChamp" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                    {{ label }}
                </label>
                <input :id="idChamp"
                       ref="champ"
                       v-model="valeur"
                       type="text"
                       class="w-full px-3 py-2 border rounded-lg dark:bg-gray-700 dark:border-gray-600"
                       @keyup.enter="valider" />
            </div>

            <div class="mt-6 flex justify-end gap-2">
                <button type="button" @click="annuler"
                        class="px-4 py-2 min-h-[40px] rounded-lg border border-gray-300 dark:border-gray-600 text-sm text-gray-700 dark:text-gray-200">
                    Annuler
                </button>
                <button type="button" @click="valider"
                        class="px-4 py-2 min-h-[40px] rounded-lg bg-blue-600 text-white hover:bg-blue-700 text-sm">
                    {{ libelleValidation }}
                </button>
            </div>
        </div>
    </Modal>
</template>

<script setup>
import { nextTick, ref, watch } from 'vue';
import Modal from '@/Components/Modal.vue';

/**
 * Saisie d'une valeur courte, en remplacement de window.prompt().
 *
 * Le prompt natif n'est pas stylable, n'a pas de libellé associé au champ, et
 * plusieurs navigateurs permettent à l'utilisateur de le supprimer définitivement —
 * après quoi l'action ne fonctionne plus, sans explication.
 */
defineProps({
    show: { type: Boolean, default: false },
    titre: { type: String, required: true },
    message: { type: String, default: null },
    label: { type: String, default: 'Valeur' },
    libelleValidation: { type: String, default: 'Confirmer' },
    idChamp: { type: String, default: 'prompt-valeur' },
});

const emit = defineEmits(['valider', 'annuler']);

const valeur = ref('');
const champ = ref(null);

watch(() => champ.value, async (el) => {
    if (el) {
        await nextTick();
        el.focus();
    }
});

function valider() {
    emit('valider', valeur.value);
    valeur.value = '';
}

function annuler() {
    valeur.value = '';
    emit('annuler');
}
</script>
