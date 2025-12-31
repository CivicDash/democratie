# 🎨 CivicDash - Guide de Charte Graphique

> Guide complet de l'identité visuelle de CivicDash pour assurer une cohérence sur toutes les interfaces.

---

## 📋 Table des matières

1. [Identité de marque](#-identité-de-marque)
2. [Palette de couleurs](#-palette-de-couleurs)
3. [Typographie](#-typographie)
4. [Composants UI](#-composants-ui)
5. [Icônes et Emojis](#-icônes-et-emojis)
6. [Gradients](#-gradients)
7. [Espacements](#-espacements)
8. [Modes clair/sombre](#-modes-clairsombre)
9. [Exemples de code](#-exemples-de-code)

---

## 🏛️ Identité de marque

### Nom
- **Principal** : CivicDash
- **Projet** : Objectif 2027
- **Association** : Civis-Consilium

### Logo
```
🗳️ CivicDash
```
- Emoji urne + texte en gradient
- Container : carré arrondi (`rounded-xl`) avec gradient indigo/violet

### Slogan
> *"Votre voix compte. Faites-la entendre."*

### Valeurs visuelles
- **Transparence** : Effets backdrop-blur, bordures légères
- **Accessibilité** : Contrastes élevés, textes lisibles
- **Modernité** : Gradients, animations subtiles
- **Confiance** : Palette bleue/indigo institutionnelle

---

## 🎨 Palette de couleurs

### Couleurs principales

| Nom | Light Mode | Dark Mode | Usage |
|-----|-----------|-----------|-------|
| **Primary** | `indigo-600` (#4F46E5) | `indigo-500` (#6366F1) | Boutons, liens, accents |
| **Secondary** | `purple-600` (#9333EA) | `purple-500` (#A855F7) | Gradients, éléments secondaires |
| **Background** | `white` / `slate-50` | `slate-950` / `gray-900` | Fonds de page |
| **Surface** | `white` | `gray-800` | Cards, modals |
| **Text** | `gray-900` | `white` / `gray-100` | Titres |
| **Text Muted** | `gray-600` | `gray-400` | Sous-titres, descriptions |

### Couleurs sémantiques

| Contexte | Couleur | Hex | Usage |
|----------|---------|-----|-------|
| 🟢 Succès | `emerald-500` | #10B981 | Votes positifs, validations |
| 🔴 Danger | `red-500` | #EF4444 | Votes négatifs, suppressions |
| 🟡 Warning | `amber-500` | #F59E0B | Alertes, mode démo |
| 🔵 Info | `sky-500` | #0EA5E9 | Informations, députés |
| 🟣 Neutre | `slate-500` | #64748B | Abstentions, inactifs |

### Couleurs thématiques (par section)

| Section | Couleur principale | Usage |
|---------|-------------------|-------|
| Parlement | `sky-500` to `blue-600` | Députés, Sénat |
| Législation | `emerald-500` to `teal-600` | Lois, amendements |
| Participation | `amber-500` to `orange-600` | Idées, votes citoyens |
| Statistiques | `violet-500` to `purple-600` | Graphiques, analyses |
| Recherche | `rose-500` to `pink-600` | Barre de recherche |
| Budget | `cyan-500` to `sky-600` | Budget participatif |

### Couleurs des groupes politiques

```css
/* Gauche → Droite */
.extreme-gauche { color: #DD0000; }  /* Rouge foncé */
.gauche         { color: #FF8080; }  /* Rose/rouge clair */
.centre         { color: #FF9900; }  /* Orange */
.droite         { color: #0066CC; }  /* Bleu */
.extreme-droite { color: #0D378A; }  /* Bleu marine */
```

---

## 🔤 Typographie

### Police principale
```css
font-family: 'Figtree', system-ui, -apple-system, sans-serif;
```

### Hiérarchie

| Élément | Tailwind Classes | Taille |
|---------|------------------|--------|
| H1 Hero | `text-4xl sm:text-5xl lg:text-7xl font-bold` | 36-72px |
| H1 Page | `text-3xl sm:text-4xl font-bold` | 30-36px |
| H2 Section | `text-2xl font-bold` | 24px |
| H3 Card | `text-xl font-semibold` | 20px |
| Body | `text-base` | 16px |
| Small | `text-sm` | 14px |
| Caption | `text-xs` | 12px |

### Line height
- Titres : `leading-tight` (1.25)
- Corps : `leading-relaxed` (1.625)

---

## 🧩 Composants UI

### Boutons

#### Primary Button
```html
<button class="px-6 py-3 bg-gradient-to-r from-indigo-500 to-purple-600 
               hover:from-indigo-600 hover:to-purple-700 
               text-white font-semibold rounded-xl 
               transition shadow-lg shadow-indigo-500/25">
    Action principale
</button>
```

#### Secondary Button
```html
<button class="px-6 py-3 bg-white/10 hover:bg-white/20 
               text-white font-medium rounded-xl 
               transition border border-white/20">
    Action secondaire
</button>
```

#### Ghost Button
```html
<button class="px-4 py-2 text-slate-400 hover:text-white 
               transition">
    Lien/Action tertiaire
</button>
```

### Cards

#### Card Standard
```html
<div class="bg-white dark:bg-gray-800 rounded-xl 
            border border-gray-200 dark:border-gray-700 
            shadow-sm hover:shadow-md transition-shadow p-6">
    <!-- Contenu -->
</div>
```

#### Card Glass (Mode sombre)
```html
<div class="bg-white/5 backdrop-blur-sm rounded-2xl 
            border border-white/10 hover:border-white/20 
            transition-all duration-300 p-8">
    <!-- Contenu -->
</div>
```

### Badges

```html
<!-- Success -->
<span class="px-3 py-1 bg-emerald-100 text-emerald-800 
             dark:bg-emerald-900/50 dark:text-emerald-300 
             text-sm font-medium rounded-full">
    ✓ Adopté
</span>

<!-- Warning -->
<span class="px-3 py-1 bg-amber-100 text-amber-800 
             dark:bg-amber-900/50 dark:text-amber-300 
             text-sm font-medium rounded-full">
    ⚠️ En cours
</span>

<!-- Info -->
<span class="px-3 py-1 bg-blue-100 text-blue-800 
             dark:bg-blue-900/50 dark:text-blue-300 
             text-sm font-medium rounded-full">
    ℹ️ Information
</span>
```

### Inputs

```html
<input class="w-full px-4 py-3 rounded-lg 
              bg-white dark:bg-gray-800 
              border border-gray-300 dark:border-gray-600 
              text-gray-900 dark:text-white 
              placeholder-gray-400 
              focus:outline-none focus:ring-2 focus:ring-indigo-500 
              focus:border-transparent transition" />
```

---

## 🎯 Icônes et Emojis

### Emojis par section

| Section | Emoji | Usage |
|---------|-------|-------|
| Accueil | 🏠 | Navigation |
| Parlement | 🏛️ | Hub législatif |
| Députés | 👥 | Liste députés |
| Sénateurs | 🎖️ | Liste sénateurs |
| Lois | ⚖️ | Projets de loi |
| Scrutins | 🗳️ | Votes |
| Idées | 💡 | Propositions citoyennes |
| Budget | 💰 | Budget participatif |
| Recherche | 🔍 | Barre de recherche |
| Profil | 👤 | Compte utilisateur |
| Stats | 📊 | Statistiques |
| Calendrier | 📅 | Agenda parlementaire |

### Votes
- ✅ Pour
- ❌ Contre
- ⚪ Abstention
- ❓ Non votant

---

## 🌈 Gradients

### Gradient Principal (Brand)
```css
background: linear-gradient(to right, #6366F1, #A855F7);
/* Tailwind: from-indigo-500 to-purple-600 */
```

### Gradient Hero Text
```css
background: linear-gradient(to right, #818CF8, #C084FC, #F472B6);
background-clip: text;
/* Tailwind: from-indigo-400 via-purple-400 to-pink-400 */
```

### Gradient Dark Background
```css
background: linear-gradient(to bottom right, #0F172A, #1E293B, #312E81);
/* Tailwind: from-slate-900 via-slate-800 to-indigo-900 */
```

### Gradients thématiques
```css
/* Parlement */
.gradient-parlement { @apply from-sky-500 to-blue-600; }

/* Législation */
.gradient-legislation { @apply from-emerald-500 to-teal-600; }

/* Participation */
.gradient-participation { @apply from-amber-500 to-orange-600; }

/* Stats */
.gradient-stats { @apply from-violet-500 to-purple-600; }
```

---

## 📏 Espacements

### Grid System
- Container max-width : `max-w-7xl` (1280px)
- Padding horizontal : `px-4 sm:px-6 lg:px-8`
- Gap grilles : `gap-4` à `gap-8`

### Sections
- Padding vertical : `py-12` à `py-20` (48-80px)
- Marge entre sections : `mt-12` à `mt-20`

### Composants
- Padding cards : `p-4` à `p-8`
- Border radius : `rounded-lg` (8px) à `rounded-2xl` (16px)

---

## 🌓 Modes clair/sombre

### Convention de nommage
```html
<!-- Pattern standard -->
<div class="bg-white dark:bg-gray-800 
            text-gray-900 dark:text-white 
            border-gray-200 dark:border-gray-700">
```

### Correspondances

| Light | Dark |
|-------|------|
| `white` | `gray-800` / `gray-900` |
| `gray-50` | `gray-900` |
| `gray-100` | `gray-800` |
| `gray-200` | `gray-700` |
| `gray-600` | `gray-400` |
| `gray-900` | `white` / `gray-100` |
| `indigo-600` | `indigo-500` |
| `blue-600` | `blue-400` |

---

## 💻 Exemples de code

### Hero Section
```vue
<section class="relative py-20 lg:py-32 overflow-hidden 
                bg-gradient-to-br from-slate-900 via-slate-800 to-indigo-900">
    <!-- Background effects -->
    <div class="absolute inset-0">
        <div class="absolute top-0 left-1/4 w-96 h-96 
                    bg-indigo-600/20 rounded-full blur-3xl"></div>
        <div class="absolute bottom-0 right-1/4 w-96 h-96 
                    bg-purple-600/20 rounded-full blur-3xl"></div>
    </div>
    
    <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <h1 class="text-4xl sm:text-5xl lg:text-7xl font-bold mb-6">
            <span class="text-white">Titre principal</span><br>
            <span class="bg-gradient-to-r from-indigo-400 via-purple-400 to-pink-400 
                         bg-clip-text text-transparent">
                Titre gradient
            </span>
        </h1>
    </div>
</section>
```

### Feature Card
```vue
<div class="group relative bg-white/5 backdrop-blur-sm rounded-2xl p-8 
            border border-white/10 hover:border-white/20 
            transition-all duration-300 hover:-translate-y-1">
    <div class="w-14 h-14 rounded-xl bg-gradient-to-br from-indigo-500 to-purple-600 
                flex items-center justify-center text-2xl mb-6 shadow-lg">
        🏛️
    </div>
    <h3 class="text-xl font-bold text-white mb-3">Titre</h3>
    <p class="text-slate-400">Description...</p>
</div>
```

### Navigation Bar
```vue
<nav class="fixed top-0 left-0 right-0 z-50 
            bg-slate-950/80 backdrop-blur-lg 
            border-b border-white/10">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between items-center h-16">
            <!-- Logo -->
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl 
                            bg-gradient-to-br from-indigo-500 to-purple-600 
                            flex items-center justify-center text-xl font-bold">
                    🗳️
                </div>
                <span class="text-xl font-bold 
                             bg-gradient-to-r from-white to-slate-400 
                             bg-clip-text text-transparent">
                    CivicDash
                </span>
            </div>
            <!-- ... -->
        </div>
    </div>
</nav>
```

---

## 📱 Responsive Design

### Breakpoints (Tailwind par défaut)
| Prefix | Min-width | Usage |
|--------|-----------|-------|
| `sm:` | 640px | Tablettes portrait |
| `md:` | 768px | Tablettes paysage |
| `lg:` | 1024px | Desktop |
| `xl:` | 1280px | Large desktop |
| `2xl:` | 1536px | Très grand écran |

### Mobile First
Toujours partir du mobile et ajouter les variations :
```html
<!-- Mobile: 1 col → Tablet: 2 cols → Desktop: 3 cols -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
```

---

## ✅ Checklist Design

- [ ] Utiliser les couleurs de la palette
- [ ] Respecter la hiérarchie typographique
- [ ] Appliquer les gradients brand sur les CTAs principaux
- [ ] Ajouter des effets hover subtils
- [ ] Supporter le mode sombre
- [ ] Tester sur mobile, tablette, desktop
- [ ] Utiliser les bons emojis par section
- [ ] Maintenir les espacements cohérents

---

*Dernière mise à jour : Décembre 2025*
*Version : 1.0.0*
