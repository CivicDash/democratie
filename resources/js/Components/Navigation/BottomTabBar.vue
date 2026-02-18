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
    institutions: 'Institutions',
    lois: 'Legislatif',
    plus: 'Menu',
};

router.on('navigate', () => { activeSheet.value = null; });
</script>

<template>
    <div class="lg:hidden">
        <!-- Bottom Tab Bar -->
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
                    <!-- Active indicator dot -->
                    <span
                        v-if="(tab.isActive && !activeSheet) || activeSheet === tab.sheet"
                        class="absolute top-1 w-1.5 h-1.5 rounded-full bg-indigo-600 dark:bg-indigo-400"
                    />

                    <!-- Icons (inline SVG for crisp rendering) -->
                    <svg v-if="tab.icon === 'home'" class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12l8.954-8.955a1.126 1.126 0 011.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25" />
                    </svg>
                    <svg v-else-if="tab.icon === 'user-group'" class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 003.741-.479 3 3 0 00-4.682-2.72m.94 3.198l.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0112 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 016 18.719m12 0a5.971 5.971 0 00-.941-3.197m0 0A5.995 5.995 0 0012 12.75a5.995 5.995 0 00-5.058 2.772m0 0a3 3 0 00-4.681 2.72 8.986 8.986 0 003.74.477m.94-3.197a5.971 5.971 0 00-.94 3.197M15 6.75a3 3 0 11-6 0 3 3 0 016 0zm6 3a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0zm-13.5 0a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0z" />
                    </svg>
                    <svg v-else-if="tab.icon === 'building-library'" class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 21v-8.25M15.75 21v-8.25M8.25 21v-8.25M3 9l9-6 9 6m-1.5 12V10.332A48.36 48.36 0 0012 9.75c-2.551 0-5.056.2-7.5.582V21M3 21h18M12 6.75h.008v.008H12V6.75z" />
                    </svg>
                    <svg v-else-if="tab.icon === 'scale'" class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v17.25m0 0c-1.472 0-2.882.265-4.185.75M12 20.25c1.472 0 2.882.265 4.185.75M18.75 4.97A48.416 48.416 0 0012 4.5c-2.291 0-4.545.16-6.75.47m13.5 0c1.01.143 2.01.317 3 .52m-3-.52l2.62 10.726c.122.499-.106 1.028-.589 1.202a5.988 5.988 0 01-2.031.352 5.988 5.988 0 01-2.031-.352c-.483-.174-.711-.703-.59-1.202L18.75 4.971zm-16.5.52c.99-.203 1.99-.377 3-.52m0 0l2.62 10.726c.122.499-.106 1.028-.589 1.202a5.989 5.989 0 01-2.031.352 5.989 5.989 0 01-2.031-.352c-.483-.174-.711-.703-.59-1.202L5.25 4.971z" />
                    </svg>
                    <svg v-else-if="tab.icon === 'bars-3'" class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
                    </svg>

                    <span class="text-xs font-medium leading-tight">{{ tab.label }}</span>
                </button>
            </div>
        </nav>

        <!-- Sheets -->
        <NavSheet
            v-for="sheetKey in ['institutions', 'lois', 'plus']"
            :key="sheetKey"
            :modelValue="activeSheet === sheetKey"
            @update:modelValue="val => { if (!val) closeSheet(); }"
            :title="sheetTitles[sheetKey]"
            :sections="mobileSheetSections[sheetKey] || []"
        />
    </div>
</template>
