# UX/UI Audit Report – Gîte de Bellevaux (gitebellevaux.fr)

**Date:** February 23, 2025  
**Scope:** Homepage, Le Gîte, Notre Histoire, Galerie, Disponibilités/Réservation  
**Method:** Content analysis, structure review, navigation flow (screenshots unavailable – browser tools not accessible in session)

---

## Executive Summary

The site presents a vacation rental chalet in Bellevaux (Haute-Savoie). The content structure is clear and the information hierarchy is logical. Several UX/UI concerns emerge from the content and structure that would benefit from visual verification and targeted improvements.

---

## 1. Homepage (/)

### Content Structure
- **Hero:** "Gîte de Bellevaux" – tagline about a chalet in the mountains
- **CTAs:** "Réserver" → /availability, "Découvrir le gîte" → /le-gite
- **Value props:** Confort, Nature, Convivialité (3 short blocks)
- **Story teaser:** "Plus qu'un chalet, une histoire" with link to /notre-histoire

### UX/UI Observations

| Aspect | Assessment | Notes |
|--------|------------|-------|
| **Visual design** | Unknown without screenshots | Need to verify hero imagery, typography, and overall polish |
| **Layout** | Likely adequate | 3-column value props suggest a simple grid |
| **Typography** | Unknown | Font choices and hierarchy need visual check |
| **Color scheme** | Unknown | Mountain/chalet theme suggests warm, natural palette |
| **Images** | Critical | Hero and section images are central to conversion |
| **Mobile** | Unknown | Responsiveness must be tested on small viewports |
| **Broken elements** | None detected | Links resolve correctly |

### Potential Issues
- **Above the fold:** Ensure hero + primary CTAs are visible without scroll
- **Value props:** Risk of weak visual differentiation if icons/imagery are missing
- **CTA hierarchy:** "Réserver" should be visually dominant over "Découvrir le gîte"

---

## 2. Le Gîte (/le-gite)

### Content Structure
- **Headline:** "Prêt pour un séjour inoubliable"
- **Intro:** Chalet description
- **CTAs:** "Voir le calendrier" → /availability, "Galerie" → /galerie
- **Amenities:** Espace Gourmet, Salle de bain, Salon au coin du feu, Chambres (7 rooms, 15 people)
- **Room list:** Chambres 1–7 with bed sizes
- **Kitchen:** Appliances listed
- **Bathrooms:** 3 WC, 1 douche, 1 baignoire, 2 salles de bains
- **Stats:** 140 m² chalet, 1300 m² terrain, 15 personnes

### UX/UI Observations

| Aspect | Assessment | Notes |
|--------|------------|-------|
| **Information density** | High | Many details; needs clear hierarchy and grouping |
| **Room layout** | Awkward | Chambres listed 7→1 (reverse order); consider 1→7 or visual plan |
| **Stats block** | Good | 140 m² / 1300 m² / 15 personnes – clear and scannable |
| **Lists** | Risk of clutter | Kitchen and bathroom lists may feel dense without icons/spacing |

### Potential Issues
- **Room numbering:** 7→1 order may confuse; add a floor plan or visual map
- **Spacing:** Dense lists need adequate line height and grouping
- **Imagery:** Each amenity (kitchen, salon, chambres) should have supporting photos
- **Mobile:** Long lists may require collapsible sections or tabs on small screens

---

## 3. Notre Histoire (/notre-histoire)

### Content Structure
- **Headline:** "Notre histoire"
- **Subtitle:** From 1978 to today – family story
- **Timeline:**
  - 1978: La vision – Les débuts aux Curtillets
  - 1983: La construction – Un chantier familial
  - 2003·2012: Souvenirs – Moments de recueillement
  - 2020→2023: La rénovation – Confort et transmission
  - Aujourd'hui: La location – Partager la maison

### UX/UI Observations

| Aspect | Assessment | Notes |
|--------|------------|-------|
| **Storytelling** | Strong | Clear timeline with emotional arc |
| **Structure** | Good | Chronological order with dates and titles |
| **Length** | Moderate | Several paragraphs; needs good typography and spacing |
| **Emotional tone** | Appropriate | Family, tradition, renovation, sharing |

### Potential Issues
- **Timeline visualization:** Text-only timeline may feel flat; consider vertical/horizontal timeline with icons or images
- **Line breaks:** Long paragraphs need comfortable line length (45–75 chars)
- **Spacing:** Adequate margin between timeline sections
- **Mobile:** Timeline may need horizontal scroll or stacked layout on small screens
- **Images:** Period photos (1978, 1983, renovation) would strengthen the story

---

## 4. Galerie (/galerie)

