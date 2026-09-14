import { computed, watch } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { useToast } from '@/composables/useToast';

/**
 * Retour visuel unique pour les messages flash ET les erreurs de validation.
 *
 * Le succès était déjà signalé partout ; l'échec ne l'était nulle part. Sur les
 * quarante-deux écrans qui postent sans lire `page.props.errors`, une réponse 422
 * était indiscernable d'un enregistrement réussi : l'utilisateur cliquait, rien ne
 * bougeait, il recliquait. Brancher l'écoute dans les layouts plutôt qu'écran par
 * écran traite la famille entière d'un coup.
 *
 * À appeler une fois dans le `<script setup>` d'un layout, qui doit par ailleurs
 * monter <ToastContainer />.
 */
export function useFeedbackToasts() {
    const page = usePage();
    const toast = useToast();

    const flash = computed(() => page.props.flash);
    watch(flash, (f) => {
        if (f?.success) toast.success(f.success);
        if (f?.error) toast.error(f.error);
        if (f?.warning) toast.warning(f.warning);
        if (f?.info) toast.info(f.info);
    }, { deep: true, immediate: true });

    // Inertia partage `errors` sur toutes les pages via parent::share() : rien à
    // ajouter côté serveur.
    const errors = computed(() => page.props.errors);
    watch(errors, (e) => {
        // Un sac d'erreurs nommé (`errorBags`) arrive sous forme d'objets imbriqués :
        // on ne garde que les messages exploitables tels quels.
        const messages = Object.entries(e ?? {})
            .filter(([, v]) => typeof v === 'string' && v.length)
            .map(([champ, message]) => ({ champ, message }));

        if (messages.length === 0) return;

        if (messages.length === 1) {
            toast.error(messages[0].message, 'Enregistrement refusé');
            return;
        }

        // Au-delà d'une erreur, citer les champs vaut mieux qu'empiler les phrases :
        // l'utilisateur a besoin de savoir OÙ regarder.
        toast.error(
            `${messages.length} champs à corriger : ${messages.map((m) => m.champ).join(', ')}.`,
            'Enregistrement refusé',
            8000,
        );
    }, { deep: true, immediate: true });
}
