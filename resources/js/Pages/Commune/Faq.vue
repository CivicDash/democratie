<script setup>
import CommuneLayout from '@/Layouts/CommuneLayout.vue';
import ShareButtons from '@/Components/Commune/ShareButtons.vue';
import { computed, ref } from 'vue';

const props = defineProps({
    ville: Object,
    page: Object,
    faq: Array,
    seo: Object,
});

const openIndex = ref(null);

const toggle = (i) => {
    openIndex.value = openIndex.value === i ? null : i;
};

const faqJsonLd = computed(() => JSON.stringify({
    '@context': 'https://schema.org',
    '@type': 'FAQPage',
    mainEntity: props.faq.map(item => ({
        '@type': 'Question',
        name: item.question,
        acceptedAnswer: {
            '@type': 'Answer',
            text: item.answer,
        },
    })),
}));
</script>

<template>
    <CommuneLayout :ville="ville" :page="page" :seo="seo" :titre="`FAQ - ${ville.nom}`">
        <component :is="'script'" type="application/ld+json" v-text="faqJsonLd" />

        <div class="max-w-3xl mx-auto px-4 sm:px-6 py-8">
            <h1 class="text-3xl font-bold text-slate-900 dark:text-white mb-2">Questions frequentes</h1>
            <p class="text-slate-500 dark:text-slate-400 mb-8">Tout ce qu'il faut savoir sur {{ ville.nom }}</p>

            <div class="space-y-3">
                <div
                    v-for="(item, i) in faq"
                    :key="i"
                    class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 overflow-hidden transition-shadow"
                    :class="openIndex === i ? 'shadow-md ring-1 ring-blue-200 dark:ring-blue-800' : ''"
                >
                    <button
                        @click="toggle(i)"
                        class="w-full flex items-center justify-between px-5 py-4 text-left"
                    >
                        <span class="font-semibold text-slate-900 dark:text-white pr-4">{{ item.question }}</span>
                        <svg
                            class="w-5 h-5 text-slate-400 flex-shrink-0 transition-transform duration-200"
                            :class="openIndex === i ? 'rotate-180' : ''"
                            fill="none" stroke="currentColor" viewBox="0 0 24 24"
                        >
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                        </svg>
                    </button>
                    <div v-show="openIndex === i" class="px-5 pb-4 text-slate-600 dark:text-slate-300 leading-relaxed border-t border-slate-100 dark:border-slate-700 pt-3">
                        {{ item.answer }}
                    </div>
                </div>
            </div>

            <div class="mt-8 pt-6 border-t border-slate-200 dark:border-slate-700">
                <ShareButtons :url="seo?.url || ''" :title="`FAQ - ${ville.nom}`" :description="`Questions frequentes sur ${ville.nom}`" />
            </div>
        </div>
    </CommuneLayout>
</template>
