<script setup>
defineProps({
    cols: { type: Number, default: 4 },
});
</script>

<template>
    <div
        class="grid gap-4"
        :class="{
            'grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4': cols === 4,
            'grid-cols-1 sm:grid-cols-2 lg:grid-cols-3': cols === 3,
            'grid-cols-1 sm:grid-cols-2': cols === 2,
        }"
    >
        <slot />
    </div>
</template>
