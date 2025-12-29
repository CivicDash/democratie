import { onMounted, onUnmounted, ref } from "vue";

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

const isSearchOpen = ref(false);
const isHelpOpen = ref(false);
let pendingKey = null;
let pendingTimeout = null;

export function useKeyboardShortcuts() {
    const shortcuts = [
        { keys: ["⌘", "K"], description: "Ouvrir la recherche", action: "search" },
        { keys: ["Échap"], description: "Fermer la recherche", action: "escape" },
        { keys: ["/"], description: "Focus recherche", action: "focus-search" },
        { keys: ["G", "H"], description: "Aller à l'accueil", action: "goto-home" },
        { keys: ["G", "D"], description: "Aller aux députés", action: "goto-deputes" },
        { keys: ["G", "S"], description: "Aller aux sénateurs", action: "goto-senateurs" },
        { keys: ["G", "L"], description: "Aller à la législation", action: "goto-legislation" },
        { keys: ["?"], description: "Aide raccourcis", action: "help" },
    ];

    const handleKeyDown = (event) => {
        const target = event.target;
        const tagName = target.tagName.toLowerCase();
        const isInputField = tagName === "input" || tagName === "textarea" || target.isContentEditable;

        // Cmd/Ctrl + K : Ouvrir la recherche
        if ((event.metaKey || event.ctrlKey) && event.key.toLowerCase() === "k") {
            event.preventDefault();
            isSearchOpen.value = true;
            return;
        }

        // Escape : Fermer tout
        if (event.key === "Escape") {
            if (isSearchOpen.value) {
                isSearchOpen.value = false;
            }
            if (isHelpOpen.value) {
                isHelpOpen.value = false;
            }
            return;
        }

        // Ne pas traiter les autres raccourcis si dans un champ de saisie
        if (isInputField) {
            return;
        }

        // / : Focus sur la recherche
        if (event.key === "/") {
            event.preventDefault();
            isSearchOpen.value = true;
            return;
        }

        // ? : Aide des raccourcis
        if (event.key === "?" || (event.shiftKey && event.key === "/")) {
            event.preventDefault();
            isHelpOpen.value = !isHelpOpen.value;
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
                    window.location.href = "/dashboard";
                    break;
                case "d":
                    window.location.href = "/representants/deputes";
                    break;
                case "s":
                    window.location.href = "/representants/senateurs";
                    break;
                case "l":
                    window.location.href = "/legislation";
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

    const openSearch = () => {
        isSearchOpen.value = true;
    };

    const closeSearch = () => {
        isSearchOpen.value = false;
    };

    const toggleHelp = () => {
        isHelpOpen.value = !isHelpOpen.value;
    };

    return {
        isSearchOpen,
        isHelpOpen,
        shortcuts,
        openSearch,
        closeSearch,
        toggleHelp,
    };
}

