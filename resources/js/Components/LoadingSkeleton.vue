<script setup>
import { computed } from 'vue';

const props = defineProps({
  type: {
    type: String,
    default: 'card', // card, list, text, avatar, button
    validator: (value) => ['card', 'list', 'text', 'avatar', 'button', 'table'].includes(value),
  },
  count: {
    type: Number,
    default: 1,
  },
  height: {
    type: String,
    default: null,
  },
  width: {
    type: String,
    default: '100%',
  },
  rounded: {
    type: Boolean,
    default: true,
  },
  animated: {
    type: Boolean,
    default: true,
  },
});

const skeletonStyle = computed(() => ({
  height: props.height,
  width: props.width,
}));
</script>

<template>
  <div class="loading-skeleton-container">
    <div
      v-for="index in count"
      :key="index"
      :class="[
        'skeleton-item',
        `skeleton-${type}`,
        { 'skeleton-animated': animated },
        { 'skeleton-rounded': rounded }
      ]"
    >
      <!-- Card Skeleton -->
      <template v-if="type === 'card'">
        <div class="skeleton-card">
          <div class="skeleton-image" />
          <div class="skeleton-content">
            <div class="skeleton-line skeleton-title" />
            <div class="skeleton-line skeleton-subtitle" />
            <div class="skeleton-line skeleton-text" />
            <div class="skeleton-line skeleton-text short" />
          </div>
        </div>
      </template>

      <!-- List Skeleton -->
      <template v-else-if="type === 'list'">
        <div class="skeleton-list-item">
          <div class="skeleton-avatar" />
          <div class="skeleton-list-content">
            <div class="skeleton-line skeleton-title" />
            <div class="skeleton-line skeleton-text short" />
          </div>
        </div>
      </template>

      <!-- Text Skeleton -->
      <template v-else-if="type === 'text'">
        <div class="skeleton-text-block">
          <div class="skeleton-line" />
          <div class="skeleton-line" />
          <div class="skeleton-line short" />
        </div>
      </template>

      <!-- Avatar Skeleton -->
      <template v-else-if="type === 'avatar'">
        <div class="skeleton-avatar-only" />
      </template>

      <!-- Button Skeleton -->
      <template v-else-if="type === 'button'">
        <div class="skeleton-button" :style="skeletonStyle" />
      </template>

      <!-- Table Skeleton -->
      <template v-else-if="type === 'table'">
        <div class="skeleton-table">
          <div class="skeleton-table-row" v-for="row in 5" :key="row">
            <div class="skeleton-table-cell" v-for="col in 4" :key="col">
              <div class="skeleton-line" />
            </div>
          </div>
        </div>
      </template>
    </div>
  </div>
</template>

<style scoped>
.loading-skeleton-container {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.skeleton-item {
  background: white;
  border-radius: 8px;
}

.skeleton-rounded {
  border-radius: 12px;
}

/* Base skeleton element */
.skeleton-line,
.skeleton-image,
.skeleton-avatar,
.skeleton-avatar-only,
.skeleton-button {
  background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
  background-size: 200% 100%;
}

.skeleton-animated .skeleton-line,
.skeleton-animated .skeleton-image,
.skeleton-animated .skeleton-avatar,
.skeleton-animated .skeleton-avatar-only,
.skeleton-animated .skeleton-button {
  animation: shimmer 1.5s infinite;
}

@keyframes shimmer {
  0% {
    background-position: -200% 0;
  }
  100% {
    background-position: 200% 0;
  }
}

/* Card Skeleton */
.skeleton-card {
  padding: 16px;
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.skeleton-image {
  width: 100%;
  height: 200px;
  border-radius: 8px;
}

.skeleton-content {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.skeleton-line {
  height: 16px;
  border-radius: 4px;
  width: 100%;
}

.skeleton-title {
  height: 24px;
  width: 70%;
}

.skeleton-subtitle {
  height: 20px;
  width: 50%;
}

.skeleton-text {
  height: 14px;
  width: 90%;
}

.skeleton-text.short {
  width: 60%;
}

/* List Skeleton */
.skeleton-list-item {
  padding: 16px;
  display: flex;
  align-items: center;
  gap: 16px;
  border-bottom: 1px solid #f0f0f0;
}

.skeleton-avatar {
  width: 48px;
  height: 48px;
  border-radius: 50%;
  flex-shrink: 0;
}

.skeleton-list-content {
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 8px;
}

/* Text Block Skeleton */
.skeleton-text-block {
  padding: 16px;
  display: flex;
  flex-direction: column;
  gap: 12px;
}

/* Avatar Only */
.skeleton-avatar-only {
  width: 64px;
  height: 64px;
  border-radius: 50%;
}

/* Button Skeleton */
.skeleton-button {
  height: 40px;
  border-radius: 8px;
}

/* Table Skeleton */
.skeleton-table {
  width: 100%;
  border: 1px solid #f0f0f0;
  border-radius: 8px;
  overflow: hidden;
}

.skeleton-table-row {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 16px;
  padding: 16px;
  border-bottom: 1px solid #f0f0f0;
}

.skeleton-table-row:last-child {
  border-bottom: none;
}

.skeleton-table-cell .skeleton-line {
  height: 12px;
}

/* Dark mode */
@media (prefers-color-scheme: dark) {
  .skeleton-item {
    background: #1e293b;
  }

  .skeleton-line,
  .skeleton-image,
  .skeleton-avatar,
  .skeleton-avatar-only,
  .skeleton-button {
    background: linear-gradient(90deg, #334155 25%, #475569 50%, #334155 75%);
    background-size: 200% 100%;
  }

  .skeleton-list-item {
    border-bottom-color: #334155;
  }

  .skeleton-table {
    border-color: #334155;
  }

  .skeleton-table-row {
    border-bottom-color: #334155;
  }
}

/* Responsive */
@media (max-width: 640px) {
  .skeleton-card {
    padding: 12px;
  }

  .skeleton-image {
    height: 150px;
  }

  .skeleton-list-item {
    padding: 12px;
  }

  .skeleton-avatar {
    width: 40px;
    height: 40px;
  }
}
</style>

