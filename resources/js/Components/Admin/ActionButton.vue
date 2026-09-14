<template>
    <button type="button"
            :class="[classes, 'rounded-lg font-medium transition disabled:opacity-50 disabled:cursor-not-allowed inline-flex items-center gap-1.5']"
            :disabled="disabled || occupe"
            :title="titre || undefined"
            :aria-label="ariaLabel"
            @click="declencher">
        <span v-if="icone" aria-hidden="true">{{ icone }}</span>
        <span><slot>{{ texte }}</slot></span>
    </button>
</template>

<script setup>
import { computed, ref } from 'vue';
import { actionOu, TAILLES } from '@/actions';
import { useConfirm } from '@/composables/useConfirm';

/**
 * Bouton d'action typé par verbe.
 *
 * Il porte trois choses que chaque écran réinventait, mal : la couleur qui correspond
 * à la gravité réelle du geste, la confirmation quand l'acte ne se rattrape pas, et un
 * verrou anti-double-clic. Il impose aussi une hauteur plancher : les boutons des files
 * faisaient 24 px, alignés par cinq, avec « Supprimer » voisin immédiat de « Phare ».
 */
const props = defineProps({
    verbe: { type: String, required: true },
    libelle: { type: String, default: null },
    taille: { type: String, default: 'sm' },
    icone: { type: String, default: null },
    disabled: { type: Boolean, default: false },
    titre: { type: String, default: null },
    /** Message de la modale. Sans lui, un verbe qui exige confirmation reste confirmé,
     *  mais avec un texte générique — donc moins utile. */
    confirmation: { type: String, default: null },
    titreConfirmation: { type: String, default: null },
    /** Force ou retire la confirmation pour ce bouton précis. */
    confirmer: { type: Boolean, default: null },
});

const emit = defineEmits(['action']);

const { confirm } = useConfirm();
const occupe = ref(false);

const action = computed(() => actionOu(props.verbe));
const texte = computed(() => props.libelle ?? action.value.libelle ?? '');
const classes = computed(() => `${action.value.classe} ${TAILLES[props.taille] ?? TAILLES.sm}`);

// Un bouton dont le contenu est une icône seule n'a pas de nom accessible.
const ariaLabel = computed(() => (texte.value ? undefined : props.titre ?? props.libelle ?? undefined));

const doitConfirmer = computed(() => (props.confirmer !== null ? props.confirmer : action.value.confirme));

async function declencher() {
    if (occupe.value) {
        return;
    }

    if (doitConfirmer.value) {
        const ok = await confirm({
            type: props.verbe === 'supprimer' ? 'danger' : 'warning',
            title: props.titreConfirmation ?? `${texte.value} ?`,
            message: props.confirmation ?? 'Cette action est engageante. Confirmez-vous ?',
            confirmLabel: texte.value || 'Confirmer',
        });
        if (!ok) {
            return;
        }
    }

    occupe.value = true;
    emit('action');
    // Le verrou se relâche au tour suivant : il protège du double-clic, pas de la
    // durée de la requête, que l'écran appelant gère avec son propre `processing`.
    setTimeout(() => { occupe.value = false; }, 400);
}
</script>
