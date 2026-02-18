<script setup>
import MegaMenuLink from '@/Components/MegaMenuLink.vue';

defineProps({
    columns: { type: Array, required: true },
});
</script>

<template>
    <div class="grid grid-cols-3 gap-0 divide-x divide-gray-100 dark:divide-gray-700 p-1 min-w-[720px]">
        <div v-for="col in columns" :key="col.key" class="px-3 py-2">
            <!-- Column header -->
            <div class="flex items-center gap-2 mb-3 px-2">
                <img
                    v-if="col.img"
                    :src="col.img"
                    :alt="col.label"
                    class="w-5 h-5 object-contain"
                />
                <span class="text-xs font-bold uppercase tracking-wider"
                    :class="{
                        'text-indigo-600 dark:text-indigo-400': col.color === 'indigo',
                        'text-rose-600 dark:text-rose-400': col.color === 'rose',
                        'text-amber-600 dark:text-amber-400': col.color === 'amber',
                    }"
                >
                    {{ col.label }}
                </span>
            </div>

            <!-- Items -->
            <div class="space-y-0.5">
                <MegaMenuLink
                    v-for="item in col.items"
                    :key="item.href"
                    :href="item.href"
                    :icon="item.icon"
                    :title="item.title"
                    :description="item.description || ''"
                    :badge="item.badge"
                    :badge-color="item.badgeColor || 'gray'"
                />
            </div>
        </div>
    </div>
</template>
