import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';

export function useNavigation() {
    const page = usePage();
    const user = computed(() => page.props.auth?.user);
    const isAuthenticated = computed(() => !!user.value);

    const url = computed(() => page.url.split('?')[0]);

    const mesElus = computed(() => ({
        key: 'mes-elus',
        label: 'Mes Elus',
        icon: 'user-group',
        emoji: '\u{1F464}',
        activeColor: 'indigo',
        isActive: url.value.startsWith('/representants') || url.value.startsWith('/parlement/comparaison'),
        items: [
            { href: route('representants.mes-representants'), icon: '\u{1F4CD}', title: 'Mes Representants', description: 'Selon votre localisation' },
            { href: route('representants.deputes.index'), icon: '\u{1F3DB}\uFE0F', title: 'Trouver un depute', description: '577 elus', badge: '577', badgeColor: 'indigo' },
            { href: route('representants.senateurs.index'), icon: '\u{1F534}', title: 'Trouver un senateur', description: '348 elus', badge: '348', badgeColor: 'red' },
            ...(isAuthenticated.value ? [{ href: route('profile.elus-suivis'), icon: '\u{1F514}', title: 'Elus suivis', description: 'Vos alertes personnalisees' }] : []),
            { href: route('parlement.comparaison'), icon: '\u{1F4CA}', title: 'Comparaison Elus', description: 'Deputes, senateurs, maires' },
        ],
    }));

    const institutions = computed(() => ({
        key: 'institutions',
        label: 'Institutions',
        icon: 'building-library',
        emoji: '\u{1F3DB}\uFE0F',
        activeColor: 'sky',
        isActive:
            url.value.startsWith('/representants') ||
            url.value.startsWith('/parlement/comparaison') ||
            url.value.startsWith('/legislation/scrutins') ||
            url.value.startsWith('/legislation/groupes') ||
            url.value.startsWith('/questions') ||
            url.value.startsWith('/debats') ||
            url.value.startsWith('/gouvernement') ||
            url.value.startsWith('/parlement/calendrier'),
        columns: [
            {
                key: 'an',
                label: 'Assemblee Nationale',
                img: "/images/Logo_de_l'Assembl\u00E9e_nationale_fran\u00E7aise.svg",
                color: 'indigo',
                items: [
                    { href: route('representants.deputes.index'), icon: '\u{1F465}', title: 'Deputes', description: '577 elus', badge: '577', badgeColor: 'indigo' },
                    { href: route('legislation.scrutins.index'), icon: '\u{1F5F3}\uFE0F', title: 'Scrutins AN', description: 'Votes publics' },
                    { href: route('questions.index'), icon: '\u2753', title: 'Questions au Gouv.', description: 'Interpellations' },
                ],
            },
            {
                key: 'senat',
                label: 'Senat',
                img: '/images/Logo_du_S\u00E9nat_Republique_fran\u00E7aise.svg',
                color: 'rose',
                items: [
                    { href: route('representants.senateurs.index'), icon: '\u{1F465}', title: 'Senateurs', description: '348 elus', badge: '348', badgeColor: 'red' },
                    { href: route('legislation.scrutins-senat.index'), icon: '\u{1F5F3}\uFE0F', title: 'Scrutins Senat', description: 'Votes publics' },
                    { href: route('debats.senat.index'), icon: '\u{1F4AC}', title: 'Debats en seance', description: 'Comptes-rendus' },
                ],
            },
            {
                key: 'gouvernement',
                label: 'Gouvernement',
                img: '/images/Logo_de_la_pr\u00E9sidence_de_la_R\u00E9publique_(2018).svg',
                color: 'amber',
                items: [
                    { href: route('gouvernement.president'), icon: '\u{1F3DB}\uFE0F', title: 'President de la Republique', description: 'Chef de l\'Etat' },
                    { href: route('gouvernement.index'), icon: '\u{1F454}', title: 'Composition', description: 'Ministres et ministeres' },
                    { href: route('legislation.groupes.index'), icon: '\u{1F3A8}', title: 'Groupes politiques', description: 'AN & Senat' },
                    { href: route('parlement.calendrier.index'), icon: '\u{1F4C5}', title: 'Calendrier parlementaire', description: 'Seances et commissions' },
                ],
            },
        ],
    }));

    const legislatif = computed(() => ({
        key: 'legislatif',
        label: 'Legislatif',
        icon: 'scale',
        emoji: '\u2696\uFE0F',
        activeColor: 'sky',
        isActive:
            url.value.startsWith('/lois') ||
            url.value.startsWith('/legislation/hub') ||
            url.value.startsWith('/legislation/thematiques') ||
            url.value.startsWith('/legislation/constitution') ||
            url.value.startsWith('/tags') ||
            url.value.startsWith('/budget-etat') ||
            url.value.startsWith('/documents'),
        items: [
            { href: route('lois.index'), icon: '\u{1F4DC}', title: 'Lois en cours', description: 'Parcours legislatif' },
            { href: route('legislation.constitution'), icon: '\u{1F4D6}', title: 'La Constitution', description: 'Texte fondamental de la Republique' },
            { href: route('tags.index'), icon: '\u{1F3F7}\uFE0F', title: 'Thematiques', description: 'Par domaine' },
            { href: route('budget-etat.index'), icon: '\u{1F4B0}', title: 'Budget de l\'Etat', description: 'Recettes et depenses' },
            { href: route('documents.index'), icon: '\u{1F4C4}', title: 'Documents publics', description: 'Officiels verifies' },
        ],
    }));

    const agir = computed(() => ({
        key: 'agir',
        label: 'Agir',
        icon: 'hand-raised',
        emoji: '\u{1F4A1}',
        activeColor: 'emerald',
        isActive:
            url.value.startsWith('/participation') ||
            url.value.startsWith('/budget') ||
            url.value.startsWith('/elections'),
        items: [
            { href: route('participation.ideas.index'), icon: '\u{1F4AC}', title: 'Idees citoyennes', description: 'Propositions & debats' },
            { href: route('budget.index'), icon: '\u{1F4B0}', title: 'Budget participatif', description: 'Repartissez le budget' },
            { divider: true },
            { href: route('elections.hub'), icon: '\u{1F4C5}', title: 'Calendrier electoral', description: 'Prochaines echeances' },
            { href: route('elections.municipales.index'), icon: '\u{1F3D8}\uFE0F', title: 'Municipales 2026', description: 'Mars 2026', badge: '34 914', badgeColor: 'green' },
        ],
    }));

    const comprendre = computed(() => ({
        key: 'comprendre',
        label: 'Comprendre',
        icon: 'academic-cap',
        emoji: '\u{1F393}',
        activeColor: 'amber',
        isActive: url.value.startsWith('/democratie') || url.value.startsWith('/statistiques') || url.value.startsWith('/donnees'),
        items: [
            { href: route('democratie.index'), icon: '\u{1F393}', title: 'Hub Democratie', description: 'Vue d\'ensemble interactive' },
            { divider: true },
            { href: route('democratie.parcours-loi'), icon: '\u{1F4DC}', title: 'Parcours d\'une Loi', description: 'De l\'initiative a la promulgation' },
            { href: route('democratie.elections'), icon: '\u{1F5F3}\uFE0F', title: 'Les Elections', description: '7 types de scrutin expliques' },
            { href: route('democratie.representants'), icon: '\u{1F465}', title: 'Nos Representants', description: 'Deputes, senateurs, maires' },
            { href: route('democratie.votes'), icon: '\u{1F4CA}', title: 'Comment Votent-ils ?', description: 'Scrutins et discipline de groupe' },
            { href: route('democratie.gouvernement'), icon: '\u{1F3DB}\uFE0F', title: 'Le Gouvernement', description: 'Executif et separation des pouvoirs' },
            { href: route('democratie.conseil-constitutionnel'), icon: '\u2696\uFE0F', title: 'Le Conseil Constitutionnel', description: 'Gardien de la Constitution' },
            { divider: true, label: 'Donnees & Statistiques' },
            { href: route('statistics.france'), icon: '\u{1F5FA}\uFE0F', title: 'Statistiques France', description: 'Demographie, economie, societe' },
            { href: route('statistics.villes'), icon: '\u{1F3D8}\uFE0F', title: 'Statistiques Villes', description: 'Communes et populations' },
            { href: route('statistics.regions.index'), icon: '\u{1F5FA}\uFE0F', title: 'Statistiques Regions', description: '18 regions et departements' },
            { href: route('donnees.gouvernements'), icon: '\u{1F3DB}\uFE0F', title: 'Statistiques Gouvernement', description: 'Ministres, ministeres' },
        ],
    }));

    const donnees = computed(() => ({
        key: 'donnees',
        label: 'Donnees',
        emoji: '\u{1F4CA}',
        isActive:
            url.value.startsWith('/statistiques') ||
            url.value.startsWith('/donnees'),
        items: [
            { href: route('statistics.france'), icon: '\u{1F5FA}\uFE0F', title: 'Statistiques France', description: 'Demographie, economie, societe' },
            { href: route('statistics.villes'), icon: '\u{1F3D8}\uFE0F', title: 'Statistiques Villes', description: 'Communes et populations' },
            { href: route('statistics.regions.index'), icon: '\u{1F5FA}\uFE0F', title: 'Statistiques Regions', description: '18 regions et departements' },
            { href: route('donnees.gouvernements'), icon: '\u{1F3DB}\uFE0F', title: 'Statistiques Gouvernement', description: 'Ministres, ministeres' },
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
        const u = url.value;

        if (u === '/' || u === '/dashboard') return 'home';

        if (u.startsWith('/democratie') ||
            u.startsWith('/statistiques') ||
            u.startsWith('/donnees')) return 'comprendre';

        if (u.startsWith('/representants') ||
            u.startsWith('/parlement/comparaison') ||
            u.startsWith('/legislation/scrutins') ||
            u.startsWith('/legislation/groupes') ||
            u.startsWith('/questions') ||
            u.startsWith('/debats') ||
            u.startsWith('/gouvernement') ||
            u.startsWith('/parlement/calendrier')) return 'institutions';

        if (u.startsWith('/participation') ||
            u.startsWith('/budget') ||
            u.startsWith('/elections')) return 'agir';

        if (u.startsWith('/lois') ||
            u.startsWith('/legislation/hub') ||
            u.startsWith('/legislation/thematiques') ||
            u.startsWith('/legislation/constitution') ||
            u.startsWith('/tags') ||
            u.startsWith('/budget-etat') ||
            u.startsWith('/documents')) return 'plus';

        return null;
    });

    const bottomTabs = computed(() => [
        {
            key: 'home',
            label: 'Accueil',
            icon: 'home',
            href: isAuthenticated.value ? route('dashboard') : route('home'),
            isActive: activeBottomTab.value === 'home',
            sheet: null,
        },
        {
            key: 'comprendre',
            label: 'Comprendre',
            icon: 'academic-cap',
            href: null,
            isActive: activeBottomTab.value === 'comprendre',
            sheet: 'comprendre',
        },
        {
            key: 'institutions',
            label: 'Institutions',
            icon: 'building-library',
            href: null,
            isActive: activeBottomTab.value === 'institutions',
            sheet: 'institutions',
        },
        {
            key: 'agir',
            label: 'Agir',
            icon: 'hand-raised',
            href: null,
            isActive: activeBottomTab.value === 'agir',
            sheet: 'agir',
        },
        {
            key: 'plus',
            label: 'Plus',
            icon: 'bars-3',
            href: null,
            isActive: activeBottomTab.value === 'plus',
            sheet: 'plus',
        },
    ]);

    const mobileSheetSections = computed(() => ({
        comprendre: [
            {
                label: 'Comprendre la Democratie',
                items: comprendre.value.items.filter(i => !i.divider),
            },
            {
                label: 'Donnees & Statistiques',
                items: donnees.value.items,
            },
        ],
        institutions: [
            {
                label: 'Mes Elus',
                items: [
                    { href: route('representants.mes-representants'), icon: '\u{1F4CD}', title: 'Mes Representants', description: 'Selon votre localisation' },
                    ...(isAuthenticated.value ? [{ href: route('profile.elus-suivis'), icon: '\u{1F514}', title: 'Elus suivis', description: 'Vos alertes personnalisees' }] : []),
                    { href: route('parlement.comparaison'), icon: '\u{1F4CA}', title: 'Comparaison', description: 'Deputes, senateurs, maires' },
                ],
            },
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
        agir: [
            {
                label: 'Agir & Participer',
                items: agir.value.items.filter(i => !i.divider),
            },
        ],
        plus: [
            {
                label: 'Legislatif',
                items: legislatif.value.items.filter(i => !i.divider),
            },
            ...(isAuthenticated.value
                ? [{
                    label: 'Mon compte',
                    items: [
                        { href: route('profile.edit'), icon: '\u{1F464}', title: 'Mon Profil' },
                        { href: route('profile.elus-suivis'), icon: '\u{1F514}', title: 'Elus suivis' },
                        { href: route('profile.gamification'), icon: '\u{1F3C6}', title: 'Mes Succes' },
                        ...(user.value?.roles?.includes('moderator') || user.value?.roles?.includes('admin')
                            ? [{ href: route('moderation.dashboard'), icon: '\u{1F6E1}\uFE0F', title: 'Moderation' }]
                            : []),
                        ...(user.value?.roles?.includes('admin')
                            ? [{ href: route('admin.dashboard'), icon: '\u2699\uFE0F', title: 'Administration' }]
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
