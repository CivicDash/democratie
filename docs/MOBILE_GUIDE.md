# 📱 Guide Développement Mobile - CivicDash

## 🎯 Vue d'ensemble

Ce guide détaille comment adapter les pages Vue 3 de CivicDash pour une expérience mobile optimale.

**Objectif** : 70% du trafic sera mobile → Priorité absolue !

---

## 🏗️ Architecture Mobile

### Approche Mobile-First

CivicDash utilise **Tailwind CSS** avec breakpoints responsive :

```vue
<!-- Exemple structure responsive -->
<div class="
  flex flex-col sm:flex-row
  p-4 sm:p-6 lg:p-8
  text-sm sm:text-base lg:text-lg
  gap-4 sm:gap-6
">
```

### Breakpoints Tailwind

```
sm:  640px  (Mobile landscape, petites tablettes)
md:  768px  (Tablettes)
lg:  1024px (Desktop)
xl:  1280px (Large desktop)
2xl: 1536px (Extra large)
```

**Stratégie** :
- Par défaut : Mobile portrait (< 640px)
- `sm:` : Mobile landscape / Petite tablette
- `md:` : Tablette
- `lg:` : Desktop

---

## 📐 Design Patterns Mobile

### 1. Touch Targets

**Taille minimale** : 44x44px (Apple HIG / Material Design)

```vue
<!-- ❌ Mauvais : trop petit -->
<button class="px-2 py-1 text-xs">
  Voter
</button>

<!-- ✅ Bon : touch-friendly -->
<button class="px-6 py-3 text-base min-h-[44px] min-w-[44px]">
  Voter
</button>
```

### 2. Spacing Adaptatif

```vue
<!-- Container avec padding responsive -->
<div class="
  px-4 py-4         <!-- Mobile -->
  sm:px-6 sm:py-6   <!-- Tablet -->
  lg:px-8 lg:py-8   <!-- Desktop -->
">
```

### 3. Typography Scale

```vue
<!-- Titres responsive -->
<h1 class="
  text-2xl font-bold      <!-- Mobile -->
  sm:text-3xl             <!-- Tablet -->
  lg:text-4xl             <!-- Desktop -->
">
  CivicDash
</h1>

<!-- Texte body -->
<p class="
  text-sm          <!-- Mobile -->
  sm:text-base     <!-- Tablet -->
  lg:text-lg       <!-- Desktop -->
">
```

---

## 🎨 Composants Responsifs

### Navigation Mobile (Menu Burger)

```vue
<!-- components/MobileNavigation.vue -->
<template>
  <nav class="lg:hidden fixed bottom-0 inset-x-0 bg-white border-t border-gray-200 z-50">
    <div class="flex justify-around items-center h-16">
      <Link 
        v-for="item in navigation" 
        :key="item.name"
        :href="item.href"
        class="flex flex-col items-center justify-center flex-1 h-full"
        :class="{ 'text-blue-600': isActive(item.href) }"
      >
        <component :is="item.icon" class="h-6 w-6" />
        <span class="text-xs mt-1">{{ item.name }}</span>
      </Link>
    </div>
  </nav>
</template>

<script setup>
import { HomeIcon, DocumentTextIcon, ChartBarIcon, UserIcon } from '@heroicons/vue/24/outline'

const navigation = [
  { name: 'Topics', href: '/topics', icon: HomeIcon },
  { name: 'Vote', href: '/vote', icon: DocumentTextIcon },
  { name: 'Budget', href: '/budget', icon: ChartBarIcon },
  { name: 'Profil', href: '/profile', icon: UserIcon },
]
</script>
```

### Header Mobile

