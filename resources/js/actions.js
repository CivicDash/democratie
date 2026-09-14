/**
 * Référentiel des verbes d'action du back-office.
 *
 * Le même geste changeait de couleur et de nom d'un écran à l'autre : « Valider »
 * était bleu sur les mesures et vert sur les propositions, « Rejeter » avait quatre
 * traitements, « Débloquer » une IP était en rouge — la couleur de l'action
 * destructrice. Un bénévole qui apprend un geste sur un écran le reproduisait à
 * contresens sur le suivant.
 *
 * Le classement suit la conséquence, pas l'esthétique. Deux constats le commandent :
 *
 *  - Le seul acte réellement irréversible est la PUBLICATION : la donnée part sur
 *    objectif2027.fr, elle est lue, indexée, archivée. Le retrait ultérieur ne la fait
 *    pas disparaître.
 *  - La suppression est un soft-delete qui renvoie la proposition en file — le code le
 *    documente lui-même.
 *
 * Donc « Publier » est plus grave que « Supprimer », et l'interface disait l'inverse :
 * seule la suppression demandait confirmation.
 *
 * Les classes sont écrites en toutes lettres. Jamais d'interpolation : Tailwind ne
 * verrait pas `bg-${couleur}-600` et purgerait la classe — le build resterait vert et
 * les boutons sortiraient sans style.
 */
export const ACTIONS = {
    valider: {
        libelle: 'Valider',
        classe: 'bg-blue-600 hover:bg-blue-700 text-white border border-transparent',
        confirme: false,
    },
    double_valider: {
        libelle: '2ᵉ validation',
        // Ni valider ni publier : un contrôle à quatre yeux, exercé par un AUTRE
        // modérateur. Une nature de geste distincte mérite sa propre couleur.
        classe: 'bg-violet-600 hover:bg-violet-700 text-white border border-transparent',
        confirme: false,
    },
    publier: {
        libelle: 'Publier',
        // Le vert ne veut plus dire qu'une chose : « c'est sur le site public ».
        classe: 'bg-emerald-600 hover:bg-emerald-700 text-white border border-transparent',
        confirme: true,
    },
    depublier: {
        libelle: 'Dépublier',
        // Réparation : doit rester rapide. Une confirmation ici ralentirait la
        // correction d'une publication erronée, ce qui serait contre-productif.
        classe: 'bg-amber-50 hover:bg-amber-100 text-amber-800 border border-amber-300 dark:bg-amber-900/20 dark:hover:bg-amber-900/40 dark:text-amber-200 dark:border-amber-800',
        confirme: false,
    },
    rejeter: {
        libelle: 'Rejeter',
        // Change un statut, ne détruit rien : contour, pas aplat.
        classe: 'bg-white hover:bg-red-50 text-red-700 border border-red-300 dark:bg-transparent dark:hover:bg-red-900/20 dark:text-red-300 dark:border-red-800',
        confirme: false,
    },
    prendre: {
        libelle: 'Prendre en charge',
        classe: 'bg-white hover:bg-gray-50 text-gray-700 border border-gray-300 dark:bg-transparent dark:hover:bg-gray-700 dark:text-gray-200 dark:border-gray-600',
        confirme: false,
    },
    supprimer: {
        libelle: 'Supprimer',
        classe: 'bg-red-600 hover:bg-red-700 text-white border border-transparent',
        confirme: true,
    },
    neutre: {
        libelle: null,
        classe: 'bg-white hover:bg-gray-50 text-gray-700 border border-gray-300 dark:bg-transparent dark:hover:bg-gray-700 dark:text-gray-200 dark:border-gray-600',
        confirme: false,
    },
};

/** Hauteur plancher : les boutons d'action des files faisaient 24 px, cinq de suite. */
export const TAILLES = {
    sm: 'px-3 py-1.5 text-xs min-h-[36px]',
    md: 'px-4 py-2 text-sm min-h-[40px]',
};

export function actionOu(verbe) {
    return ACTIONS[verbe] ?? ACTIONS.neutre;
}
