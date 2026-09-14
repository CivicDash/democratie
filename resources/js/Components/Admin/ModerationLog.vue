<template>
    <details class="text-left" @toggle="charger">
        <summary class="cursor-pointer text-xs text-gray-500 dark:text-gray-400 hover:text-blue-600 dark:hover:text-blue-400">
            Historique des décisions
        </summary>

        <div class="mt-2 min-w-[18rem]">
            <p v-if="chargement" class="text-xs text-gray-400">Chargement…</p>

            <p v-else-if="erreur" class="text-xs text-red-600 dark:text-red-400">
                Impossible de charger l'historique.
            </p>

            <ul v-else-if="entrees.length" class="space-y-2">
                <li v-for="e in entrees" :key="e.id"
                    class="border-l-2 pl-3 py-0.5"
                    :class="bordure(e.action)">
                    <div class="flex flex-wrap items-baseline justify-between gap-2">
                        <span class="text-xs font-medium text-gray-800 dark:text-gray-200">
                            {{ libelleAction(e) }}
                        </span>
                        <span class="text-[11px] text-gray-500 dark:text-gray-400">{{ e.date }}</span>
                    </div>
                    <p class="text-[11px] text-gray-500 dark:text-gray-400">par {{ e.moderateur }}</p>
                    <p v-if="e.commentaire" class="text-xs text-gray-600 dark:text-gray-300 mt-0.5">
                        {{ e.commentaire }}
                    </p>
                </li>
            </ul>

            <p v-else class="text-xs text-gray-400">Aucune décision enregistrée pour cet élément.</p>
        </div>
    </details>
</template>

<script setup>
import { ref } from 'vue';

/**
 * Journal des décisions prises sur un élément.
 *
 * Plus de mille sept cents décisions étaient enregistrées et affichées nulle part.
 * Le chargement est différé à l'ouverture : une file affiche vingt-cinq lignes, et
 * charger l'historique de chacune d'avance coûterait cher pour une information qu'on
 * consulte rarement — mais dont on a absolument besoin quand une décision est
 * contestée.
 */
const props = defineProps({
    type: { type: String, required: true },
    id: { type: [Number, String], required: true },
});

const entrees = ref([]);
const chargement = ref(false);
const charge = ref(false);
const erreur = ref(false);

async function charger(evenement) {
    if (!evenement.target.open || charge.value) {
        return;
    }
    chargement.value = true;
    try {
        const reponse = await fetch(route('admin.presidentielle.journal', { type: props.type, id: props.id }),
            { headers: { Accept: 'application/json' } });
        if (!reponse.ok) throw new Error(reponse.statusText);
        entrees.value = (await reponse.json()).entrees ?? [];
        charge.value = true;
    } catch {
        erreur.value = true;
    } finally {
        chargement.value = false;
    }
}

const LIBELLES = {
    valider: 'Validé',
    double_valider: 'Deuxième validation',
    publier: 'Publié',
    depublier: 'Dépublié',
    rejeter: 'Rejeté',
    supprimer: 'Supprimé',
    mettre_en_avant: 'Passé en mesure phare',
    retirer_en_avant: 'Retiré des mesures phares',
};

function libelleAction(e) {
    const base = LIBELLES[e.action] ?? e.action.replace(/_/g, ' ');
    return e.ancien_statut && e.nouveau_statut && e.ancien_statut !== e.nouveau_statut
        ? `${base} (${e.ancien_statut} → ${e.nouveau_statut})`
        : base;
}

function bordure(action) {
    return {
        publier: 'border-emerald-500',
        depublier: 'border-amber-400',
        valider: 'border-blue-500',
        double_valider: 'border-violet-500',
        rejeter: 'border-red-400',
        supprimer: 'border-red-600',
    }[action] ?? 'border-gray-300 dark:border-gray-600';
}
</script>