```vue
<!-- components/MobileHeader.vue -->
<template>
  <header class="lg:hidden sticky top-0 z-40 bg-white border-b border-gray-200">
    <div class="flex items-center justify-between px-4 h-14">
      <!-- Logo -->
      <Link href="/" class="flex items-center">
        <img src="/images/logo.svg" alt="CivicDash" class="h-8" />
      </Link>

      <!-- Actions -->
      <div class="flex items-center gap-3">
        <!-- Search -->
        <button @click="openSearch" class="p-2">
          <MagnifyingGlassIcon class="h-6 w-6" />
        </button>

        <!-- Notifications -->
        <button @click="openNotifications" class="p-2 relative">
          <BellIcon class="h-6 w-6" />
          <span v-if="unreadCount" class="absolute top-1 right-1 h-4 w-4 bg-red-500 rounded-full text-xs text-white flex items-center justify-center">
            {{ unreadCount }}
          </span>
        </button>

        <!-- Menu burger -->
        <button @click="toggleMenu" class="p-2">
          <Bars3Icon class="h-6 w-6" />
        </button>
      </div>
    </div>
  </header>
</template>
```

### Cards Mobile

```vue
<!-- Avant : Desktop-first -->
<div class="grid grid-cols-3 gap-6">
  <!-- Cards -->
</div>

<!-- Après : Mobile-first -->
<div class="
  grid 
  grid-cols-1           <!-- Mobile: 1 colonne -->
  sm:grid-cols-2        <!-- Tablet: 2 colonnes -->
  lg:grid-cols-3        <!-- Desktop: 3 colonnes -->
  gap-4 sm:gap-6        <!-- Gap adaptatif -->
">
  <!-- Cards -->
</div>
```

---

## 📄 Adaptation des Pages

### Topics/Index.vue

**Avant** (Desktop) :
```vue
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
  <div class="grid grid-cols-3 gap-6">
    <!-- Topics -->
  </div>
</div>
```

**Après** (Mobile-first) :
```vue
<template>
  <div class="
    max-w-7xl mx-auto 
    px-4 sm:px-6 lg:px-8 
    py-4 sm:py-6 lg:py-8
    pb-20 lg:pb-8  <!-- Espace pour bottom nav mobile -->
  ">
    <!-- Filtres (horizontal scroll sur mobile) -->
    <div class="mb-4 overflow-x-auto flex gap-2 pb-2 -mx-4 px-4 sm:mx-0 sm:px-0">
      <button 
        v-for="filter in filters" 
        :key="filter"
        class="
          px-4 py-2 
          whitespace-nowrap 
          rounded-full 
          text-sm font-medium
          min-h-[44px]  <!-- Touch target -->
        "
      >
        {{ filter }}
      </button>
    </div>

    <!-- Liste topics -->
    <div class="
      space-y-4        <!-- Mobile: stack vertical -->
      sm:grid sm:grid-cols-2 sm:gap-4 sm:space-y-0
      lg:grid-cols-3 lg:gap-6
    ">
      <TopicCard 
        v-for="topic in topics" 
        :key="topic.id" 
        :topic="topic"
        class="w-full"  <!-- Full width sur mobile -->
      />
    </div>
  </div>
</template>
```

### Topics/Show.vue (Détail Topic)

