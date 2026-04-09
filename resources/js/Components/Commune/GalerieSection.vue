<script setup>
import { ref } from 'vue';

defineProps({
    images: { type: Array, default: () => [] },
});

const lightboxOpen = ref(false);
const lightboxIndex = ref(0);

const openLightbox = (index) => {
    lightboxIndex.value = index;
    lightboxOpen.value = true;
    document.body.style.overflow = 'hidden';
};

const closeLightbox = () => {
    lightboxOpen.value = false;
    document.body.style.overflow = '';
};

const prev = () => {
    lightboxIndex.value = Math.max(0, lightboxIndex.value - 1);
};

const next = (total) => {
    lightboxIndex.value = Math.min(total - 1, lightboxIndex.value + 1);
};

const onKeydown = (e, total) => {
    if (e.key === 'Escape') closeLightbox();
    if (e.key === 'ArrowLeft') prev();
    if (e.key === 'ArrowRight') next(total);
};
</script>

<template>
    <section v-if="images?.length" class="mb-8">
        <h2 class="text-xl font-bold text-slate-900 dark:text-white mb-4 flex items-center gap-2">
            <span class="w-1 h-6 bg-blue-600 rounded-full"></span>
            Galerie
        </h2>

        <!-- Carousel horizontal -->
        <div class="flex gap-3 overflow-x-auto pb-3 -mx-4 px-4 snap-x snap-mandatory scrollbar-hide">
            <button
                v-for="(img, idx) in images"
                :key="img.id || idx"
                @click="openLightbox(idx)"
                class="flex-shrink-0 w-56 h-40 sm:w-64 sm:h-44 rounded-xl overflow-hidden relative group snap-start"
            >
                <img
                    :src="img.image_url"
                    :alt="img.legende || 'Photo'"
                    class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
                    loading="lazy"
                />
                <div class="absolute inset-0 bg-gradient-to-t from-black/50 to-transparent opacity-0 group-hover:opacity-100 transition-opacity">
                    <div class="absolute bottom-2 left-3 right-3">
                        <p v-if="img.legende" class="text-white text-xs truncate">{{ img.legende }}</p>
                    </div>
                </div>
            </button>
        </div>

        <!-- Lightbox -->
        <Teleport to="body">
            <Transition
                enter-active-class="transition-opacity duration-200"
                enter-from-class="opacity-0"
                enter-to-class="opacity-100"
                leave-active-class="transition-opacity duration-200"
                leave-from-class="opacity-100"
                leave-to-class="opacity-0"
            >
                <div
                    v-if="lightboxOpen"
                    class="fixed inset-0 z-[100] bg-black/90 flex items-center justify-center"
                    @click.self="closeLightbox"
                    @keydown="onKeydown($event, images.length)"
                    tabindex="0"
                    ref="lightboxEl"
                >
                    <!-- Close -->
                    <button @click="closeLightbox" class="absolute top-4 right-4 text-white/80 hover:text-white p-2 z-10">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>

                    <!-- Prev -->
                    <button
                        v-if="lightboxIndex > 0"
                        @click="prev"
                        class="absolute left-4 text-white/80 hover:text-white p-2 z-10"
                    >
                        <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                        </svg>
                    </button>

                    <!-- Next -->
                    <button
                        v-if="lightboxIndex < images.length - 1"
                        @click="next(images.length)"
                        class="absolute right-4 text-white/80 hover:text-white p-2 z-10"
                    >
                        <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                        </svg>
                    </button>

                    <!-- Image -->
                    <div class="max-w-5xl max-h-[85vh] px-16">
                        <img
                            :src="images[lightboxIndex]?.image_url"
                            :alt="images[lightboxIndex]?.legende || 'Photo'"
                            class="max-w-full max-h-[80vh] object-contain rounded-lg"
                        />
                        <div v-if="images[lightboxIndex]?.legende || images[lightboxIndex]?.credit" class="text-center mt-3">
                            <p v-if="images[lightboxIndex]?.legende" class="text-white text-sm">{{ images[lightboxIndex].legende }}</p>
                            <p v-if="images[lightboxIndex]?.credit" class="text-white/60 text-xs mt-1">{{ images[lightboxIndex].credit }}</p>
                        </div>
                        <div class="text-center mt-2 text-white/50 text-xs">{{ lightboxIndex + 1 }} / {{ images.length }}</div>
                    </div>
                </div>
            </Transition>
        </Teleport>
    </section>
</template>

<style scoped>
.scrollbar-hide::-webkit-scrollbar { display: none; }
.scrollbar-hide { -ms-overflow-style: none; scrollbar-width: none; }
</style>
