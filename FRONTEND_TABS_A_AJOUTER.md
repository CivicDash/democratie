# 📊 Frontend - Onglets à ajouter

## Navigation (après "🗺️ Régions")

```vue
<!-- ✨ Qualité de vie -->
<button
    @click="activeTab = 'quality'"
    :class="[
        activeTab === 'quality'
            ? 'border-indigo-500 text-indigo-600 dark:text-indigo-400'
            : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300',
        'whitespace-nowrap border-b-2 py-4 px-1 text-sm font-medium'
    ]"
>
    ✨ Qualité de vie
</button>

<!-- 📚 Éducation -->
<button
    @click="activeTab = 'education'"
    :class="[
        activeTab === 'education'
            ? 'border-indigo-500 text-indigo-600 dark:text-indigo-400'
            : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300',
        'whitespace-nowrap border-b-2 py-4 px-1 text-sm font-medium'
    ]"
>
    📚 Éducation
</button>

<!-- 🏥 Santé -->
<button
    @click="activeTab = 'health'"
    :class="[
        activeTab === 'health'
            ? 'border-indigo-500 text-indigo-600 dark:text-indigo-400'
            : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300',
        'whitespace-nowrap border-b-2 py-4 px-1 text-sm font-medium'
    ]"
>
    🏥 Santé
</button>

<!-- 🏠 Logement -->
<button
    @click="activeTab = 'housing'"
    :class="[
        activeTab === 'housing'
            ? 'border-indigo-500 text-indigo-600 dark:text-indigo-400'
            : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300',
        'whitespace-nowrap border-b-2 py-4 px-1 text-sm font-medium'
    ]"
>
    🏠 Logement
</button>

<!-- 🌱 Environnement -->
<button
    @click="activeTab = 'environment'"
    :class="[
        activeTab === 'environment'
            ? 'border-indigo-500 text-indigo-600 dark:text-indigo-400'
            : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300',
        'whitespace-nowrap border-b-2 py-4 px-1 text-sm font-medium'
    ]"
>
    🌱 Environnement
</button>

<!-- 🔒 Sécurité -->
<button
    @click="activeTab = 'security'"
    :class="[
        activeTab === 'security'
            ? 'border-indigo-500 text-indigo-600 dark:text-indigo-400'
            : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300',
        'whitespace-nowrap border-b-2 py-4 px-1 text-sm font-medium'
    ]"
>
    🔒 Sécurité
</button>

<!-- 💼 Emploi -->
<button
    @click="activeTab = 'employment'"
    :class="[
        activeTab === 'employment'
            ? 'border-indigo-500 text-indigo-600 dark:text-indigo-400'
            : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300',
        'whitespace-nowrap border-b-2 py-4 px-1 text-sm font-medium'
    ]"
>
    💼 Emploi
</button>
```

## Note

Le fichier `Index.vue` est déjà très long (900+ lignes). 

**Recommandation** : Créer des composants séparés pour chaque onglet :
- `QualityOfLifeTab.vue`
- `EducationTab.vue`
- `HealthTab.vue`
- `HousingTab.vue`
- `EnvironmentTab.vue`
- `SecurityTab.vue`
- `EmploymentTab.vue`

Cela rendra le code plus maintenable et plus rapide à charger.

Veux-tu que je :
1. **Ajoute tout dans Index.vue** (fichier très long mais tout au même endroit)
2. **Crée des composants séparés** (meilleure pratique, code plus propre)

Je recommande l'option 2 ! 🎯