### Content Structure (from fetch)
- **Title:** "Galerie"
- **Content:** Very minimal in fetched output – likely image-heavy

### UX/UI Observations

| Aspect | Assessment | Notes |
|--------|------------|-------|
| **Content** | Sparse in text | Page likely driven by images/slideshow |
| **Slideshow** | Not verifiable | User requested interaction; behavior unknown |
| **Images** | Critical | Quality, loading, aspect ratio need review |

### Potential Issues (to verify visually)
- **Slideshow controls:** Clear prev/next, dots, keyboard support
- **Loading:** Lazy loading, placeholders, smooth transitions
- **Image quality:** Resolution, compression, consistent aspect ratios
- **Mobile:** Touch swipe, thumbnails, full-screen behavior
- **Accessibility:** Alt text, focus management, reduced motion
- **Performance:** Large images may slow load; check optimization
- **Empty state:** If no images load, ensure fallback content

---

## 5. Disponibilités / Réservation (/availability)

### Content Structure
- **Title:** "Disponibilités - Gîte de Bellevaux"
- **Capacity:** 15 personnes (adultes et enfants)
- **Rule:** Réservation au moins 30 jours à l'avance
- **Form fields:** Arrivée, Départ, Adultes, Enfants
- **Actions:** "Simuler", "Créer un compte", "Se connecter"

### UX/UI Observations

| Aspect | Assessment | Notes |
|--------|------------|-------|
| **Form layout** | Unknown | Date pickers, dropdowns need clear layout |
| **CTA clarity** | Concern | "Simuler" vs "Réserver" – booking intent may be unclear |
| **Auth links** | Present | Register/Login – placement and prominence matter |
| **Calendar** | Likely FullCalendar | Per existing plan; mobile height and view need verification |

### Potential Issues
- **"Simuler" vs "Réserver":** "Simuler" suggests simulation only; primary action for booking should be obvious
- **Date picker UX:** Native `<input type="date">` varies by browser; consider custom picker
- **Calendar integration:** If calendar and form are separate, ensure clear connection
- **30-day rule:** Should be visible and understandable (e.g. tooltip or info icon)
- **Mobile:** Form fields and calendar must work well on small screens
- **Guest vs logged-in:** Flow for guests vs registered users should be clear

---

## Cross-Page Observations

### Navigation & IA
- **Routes:** /, /le-gite, /notre-histoire, /galerie, /availability – logical and consistent
- **Internal links:** Homepage links to main sections; Le Gîte links to availability and galerie
- **Missing:** No explicit "Contact" or "Tarifs" in fetched content – may exist in header/footer

### Consistency
- **Tone:** Warm, family-oriented, mountain/chalet
- **Language:** French throughout
- **CTAs:** "Réserver" and "Découvrir" appear repeatedly – good for conversion

### Technical
- **Availability page:** Previously reported timeout on fetch – possible performance issue
- **Galerie:** Fetched content very light – may rely heavily on JS/images

---

## Recommendations Summary

### High Priority
1. **Galerie:** Audit slideshow behavior, image quality, loading, and mobile experience
2. **Availability:** Clarify "Simuler" vs booking flow; improve date selection UX
3. **Le Gîte:** Reorder room list (1→7), add imagery per section, improve list spacing

### Medium Priority
4. **Notre Histoire:** Add timeline visualization and period images
5. **Homepage:** Verify hero imagery, CTA hierarchy, and mobile layout
6. **Mobile:** Test all pages at 375px and 768px widths

### Lower Priority
7. **Contact/Tarifs:** Ensure visible if they exist
8. **Accessibility:** Alt text, focus states, keyboard navigation
9. **Performance:** Image optimization, lazy loading, caching

---

## Screenshot Limitation

Screenshots could not be captured in this session because the browser MCP tools were not available. To complete a visual audit:

1. **Manual capture:** Use browser dev tools (e.g. responsive mode) and screenshot each page
2. **Automation:** Run a Playwright/Puppeteer script to capture full-page screenshots
3. **Lighthouse:** Run Lighthouse audits for performance, accessibility, and best practices
4. **Real devices:** Test on actual phones and tablets

---

## Appendix: Page URLs Verified

| Page | URL | Status |
|------|-----|--------|
| Homepage | https://www.gitebellevaux.fr/ | ✓ Fetched |
| Le Gîte | https://www.gitebellevaux.fr/le-gite | ✓ Fetched |
| Notre Histoire | https://www.gitebellevaux.fr/notre-histoire | ✓ Fetched |
| Galerie | https://www.gitebellevaux.fr/galerie | ✓ Fetched (minimal content) |
| Disponibilités | https://www.gitebellevaux.fr/availability | ✓ Fetched |
