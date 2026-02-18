import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';

export function useNavigation() {
    const page = usePage();
    const user = computed(() => page.props.auth?.user);
    const isAuthenticated = computed(() => !!user.value);

    const mesElus = computed(() => ({
        key: 'mes-elus',
        label: 'Mes Elus',
        icon: 'user-group',
        emoji: '👤',
        activeColor: 'indigo',
        isActive: page.url.startsWith('/representants') || page.url.startsWith('/parlement/comparaison'),
        items: [
            { href: route('representants.mes-representants'), icon: '📍', title: 'Mes Representants', description: 'Selon votre localisation' },
            { href: route('representants.deputes.index'), icon: '🏛️', title: 'Trouver un depute', description: '577 elus', badge: '577', badgeColor: 'indigo' },
            { href: route('representants.senateurs.index'), icon: '🔴', title: 'Trouver un senateur', description: '348 elus', badge: '348', badgeColor: 'red' },
            ...(isAuthenticated.value ? [{ href: route('profile.elus-suivis'), icon: '🔔', title: 'Elus suivis', description: 'Vos alertes personnalisees' }] : []),
            { href: route('parlement.comparaison'), icon: '📊', title: 'Comparaison Elus', description: 'Deputes, senateurs, maires' },
        ],
    }));

    const institutions = computed(() => ({
        key: 'institutions',
        label: 'Institutions',
        icon: 'building-library',
        emoji: '🏛️',
        activeColor: 'sky',
        isActive:
            page.url.startsWith('/representants/deputes') ||
            page.url.startsWith('/representants/senateurs') ||
            page.url.startsWith('/legislation/scrutins') ||
            page.url.startsWith('/legislation/groupes') ||
            page.url.startsWith('/questions') ||
            page.url.startsWith('/debats') ||
            page.url.startsWith('/gouvernement') ||
            page.url.startsWith('/parlement/calendrier'),
        columns: [
            {
                key: 'an',
                label: 'Assemblee Nationale',
                img: "/images/Logo_de_l'Assemblée_nationale_française.svg",
                color: 'indigo',
                items: [
                    { href: route('representants.deputes.index'), icon: '👥', title: 'Deputes', description: '577 elus', badge: '577', badgeColor: 'indigo' },
                    { href: route('legislation.scrutins.index'), icon: '🗳️', title: 'Scrutins AN', description: 'Votes publics' },
                    { href: route('questions.index'), icon: '❓', title: 'Questions au Gouv.', description: 'Interpellations' },
                ],
            },
            {
                key: 'senat',
                label: 'Senat',
                img: '/images/Logo_du_Sénat_Republique_française.svg',
                color: 'rose',
                items: [
                    { href: route('representants.senateurs.index'), icon: '👥', title: 'Senateurs', description: '348 elus', badge: '348', badgeColor: 'red' },
                    { href: route('legislation.scrutins-senat.index'), icon: '🗳️', title: 'Scrutins Senat', description: 'Votes publics' },
                    { href: route('debats.senat.index'), icon: '💬', title: 'Debats en seance', description: 'Comptes-rendus' },
                ],
            },
            {
                key: 'gouvernement',
                label: 'Gouvernement',
                img: '/images/Logo_de_la_présidence_de_la_République_(2018).svg',
                color: 'amber',
                items: [
                    { href: route('gouvernement.president'), icon: '🏛️', title: 'President de la Republique', description: 'Chef de l\'Etat' },
                    { href: route('gouvernement.index'), icon: '👔', title: 'Composition', description: 'Ministres et ministeres' },
                    { href: route('legislation.groupes.index'), icon: '🎨', title: 'Groupes politiques', description: 'AN & Senat' },
                    { href: route('parlement.calendrier.index'), icon: '📅', title: 'Calendrier parlementaire', description: 'Seances et commissions' },
                ],
            },
        ],
    }));

    const legislatif = computed(() => ({
        key: 'legislatif',
        label: 'Legislatif',
        icon: 'scale',
        emoji: '⚖️',
        activeColor: 'sky',
        isActive:
            page.url.startsWith('/lois') ||
            page.url.startsWith('/legislation/hub') ||
            page.url.startsWith('/legislation/thematiques') ||
            page.url.startsWith('/tags') ||
            page.url.startsWith('/budget-etat') ||
            page.url.startsWith('/documents'),
        items: [
            { href: route('lois.index'), icon: '📜', title: 'Lois en cours', description: 'Parcours legislatif' },
            { href: route('tags.index'), icon: '🏷️', title: 'Thematiques', description: 'Par domaine' },
            { href: route('budget-etat.index'), icon: '💰', title: 'Budget de l\'Etat', description: 'Recettes et depenses' },
            { href: route('documents.index'), icon: '📄', title: 'Documents publics', description: 'Officiels verifies' },
        ],
    }));

    const agir = computed(() => ({
        key: 'agir',
        label: 'Agir',
        icon: 'hand-raised',
        emoji: '💡',
        activeColor: 'emerald',
        isActive:
            page.url.startsWith('/participation') ||
            page.url.startsWith('/budget') ||
            page.url.startsWith('/elections'),
        items: [
            { href: route('participation.ideas.index'), icon: '💬', title: 'Idees citoyennes', description: 'Propositions & debats' },
            { href: route('budget.index'), icon: '💰', title: 'Budget participatif', description: 'Repartissez le budget' },
            { divider: true },
            { href: route('elections.hub'), icon: '📅', title: 'Calendrier electoral', description: 'Prochaines echeances' },
            { href: route('elections.municipales.index'), icon: '🏘️', title: 'Municipales 2026', description: 'Mars 2026', badge: '34 914', badgeColor: 'green' },
        ],
    }));

    const comprendre = computed(() => ({
        key: 'comprendre',
        label: 'Comprendre',
        icon: 'academic-cap',
        emoji: '🎓',
        activeColor: 'amber',
        isActive: page.url.startsWith('/democratie'),
        items: [
            { href: route('democratie.index'), icon: '🎓', title: 'Hub Democratie', description: 'Vue d\'ensemble interactive' },
            { divider: true },
            { href: route('democratie.parcours-loi'), icon: '📜', title: 'Parcours d\'une Loi', description: 'De l\'initiative a la promulgation' },
            { href: route('democratie.elections'), icon: '🗳️', title: 'Les Elections', description: '7 types de scrutin expliques' },
            { href: route('democratie.representants'), icon: '👥', title: 'Nos Representants', description: 'Deputes, senateurs, maires' },
            { href: route('democratie.votes'), icon: '📊', title: 'Comment Votent-ils ?', description: 'Scrutins et discipline de groupe' },
            { href: route('democratie.gouvernement'), icon: '🏛️', title: 'Le Gouvernement', description: 'Executif et separation des pouvoirs' },
        ],
    }));

    const donnees = computed(() => ({
        key: 'donnees',
        label: 'Donnees',
        emoji: '📊',
        isActive:
            page.url.startsWith('/statistiques') ||
            page.url.startsWith('/donnees'),
        items: [
            { href: route('statistics.france'), icon: '🗺️', title: 'Statistiques France', description: 'Demographie, economie, societe' },
            { href: route('statistics.villes'), icon: '🏘️', title: 'Statistiques Villes', description: 'Communes et populations' },
            { href: route('statistics.regions.index'), icon: '🗺️', title: 'Statistiques Regions', description: '18 regions et departements' },
            { href: route('donnees.gouvernements'), icon: '🏛️', title: 'Statistiques Gouvernement', description: 'Ministres, ministeres' },
        ],
    }));

    const desktopSections = computed(() => [
        mesElus.value,
        institutions.value,
        legislatif.value,
        agir.value,
        comprendre.value,
    ]);

    const activeBottomTab = computed(() => {
        const url = page.url.split('?')[0];

        if (institutions.value.isActive) return 'institutions';
        if (legislatif.value.isActive) return 'lois';
        if (agir.value.isActive || comprendre.value.isActive || donnees.value.isActive) return 'plus';
        if (url.startsWith('/representants') || url.startsWith('/parlement/comparaison')) return 'mes-elus';
        if (url === '/' || url === '/dashboard') return 'home';
        return null;
    });

    const bottomTabs = computed(() => [
        {
            key: 'home',
            label: 'Accueil',
            icon: 'home',
            emoji: '🏠',
            href: isAuthenticated.value ? route('dashboard') : route('home'),
            isActive: activeBottomTab.value === 'home',
            sheet: null,
        },
        {
            key: 'mes-elus',
            label: 'Mes Elus',
            icon: 'user-group',
            emoji: '👤',
            href: route('representants.mes-representants'),
            isActive: activeBottomTab.value === 'mes-elus',
            sheet: null,
        },
        {
            key: 'institutions',
            label: 'Institutions',
            icon: 'building-library',
            emoji: '🏛️',
            href: null,
            isActive: activeBottomTab.value === 'institutions',
            sheet: 'institutions',
        },
        {
            key: 'lois',
            label: 'Lois',
            icon: 'scale',
            emoji: '⚖️',
            href: null,
            isActive: activeBottomTab.value === 'lois',
            sheet: 'lois',
        },
        {
            key: 'plus',
            label: 'Plus',
            icon: 'bars-3',
            emoji: '☰',
            href: null,
            isActive: activeBottomTab.value === 'plus',
            sheet: 'plus',
        },
    ]);

    const mobileSheetSections = computed(() => ({
        institutions: [
            {
                label: 'Assemblee Nationale',
                items: institutions.value.columns[0].items,
            },
            {
                label: 'Senat',
                items: institutions.value.columns[1].items,
            },
            {
                label: 'Gouvernement',
                items: institutions.value.columns[2].items,
            },
        ],
        lois: [
            {
                label: 'Legislatif',
                items: legislatif.value.items.filter(i => !i.divider),
            },
        ],
        plus: [
            {
                label: 'Agir & Participer',
                items: agir.value.items.filter(i => !i.divider),
            },
            {
                label: 'Comprendre',
                items: comprendre.value.items.filter(i => !i.divider),
            },
            {
                label: 'Donnees & Statistiques',
                items: donnees.value.items,
            },
            ...(isAuthenticated.value
                ? [{
                    label: 'Mon compte',
                    items: [
                        { href: route('profile.edit'), icon: '👤', title: 'Mon Profil' },
                        { href: route('profile.elus-suivis'), icon: '🔔', title: 'Elus suivis' },
                        { href: route('profile.gamification'), icon: '🏆', title: 'Mes Succes' },
                        ...(user.value?.roles?.includes('moderator') || user.value?.roles?.includes('admin')
                            ? [{ href: route('moderation.dashboard'), icon: '🛡️', title: 'Moderation' }]
                            : []),
                        ...(user.value?.roles?.includes('admin')
                            ? [{ href: route('admin.dashboard'), icon: '⚙️', title: 'Administration' }]
                            : []),
                    ],
                }]
                : []),
        ],
    }));

    return {
        user,
        isAuthenticated,
        desktopSections,
        mesElus,
        institutions,
        legislatif,
        agir,
        comprendre,
        donnees,
        bottomTabs,
        mobileSheetSections,
    };
}
