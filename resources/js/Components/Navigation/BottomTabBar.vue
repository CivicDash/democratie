<script setup>
import { ref } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import NavSheet from './NavSheet.vue';
import { useNavigation } from '@/composables/useNavigation';

const { bottomTabs, mobileSheetSections } = useNavigation();

const activeSheet = ref(null);

function onTabClick(tab) {
    if (tab.href) {
        activeSheet.value = null;
        router.visit(tab.href);
        return;
    }
    if (tab.sheet) {
        activeSheet.value = activeSheet.value === tab.sheet ? null : tab.sheet;
    }
}

function closeSheet() {
    activeSheet.value = null;
}

const sheetTitles = {
    comprendre: 'Comprendre',
    institutions: 'Institutions',
    agir: 'Agir',
    plus: 'Menu',
};

router.on('navigate', () => { activeSheet.value = null; });
</script>

<template>
    <div class="lg:hidden">
        <nav
            class="fixed inset-x-0 bottom-0 z-50 bg-white dark:bg-gray-800 border-t border-gray-200 dark:border-gray-700 shadow-[0_-2px_10px_rgba(0,0,0,0.06)]"
            style="padding-bottom: env(safe-area-inset-bottom, 0px);"
            role="tablist"
            aria-label="Navigation principale"
        >
            <div class="flex items-stretch justify-around h-16 max-w-xl mx-auto">
                <button
                    v-for="tab in bottomTabs"
                    :key="tab.key"
                    @click="onTabClick(tab)"
                    role="tab"
                    :aria-selected="tab.isActive || activeSheet === tab.sheet"
                    :aria-label="tab.label"
                    :aria-current="(tab.isActive && !activeSheet) ? 'page' : undefined"
                    class="flex-1 flex flex-col items-center justify-center gap-1 relative transition-colors min-h-[44px] focus-visible:outline-2 focus-visible:outline-offset-[-2px] focus-visible:outline-indigo-500 focus-visible:rounded-lg"
                    :class="(tab.isActive && !activeSheet) || activeSheet === tab.sheet
                        ? 'text-indigo-600 dark:text-indigo-400'
                        : 'text-gray-500 dark:text-gray-400'"
                >
                    <span
                        v-if="(tab.isActive && !activeSheet) || activeSheet === tab.sheet"
                        class="absolute top-1 w-1.5 h-1.5 rounded-full bg-indigo-600 dark:bg-indigo-400"
                    />

                    <!-- Home -->
                    <svg v-if="tab.icon === 'home'" class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12l8.954-8.955a1.126 1.126 0 011.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25" />
                    </svg>
                    <!-- Academic Cap (Comprendre) -->
                    <svg v-else-if="tab.icon === 'academic-cap'" class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4.26 10.147a60.438 60.438 0 00-.491 6.347A48.62 48.62 0 0112 20.904a48.62 48.62 0 018.232-4.41 60.46 60.46 0 00-.491-6.347m-15.482 0a50.636 50.636 0 00-2.658-.813A59.906 59.906 0 0112 3.493a59.903 59.903 0 0110.399 5.84c-.896.248-1.783.52-2.658.814m-15.482 0A50.717 50.717 0 0112 13.489a50.702 50.702 0 017.74-3.342M6.75 15a.75.75 0 100-1.5.75.75 0 000 1.5zm0 0v-3.675A55.378 55.378 0 0112 8.443m-7.007 11.55A5.981 5.981 0 006.75 15.75v-1.5" />
                    </svg>
                    <!-- Building Library (Institutions) -->
                    <svg v-else-if="tab.icon === 'building-library'" class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 21v-8.25M15.75 21v-8.25M8.25 21v-8.25M3 9l9-6 9 6m-1.5 12V10.332A48.36 48.36 0 0012 9.75c-2.551 0-5.056.2-7.5.582V21M3 21h18M12 6.75h.008v.008H12V6.75z" />
                    </svg>
                    <!-- Hand Raised (Agir) -->
                    <svg v-else-if="tab.icon === 'hand-raised'" class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10.05 4.575a1.575 1.575 0 10-3.15 0v3m3.15-3v-1.5a1.575 1.575 0 013.15 0v1.5m-3.15 0l.075 5.925m3.075.75V4.575m0 0a1.575 1.575 0 013.15 0V15M6.9 7.575a1.575 1.575 0 10-3.15 0v8.175a6.75 6.75 0 006.75 6.75h2.018a5.25 5.25 0 003.712-1.538l1.732-1.732a5.25 5.25 0 001.538-3.712l.003-2.024a.668.668 0 01.198-.471 1.575 1.575 0 10-2.228-2.228 3.818 3.818 0 00-1.12 2.687M6.9 7.575V12m6.27 4.318A4.49 4.49 0 0116.35 15m.002 0h-.002" />
                    </svg>
                    <!-- Bars 3 (Plus) -->
                    <svg v-else-if="tab.icon === 'bars-3'" class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
                    </svg>

                    <span class="text-xs font-medium leading-tight">{{ tab.label }}</span>
                </button>
            </div>
        </nav>

        <NavSheet
            v-for="sheetKey in ['comprendre', 'institutions', 'agir', 'plus']"
            :key="sheetKey"
            :modelValue="activeSheet === sheetKey"
            @update:modelValue="val => { if (!val) closeSheet(); }"
            :title="sheetTitles[sheetKey]"
            :sections="mobileSheetSections[sheetKey] || []"
        />
    </div>
</template>