```vue
<template>
  <div class="min-h-screen bg-gray-50">
    <!-- Header mobile sticky -->
    <div class="lg:hidden sticky top-0 z-30 bg-white border-b px-4 py-3">
      <button @click="$inertia.visit('/topics')" class="p-2 -ml-2">
        <ChevronLeftIcon class="h-6 w-6" />
      </button>
    </div>

    <div class="
      max-w-4xl mx-auto 
      px-4 sm:px-6 lg:px-8 
      py-4 sm:py-6 lg:py-8
      pb-20 lg:pb-8
    ">
      <!-- Topic header -->
      <div class="bg-white rounded-lg shadow p-4 sm:p-6 mb-4">
        <h1 class="text-xl sm:text-2xl lg:text-3xl font-bold mb-2">
          {{ topic.title }}
        </h1>
        
        <!-- Meta info (stack sur mobile) -->
        <div class="
          flex flex-col gap-2
          sm:flex-row sm:items-center sm:justify-between
        ">
          <div class="flex items-center gap-2 text-sm text-gray-600">
            <UserIcon class="h-5 w-5" />
            <span>{{ topic.author.name }}</span>
          </div>
          <div class="flex items-center gap-2 text-sm text-gray-500">
            <ClockIcon class="h-5 w-5" />
            <span>{{ formatDate(topic.created_at) }}</span>
          </div>
        </div>

        <!-- Description -->
        <p class="mt-4 text-sm sm:text-base text-gray-700">
          {{ topic.description }}
        </p>

        <!-- Actions (full width sur mobile) -->
        <div class="
          mt-4 
          flex flex-col gap-2
          sm:flex-row sm:gap-3
        ">
          <button class="
            flex-1 sm:flex-initial
            px-6 py-3 
            bg-blue-600 text-white 
            rounded-lg font-medium
            min-h-[44px]
          ">
            Participer
          </button>
          <button class="
            flex-1 sm:flex-initial
            px-6 py-3 
            border border-gray-300 
            rounded-lg font-medium
            min-h-[44px]
          ">
            Suivre
          </button>
        </div>
      </div>

      <!-- Posts -->
      <div class="space-y-4">
        <PostCard 
          v-for="post in posts" 
          :key="post.id" 
          :post="post"
        />
      </div>
    </div>
  </div>
</template>
```

### Vote/Show.vue (Workflow Vote)

```vue
<template>
  <div class="min-h-screen bg-gray-50">
    <div class="
      max-w-2xl mx-auto 
      px-4 sm:px-6 
      py-4 sm:py-8
      pb-20 lg:pb-8
    ">
      <!-- Stepper (horizontal sur desktop, vertical sur mobile) -->
      <div class="mb-6">
        <ol class="
          flex flex-col gap-4
          sm:flex-row sm:justify-between sm:items-center
        ">
          <li 
            v-for="(step, index) in steps" 
            :key="index"
            class="
              flex items-center gap-3
              sm:flex-col sm:flex-1
            "
            :class="{ 'opacity-50': currentStep < index }"
          >
            <div class="
              flex items-center justify-center
              w-10 h-10 
              rounded-full 
              font-semibold
              min-w-[44px] min-h-[44px]  <!-- Touch target -->
            " :class="currentStep >= index ? 'bg-blue-600 text-white' : 'bg-gray-300'">
              {{ index + 1 }}
            </div>
            <span class="text-sm font-medium sm:text-center">
              {{ step }}
            </span>
          </li>
        </ol>
      </div>

      <!-- Carte vote -->
      <div class="bg-white rounded-lg shadow p-4 sm:p-6">
        <!-- Étape 1 : Demander token -->
        <div v-if="currentStep === 0">
          <h2 class="text-lg sm:text-xl font-bold mb-4">
            Demander un token de vote
          </h2>
          <p class="text-sm sm:text-base text-gray-600 mb-6">
            Pour garantir l'anonymat, nous allons vous générer un token unique.
          </p>
          <button 
            @click="requestToken"
            class="
              w-full 
              px-6 py-4 
              bg-blue-600 text-white 
              rounded-lg font-medium text-base
              min-h-[44px]
            "
          >
            Générer mon token
          </button>
        </div>

        <!-- Étape 2 : Voter -->
        <div v-if="currentStep === 1">
          <h2 class="text-lg sm:text-xl font-bold mb-4">
            Votez anonymement
          </h2>
          
          <!-- Options de vote (stack vertical sur mobile) -->
          <div class="space-y-3 mb-6">
            <button
              v-for="option in voteOptions"
              :key="option.value"
              @click="selectOption(option.value)"
              class="
                w-full
                p-4 sm:p-6
                border-2 rounded-lg
                text-left
                transition-all
                min-h-[44px]
              "
              :class="selectedOption === option.value 
                ? 'border-blue-600 bg-blue-50' 
                : 'border-gray-300 hover:border-blue-300'"
            >
              <div class="flex items-center gap-3">
                <div class="
                  w-6 h-6 
                  rounded-full border-2
                  flex items-center justify-center
                " :class="selectedOption === option.value 
                  ? 'border-blue-600' 
                  : 'border-gray-300'">
                  <div v-if="selectedOption === option.value" class="w-3 h-3 bg-blue-600 rounded-full" />
                </div>
                <span class="text-base sm:text-lg font-medium">
                  {{ option.label }}
                </span>
              </div>
            </button>
          </div>

          <button 
            @click="confirmVote"
            :disabled="!selectedOption"
            class="
              w-full 
              px-6 py-4 
              bg-blue-600 text-white 
              rounded-lg font-medium text-base
              disabled:opacity-50 disabled:cursor-not-allowed
              min-h-[44px]
            "
          >
            Confirmer mon vote
          </button>
        </div>

        <!-- Étape 3 : Confirmation -->
        <div v-if="currentStep === 2">
          <div class="text-center py-8">
            <CheckCircleIcon class="h-16 w-16 sm:h-20 sm:w-20 text-green-500 mx-auto mb-4" />
            <h2 class="text-xl sm:text-2xl font-bold mb-2">
              Vote enregistré !
            </h2>
            <p class="text-sm sm:text-base text-gray-600">
              Votre vote anonyme a été pris en compte.
            </p>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
```

