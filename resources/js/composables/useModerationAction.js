import { ref } from 'vue';
import { router } from '@inertiajs/vue3';

/**
 * Actions de modération du module présidentielle.
 *
 * Dix écrans sur douze redéfinissaient la même fonction `agir(objet, action)`. En la
 * centralisant, la confirmation de publication, la préservation du défilement et le
 * verrou anti-double-clic cessent d'être une affaire de discipline écran par écran.
 *
 * Toutes les actions passent par cinq routes seulement — c'est ce qui rend la
 * centralisation possible sans perdre en clarté.
 */
export function useModerationAction(type, options = {}) {
    const enCours = ref(new Set());
    const routeAction = options.route ?? 'admin.presidentielle.moderation.action';

    function agir(id, action, apres = {}) {
        if (enCours.value.has(id)) {
            return;
        }
        enCours.value.add(id);

        router.post(route(routeAction), { type, id, action }, {
            preserveScroll: true,
            ...apres,
            onFinish: () => {
                enCours.value.delete(id);
                apres.onFinish?.();
            },
        });
    }

    function agirLot(ids, action, apres = {}) {
        if (!ids.length) {
            return;
        }
        router.post(route('admin.presidentielle.moderation.action-lot'),
            { type, ids, action },
            { preserveScroll: true, ...apres });
    }

    return { agir, agirLot, enCours };
}

/**
 * Le texte d'une confirmation de publication.
 *
 * Publier est le seul acte irréversible en pratique : la donnée part sur
 * objectif2027.fr, elle est lue, indexée, archivée. La modale doit donc nommer
 * précisément ce qui va devenir public — pas demander « Êtes-vous sûr ? ».
 */
export function messagePublication(quoi, nom, complement = null) {
    const morceaux = [`${quoi} « ${nom} » sera publiquement lisible sur objectif2027.fr et indexée par les moteurs de recherche.`];
    if (complement) {
        morceaux.push(complement);
    }
    morceaux.push('Une dépublication ultérieure ne la fera pas disparaître des copies déjà faites.');

    return morceaux.join(' ');
}
