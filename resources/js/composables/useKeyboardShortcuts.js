import { onMounted, onUnmounted } from "vue";

/**
 * Composable pour gérer les raccourcis clavier globaux
 * 
 * Raccourcis disponibles:
 * - Cmd/Ctrl + K : Ouvrir la recherche
 * - Escape : Fermer la recherche / modales
 * - / : Focus sur la recherche (si pas dans un input)
 * - G puis H : Aller à l'accueil
 * - G puis D : Aller aux députés
 * - G puis S : Aller aux sénateurs
 * - G puis L : Aller à la législation
 * - ? : Afficher l'aide des raccourcis
 */

let pendingKey = null;
let pendingTimeout = null;

export function useKeyboardShortcuts(options = {}) {
    const {
        onOpenPalette,
        onToggleHelp,
        onCloseAll,
        onNavigate,
    } = options;

    const handleKeyDown = (event) => {
        const target = event.target;
        const tagName = target.tagName.toLowerCase();
        const isInputField = tagName === "input" || tagName === "textarea" || target.isContentEditable;

        // Cmd/Ctrl + K ou Cmd/Ctrl + / : Ouvrir la recherche
        if ((event.metaKey || event.ctrlKey) && (event.key.toLowerCase() === "k" || event.key === "/")) {
            event.preventDefault();
            onOpenPalette?.();
            return;
        }

        // Escape : Fermer tout
        if (event.key === "Escape") {
            onCloseAll?.();
            return;
        }

        // Ne pas traiter les autres raccourcis si dans un champ de saisie
        if (isInputField) {
            return;
        }

        // / : Focus sur la recherche
        if (event.key === "/") {
            event.preventDefault();
            onOpenPalette?.();
            return;
        }

        // ? : Aide des raccourcis
        if (event.key === "?" || (event.shiftKey && event.key === "/")) {
            event.preventDefault();
            onToggleHelp?.();
            return;
        }

        // Séquences de touches (G puis autre touche)
        if (event.key.toLowerCase() === "g") {
            pendingKey = "g";
            if (pendingTimeout) clearTimeout(pendingTimeout);
            pendingTimeout = setTimeout(() => {
                pendingKey = null;
            }, 1000);
            return;
        }

        if (pendingKey === "g") {
            pendingKey = null;
            if (pendingTimeout) clearTimeout(pendingTimeout);

            switch (event.key.toLowerCase()) {
                case "h":
                    onNavigate?.("dashboard");
                    break;
                case "d":
                    onNavigate?.("representants.deputes.index");
                    break;
                case "s":
                    onNavigate?.("representants.senateurs.index");
                    break;
                case "l":
                    onNavigate?.("legislation.index");
                    break;
            }
        }
    };

    onMounted(() => {
        document.addEventListener("keydown", handleKeyDown);
    });

    onUnmounted(() => {
        document.removeEventListener("keydown", handleKeyDown);
        if (pendingTimeout) clearTimeout(pendingTimeout);
    });
}