### Budget/Index.vue (Allocation Budget)

```vue
<template>
  <div class="min-h-screen bg-gray-50">
    <div class="
      max-w-4xl mx-auto 
      px-4 sm:px-6 lg:px-8 
      py-4 sm:py-6 lg:py-8
      pb-20 lg:pb-8
    ">
      <div class="bg-white rounded-lg shadow p-4 sm:p-6">
        <!-- Header -->
        <div class="mb-6">
          <h1 class="text-xl sm:text-2xl font-bold mb-2">
            Budget Participatif
          </h1>
          <p class="text-sm sm:text-base text-gray-600">
            Répartissez 100% du budget entre 10 secteurs
          </p>
        </div>

        <!-- Progress bar (sticky sur mobile) -->
        <div class="
          sticky top-14 lg:static
          -mx-4 sm:mx-0
          px-4 py-3 sm:p-0 sm:mb-6
          bg-white lg:bg-transparent
          border-b lg:border-0
          z-20
        ">
          <div class="flex justify-between items-center mb-2">
            <span class="text-sm font-medium">Total alloué</span>
            <span 
              class="text-lg font-bold"
              :class="total === 100 ? 'text-green-600' : 'text-orange-600'"
            >
              {{ total }}%
            </span>
          </div>
          <div class="h-3 bg-gray-200 rounded-full overflow-hidden">
            <div 
              class="h-full transition-all duration-300"
              :class="total === 100 ? 'bg-green-600' : 'bg-orange-400'"
              :style="{ width: `${Math.min(total, 100)}%` }"
            />
          </div>
        </div>

        <!-- Secteurs (cards sur mobile, liste sur desktop) -->
        <div class="space-y-4">
          <div 
            v-for="sector in sectors" 
            :key="sector.id"
            class="
              border border-gray-200 rounded-lg 
              p-4 sm:p-5
            "
          >
            <!-- Header secteur -->
            <div class="flex items-start justify-between mb-3">
              <div class="flex-1">
                <h3 class="font-semibold text-base sm:text-lg mb-1">
                  {{ sector.name }}
                </h3>
                <p class="text-xs sm:text-sm text-gray-500">
                  {{ sector.description }}
                </p>
              </div>
              <div class="
                ml-3
                px-3 py-1 
                bg-blue-100 text-blue-700 
                rounded-full 
                font-bold text-base sm:text-lg
                min-w-[60px] text-center
              ">
                {{ allocations[sector.id] || 0 }}%
              </div>
            </div>

            <!-- Slider tactile -->
            <div class="relative">
              <!-- Range input natif (touch-friendly) -->
              <input
                type="range"
                v-model="allocations[sector.id]"
                min="0"
                max="100"
                step="1"
                class="
                  w-full h-3 
                  appearance-none 
                  bg-gray-200 rounded-full
                  cursor-pointer
                  touch-pan-x
                  [&::-webkit-slider-thumb]:appearance-none
                  [&::-webkit-slider-thumb]:w-6
                  [&::-webkit-slider-thumb]:h-6
                  [&::-webkit-slider-thumb]:bg-blue-600
                  [&::-webkit-slider-thumb]:rounded-full
                  [&::-webkit-slider-thumb]:cursor-pointer
                  [&::-moz-range-thumb]:w-6
                  [&::-moz-range-thumb]:h-6
                  [&::-moz-range-thumb]:bg-blue-600
                  [&::-moz-range-thumb]:rounded-full
                  [&::-moz-range-thumb]:border-0
                  [&::-moz-range-thumb]:cursor-pointer
                "
                @input="updateAllocation(sector.id, $event.target.value)"
              />

              <!-- Boutons +/- (mobile only) -->
              <div class="flex sm:hidden items-center justify-between mt-3 gap-2">
                <button 
                  @click="decrementAllocation(sector.id)"
                  class="
                    px-4 py-2
                    bg-gray-200 
                    rounded-lg 
                    font-bold text-lg
                    min-w-[44px] min-h-[44px]
                  "
                >
                  −
                </button>
                <button 
                  @click="incrementAllocation(sector.id)"
                  class="
                    px-4 py-2
                    bg-gray-200 
                    rounded-lg 
                    font-bold text-lg
                    min-w-[44px] min-h-[44px]
                  "
                >
                  +
                </button>
              </div>
            </div>
          </div>
        </div>

        <!-- Actions (sticky bottom sur mobile) -->
        <div class="
          sticky bottom-16 lg:static
          -mx-4 sm:mx-0
          mt-6
          p-4 sm:p-0
          bg-white lg:bg-transparent
          border-t lg:border-0
          z-20
        ">
          <button 
            @click="submitAllocations"
            :disabled="total !== 100"
            class="
              w-full 
              px-6 py-4 
              bg-blue-600 text-white 
              rounded-lg font-medium text-base
              disabled:opacity-50 disabled:cursor-not-allowed
              min-h-[44px]
            "
          >
            <span v-if="total === 100">Enregistrer mon allocation</span>
            <span v-else>Allouez 100% pour continuer ({{ 100 - total }}% restant)</span>
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'

const allocations = ref({})

const total = computed(() => {
  return Object.values(allocations.value).reduce((sum, val) => sum + parseInt(val || 0), 0)
})

const updateAllocation = (sectorId, value) => {
  allocations.value[sectorId] = parseInt(value)
}

const incrementAllocation = (sectorId) => {
  const current = allocations.value[sectorId] || 0
  if (current < 100 && total.value < 100) {
    allocations.value[sectorId] = current + 1
  }
}

const decrementAllocation = (sectorId) => {
  const current = allocations.value[sectorId] || 0
  if (current > 0) {
    allocations.value[sectorId] = current - 1
  }
}
</script>
```

---

## 🎨 Composants UI Mobiles

### Modal Mobile (Fullscreen)

```vue
<!-- components/MobileModal.vue -->
<template>
  <Teleport to="body">
    <Transition name="modal">
      <div v-if="show" class="fixed inset-0 z-50 lg:flex lg:items-center lg:justify-center">
        <!-- Overlay -->
        <div 
          class="fixed inset-0 bg-black/50" 
          @click="close"
        />

        <!-- Content (fullscreen sur mobile, modal sur desktop) -->
        <div class="
          fixed inset-0 lg:relative lg:inset-auto
          lg:max-w-2xl lg:w-full
          bg-white
          lg:rounded-lg lg:shadow-xl
          flex flex-col
          overflow-hidden
        ">
          <!-- Header -->
          <div class="
            flex items-center justify-between
            px-4 py-3 lg:px-6 lg:py-4
            border-b
          ">
            <h2 class="text-lg lg:text-xl font-semibold">
              {{ title }}
            </h2>
            <button 
              @click="close"
              class="p-2 hover:bg-gray-100 rounded-lg"
            >
              <XMarkIcon class="h-6 w-6" />
            </button>
          </div>

          <!-- Body (scrollable) -->
          <div class="flex-1 overflow-y-auto p-4 lg:p-6">
            <slot />
          </div>

          <!-- Footer (sticky) -->
          <div v-if="$slots.footer" class="
            border-t 
            p-4 lg:p-6
            bg-white
          ">
            <slot name="footer" />
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<style scoped>
.modal-enter-active,
.modal-leave-active {
  transition: opacity 0.3s ease;
}

.modal-enter-from,
.modal-leave-to {
  opacity: 0;
}
</style>
```

### Bottom Sheet Mobile

```vue
<!-- components/BottomSheet.vue -->
<template>
  <Teleport to="body">
    <Transition name="sheet">
      <div v-if="show" class="lg:hidden fixed inset-0 z-50">
        <div 
          class="fixed inset-0 bg-black/50" 
          @click="close"
        />
        
        <div 
          class="
            fixed inset-x-0 bottom-0
            bg-white 
            rounded-t-3xl 
            shadow-xl
            max-h-[80vh]
            flex flex-col
          "
          @touchstart="handleTouchStart"
          @touchmove="handleTouchMove"
          @touchend="handleTouchEnd"
        >
          <!-- Handle -->
          <div class="flex justify-center pt-3 pb-2">
            <div class="w-12 h-1 bg-gray-300 rounded-full" />
          </div>

          <!-- Content -->
          <div class="flex-1 overflow-y-auto px-4 pb-4">
            <slot />
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<script setup>
import { ref } from 'vue'

const emit = defineEmits(['close'])

let startY = 0
let currentY = 0

const handleTouchStart = (e) => {
  startY = e.touches[0].clientY
}

const handleTouchMove = (e) => {
  currentY = e.touches[0].clientY
}

const handleTouchEnd = () => {
  if (currentY - startY > 100) { // Swipe down > 100px
    emit('close')
  }
}
</script>

<style scoped>
.sheet-enter-active {
  transition: transform 0.3s ease-out;
}

.sheet-leave-active {
  transition: transform 0.3s ease-in;
}

.sheet-enter-from,
.sheet-leave-to {
  transform: translateY(100%);
}
</style>
```

### Pull to Refresh

```vue
<!-- components/PullToRefresh.vue -->
<template>
  <div 
    class="relative overflow-hidden"
    @touchstart="handleTouchStart"
    @touchmove="handleTouchMove"
    @touchend="handleTouchEnd"
  >
    <!-- Indicateur pull -->
    <div 
      v-show="pulling"
      class="absolute inset-x-0 top-0 flex items-center justify-center h-16 text-blue-600"
      :style="{ transform: `translateY(${pullDistance}px)` }"
    >
      <ArrowPathIcon class="h-6 w-6 animate-spin" v-if="refreshing" />
      <ChevronDownIcon class="h-6 w-6" v-else />
    </div>

    <!-- Content -->
    <div :style="{ transform: `translateY(${pullDistance}px)` }">
      <slot />
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'

const emit = defineEmits(['refresh'])

const pulling = ref(false)
const pullDistance = ref(0)
const refreshing = ref(false)
let startY = 0

const handleTouchStart = (e) => {
  if (window.scrollY === 0) {
    startY = e.touches[0].clientY
  }
}

const handleTouchMove = (e) => {
  if (window.scrollY === 0) {
    const currentY = e.touches[0].clientY
    const distance = currentY - startY
    
    if (distance > 0 && distance < 150) {
      pulling.value = true
      pullDistance.value = distance * 0.5 // Effet élastique
      e.preventDefault()
    }
  }
}

const handleTouchEnd = async () => {
  if (pullDistance.value > 50) {
    refreshing.value = true
    await emit('refresh')
    refreshing.value = false
  }
  
  pulling.value = false
  pullDistance.value = 0
}
</script>
```

---

## 🎯 Checklist Adaptation Mobile

### Pour chaque page :

- [ ] **Layout** 
  - [ ] Padding responsive (px-4 sm:px-6 lg:px-8)
  - [ ] Espace bottom navigation (pb-20 lg:pb-8)
  - [ ] Max-width container adapté

- [ ] **Typography**
  - [ ] Tailles responsive (text-base sm:text-lg lg:text-xl)
  - [ ] Line-height adapté mobile

- [ ] **Touch Targets**
  - [ ] Tous boutons min 44x44px
  - [ ] Spacing entre éléments cliquables

- [ ] **Navigation**
  - [ ] Bottom nav mobile
  - [ ] Back button visible
  - [ ] Breadcrumbs cachés mobile

- [ ] **Formulaires**
  - [ ] Inputs full-width mobile
  - [ ] Labels au-dessus sur mobile
  - [ ] Keyboard-aware (viewport adjust)

- [ ] **Images**
  - [ ] Responsive (w-full, aspect-ratio)
  - [ ] Lazy loading
  - [ ] WebP format

- [ ] **Modals**
  - [ ] Fullscreen sur mobile
  - [ ] Swipe to dismiss

- [ ] **Listes**
  - [ ] Scroll vertical mobile
  - [ ] Pull to refresh
  - [ ] Infinite scroll

---

## 🧪 Tests Mobile

### Outils de test :

1. **Chrome DevTools**
```
F12 → Toggle device toolbar (Ctrl+Shift+M)
Tester sur : iPhone 12, Pixel 5, iPad
```

2. **Safari Responsive Design Mode**
```
Développer → Responsive Design Mode
```

3. **Real Device Testing**
- iPhone (iOS Safari)
- Android (Chrome)
- Tablet (iPad, Samsung Tab)

### Tests critiques :

- [ ] Touch targets < 44px ?
- [ ] Scroll horizontal involontaire ?
- [ ] Texte trop petit ?
- [ ] Images dépassent ?
- [ ] Formulaires keyboard-friendly ?
- [ ] Performance < 3s first paint ?

---

## 📈 Métriques Mobile

**Objectifs** :
- 🎯 Lighthouse Mobile Score > 85
- 🎯 First Contentful Paint < 2s
- 🎯 Time to Interactive < 3s
- 🎯 Cumulative Layout Shift < 0.1
- 🎯 Largest Contentful Paint < 2.5s

**Tester** :
```bash
npm run build
lighthouse http://localhost:7777 --view --preset=mobile
```

---

## 🎨 Prochaines Étapes

1. **Phase 1** : Adapter les 5 pages critiques
   - Topics/Index
   - Topics/Show
   - Vote/Show
   - Budget/Index
   - Profile/Edit

2. **Phase 2** : Composants UI mobiles
   - Bottom Navigation
   - Mobile Header
   - Bottom Sheet
   - Pull to Refresh

3. **Phase 3** : Tests & Optimisations
   - Real device testing
   - Performance profiling
   - Touch gestures

4. **Phase 4** : PWA Features
   - Service Worker
   - Offline mode
   - Install prompt

---

💙 CivicDash Mobile - Ready for 70% Mobile Traffic ! 📱🚀

