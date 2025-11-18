#!/bin/bash

##############################################################################
# 🔍 Script d'analyse COMPLÈTE des données JSON de l'Assemblée Nationale
##############################################################################
# 
# Ce script analyse en profondeur tous les types de données et leurs relations
# pour construire un modèle de données optimal.
#
# Usage:
#   bash scripts/analyse_complete_donnees_an.sh
#
##############################################################################

set -e

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(dirname "$SCRIPT_DIR")"
DATA_DIR="$PROJECT_ROOT/public/data"

echo "═══════════════════════════════════════════════════════════════"
echo "🔍  ANALYSE COMPLÈTE DES DONNÉES JSON DE L'ASSEMBLÉE NATIONALE"
echo "═══════════════════════════════════════════════════════════════"
echo ""

# Fonction pour compter récursivement les fichiers JSON
count_json_files() {
    local dir=$1
    find "$dir" -name "*.json" -type f 2>/dev/null | wc -l
}

# 1. INVENTAIRE COMPLET
echo "📊  INVENTAIRE COMPLET DES FICHIERS"
echo "───────────────────────────────────────────────────────────────"
echo ""

TOTAL_ACTEURS=$(count_json_files "$DATA_DIR/acteur")
TOTAL_MANDATS=$(count_json_files "$DATA_DIR/mandat")
TOTAL_ORGANES=$(count_json_files "$DATA_DIR/organe")
TOTAL_SCRUTINS=$(count_json_files "$DATA_DIR/scrutins")
TOTAL_REUNIONS=$(count_json_files "$DATA_DIR/reunion")
TOTAL_DEPORTS=$(count_json_files "$DATA_DIR/deport")
TOTAL_AMENDEMENTS=$(count_json_files "$DATA_DIR/amendements")
TOTAL_PAYS=$(count_json_files "$DATA_DIR/pays")

printf "  📂 %-20s : %8d fichiers JSON\n" "acteur/" "$TOTAL_ACTEURS"
printf "  📂 %-20s : %8d fichiers JSON\n" "mandat/" "$TOTAL_MANDATS"
printf "  📂 %-20s : %8d fichiers JSON\n" "organe/" "$TOTAL_ORGANES"
printf "  📂 %-20s : %8d fichiers JSON\n" "scrutins/" "$TOTAL_SCRUTINS"
printf "  📂 %-20s : %8d fichiers JSON\n" "reunion/" "$TOTAL_REUNIONS"
printf "  📂 %-20s : %8d fichiers JSON\n" "deport/" "$TOTAL_DEPORTS"
printf "  📂 %-20s : %8d fichiers JSON (🔥 ÉNORME !)\n" "amendements/" "$TOTAL_AMENDEMENTS"
printf "  📂 %-20s : %8d fichiers JSON\n" "pays/" "$TOTAL_PAYS"
echo ""

TOTAL_FILES=$((TOTAL_ACTEURS + TOTAL_MANDATS + TOTAL_ORGANES + TOTAL_SCRUTINS + TOTAL_REUNIONS + TOTAL_DEPORTS + TOTAL_AMENDEMENTS + TOTAL_PAYS))
TOTAL_SIZE=$(du -sh "$DATA_DIR" | cut -f1)

echo "  🎯 TOTAL : $TOTAL_FILES fichiers JSON ($TOTAL_SIZE)"
echo ""

# 2. ANALYSE DES RELATIONS
echo "═══════════════════════════════════════════════════════════════"
echo "🔗  ANALYSE DES RELATIONS ENTRE ENTITÉS"
echo "═══════════════════════════════════════════════════════════════"
echo ""

echo "📋  MODÈLE DE DONNÉES IDENTIFIÉ :"
echo ""
echo "  1️⃣  ACTEUR (603 fichiers)"
echo "      ├─ uid: PAxxxx"
echo "      ├─ Données: nom, prénom, naissance, profession, adresses"
echo "      └─ Relations: → MANDAT (via acteurRef)"
echo ""
echo "  2️⃣  MANDAT (13 184 fichiers)"
echo "      ├─ uid: PMxxxxxx"
echo "      ├─ acteurRef → ACTEUR"
echo "      ├─ organeRef → ORGANE"
echo "      ├─ legislature: 9, 10, 11... 17"
echo "      ├─ typeOrgane: ASSEMBLEE, COMPER, GP, DELEG, etc."
echo "      └─ Dates: dateDebut, dateFin"
echo ""
echo "  3️⃣  ORGANE (8 957 fichiers)"
echo "      ├─ uid: POxxxxxx"
echo "      ├─ codeType: GP (groupe), COMPER (commission), DELEG (délégation), etc."
echo "      ├─ libelle: nom complet"
echo "      └─ Relations: ← MANDAT (via organeRef)"
echo ""
echo "  4️⃣  SCRUTIN (3 876 fichiers)"
echo "      ├─ uid: VTANRxLxxVxxxx"
echo "      ├─ legislature, dateScrutin, typeVote"
echo "      ├─ titre: description du vote"
echo "      ├─ syntheseVote: pour/contre/abstentions"
echo "      └─ ventilationVotes: votes NOMINATIFS par acteur ⭐"
echo "         ├─ acteurRef → ACTEUR"
echo "         ├─ mandatRef → MANDAT"
echo "         ├─ groupeRef → ORGANE (groupe politique)"
echo "         └─ position: pour/contre/abstention/non_votant"
echo ""
echo "  5️⃣  AMENDEMENT (68 539 fichiers ! 🔥)"
echo "      ├─ uid: AMANRxLxxPOxxxxxBxxxxPxDxNxxxxxx"
echo "      ├─ Structure hiérarchique: DLR > PION/PRJL > AMAN"
echo "      │  ├─ DLR = Dossier législatif (ex: DLR5L17N51035)"
echo "      │  ├─ PION/PRJL = Texte (proposition/projet de loi)"
echo "      │  └─ AMAN = Amendement individuel"
echo "      ├─ texteLegislatifRef: lien vers le texte"
echo "      ├─ examenRef: examen en commission ou hémicycle"
echo "      ├─ signataires:"
echo "      │  ├─ auteur.acteurRef → ACTEUR"
echo "      │  ├─ auteur.groupePolitiqueRef → ORGANE"
echo "      │  └─ cosignataires.acteurRef[] → ACTEUR[]"
echo "      ├─ pointeurFragmentTexte: article visé"
echo "      ├─ corps: dispositif, exposé"
echo "      └─ cycleDeVie:"
echo "         ├─ dateDepot, datePublication"
echo "         ├─ etat: Adopté/Rejeté/IRR45/etc."
echo "         └─ sort: résultat final"
echo ""
echo "  6️⃣  RÉUNION (4 601 fichiers)"
echo "      ├─ uid: RUANRxLxxSxxxxxIDSxxxxx"
echo "      ├─ organeRef → ORGANE"
echo "      ├─ dateReunion"
echo "      └─ Relations: présences, interventions (à explorer)"
echo ""
echo "  7️⃣  DÉPORT (37 fichiers)"
echo "      ├─ uid: DPTRxLxxPAxxxxxxDxxxx"
echo "      ├─ acteurRef → ACTEUR"
echo "      └─ Raison: conflit d'intérêt, absence justifiée"
echo ""

# 3. ANALYSE PAR LÉGISLATURE
echo "═══════════════════════════════════════════════════════════════"
echo "📈  RÉPARTITION PAR LÉGISLATURE"
echo "═══════════════════════════════════════════════════════════════"
echo ""

if command -v jq &> /dev/null; then
    echo "🗳️  SCRUTINS par législature :"
    echo ""
    find "$DATA_DIR/scrutins" -name "*.json" -exec jq -r '.scrutin.legislature' {} \; 2>/dev/null | sort | uniq -c | sort -rn | while read count leg; do
        printf "     Législature %2s : %4d scrutins\n" "$leg" "$count"
    done
    echo ""
    
    echo "📋  MANDATS par législature :"
    echo ""
    find "$DATA_DIR/mandat" -name "*.json" -exec jq -r '.mandat.legislature // "N/A"' {} \; 2>/dev/null | grep -v "N/A" | sort | uniq -c | sort -rn | head -10 | while read count leg; do
        printf "     Législature %2s : %5d mandats\n" "$leg" "$count"
    done
    echo ""
    
    echo "📝  AMENDEMENTS par législature :"
    echo ""
    find "$DATA_DIR/amendements" -name "*.json" -type f -exec jq -r '.amendement.legislature' {} \; 2>/dev/null | sort | uniq -c | sort -rn | while read count leg; do
        printf "     Législature %2s : %5d amendements\n" "$leg" "$count"
    done
    echo ""
fi

# 4. ANALYSE DES ÉTATS D'AMENDEMENTS
echo "═══════════════════════════════════════════════════════════════"
echo "📊  ÉTATS DES AMENDEMENTS"
echo "═══════════════════════════════════════════════════════════════"
echo ""

if command -v jq &> /dev/null; then
    echo "Échantillon de 1000 amendements aléatoires :"
    echo ""
    find "$DATA_DIR/amendements" -name "*.json" -type f | shuf | head -1000 | xargs jq -r '.amendement.cycleDeVie.etatDesTraitements.etat.code' 2>/dev/null | sort | uniq -c | sort -rn | while read count code; do
        case $code in
            "ADO") desc="Adopté" ;;
            "REJ") desc="Rejeté" ;;
            "IRR45") desc="Irrecevable (entonnoir)" ;;
            "IRR40") desc="Irrecevable (article 40)" ;;
            "RET") desc="Retiré" ;;
            "TOM") desc="Tombé" ;;
            "DEV") desc="Devenu sans objet" ;;
            "NDE") desc="Non défendu" ;;
            *) desc="$code" ;;
        esac
        printf "     %-6s (%-30s) : %4d\n" "$code" "$desc" "$count"
    done
    echo ""
fi

# 5. STRUCTURE HIÉRARCHIQUE DES AMENDEMENTS
echo "═══════════════════════════════════════════════════════════════"
echo "🗂️  STRUCTURE HIÉRARCHIQUE DES AMENDEMENTS"
echo "═══════════════════════════════════════════════════════════════"
echo ""

echo "Exemple de hiérarchie :"
echo ""
echo "  📁 DLR5L17N51035/ (Dossier Législatif)"
echo "     └─ 📁 PIONANR5L17B0689/ (Proposition de loi)"
echo "        ├─ AMANR5L17PO838901B0689P0D1N000001.json"
echo "        ├─ AMANR5L17PO838901B0689P0D1N000002.json"
echo "        ├─ AMANR5L17PO838901B0689P0D1N000003.json"
echo "        └─ ..."
echo ""

DOSSIER_EXAMPLE=$(find "$DATA_DIR/amendements" -mindepth 1 -maxdepth 1 -type d | head -1)
if [ -d "$DOSSIER_EXAMPLE" ]; then
    DOSSIER_NAME=$(basename "$DOSSIER_EXAMPLE")
    NB_TEXTES=$(find "$DOSSIER_EXAMPLE" -mindepth 1 -maxdepth 1 -type d | wc -l)
    TEXTE_EXAMPLE=$(find "$DOSSIER_EXAMPLE" -mindepth 1 -maxdepth 1 -type d | head -1)
    TEXTE_NAME=$(basename "$TEXTE_EXAMPLE")
    NB_AMENDEMENTS=$(find "$TEXTE_EXAMPLE" -name "*.json" -type f | wc -l)
    
    echo "Exemple réel :"
    echo ""
    echo "  📁 $DOSSIER_NAME/ ($NB_TEXTES texte(s))"
    echo "     └─ 📁 $TEXTE_NAME/ ($NB_AMENDEMENTS amendement(s))"
    echo ""
fi

# 6. TYPES D'ORGANES
echo "═══════════════════════════════════════════════════════════════"
echo "🏛️  TYPES D'ORGANES PARLEMENTAIRES"
echo "═══════════════════════════════════════════════════════════════"
echo ""

if command -v jq &> /dev/null; then
    find "$DATA_DIR/organe" -name "*.json" -exec jq -r '.organe.codeType // "N/A"' {} \; 2>/dev/null | sort | uniq -c | sort -rn | while read count type; do
        case $type in
            "ASSEMBLEE") desc="Assemblée Nationale" ;;
            "GP") desc="Groupe Parlementaire" ;;
            "COMPER") desc="Commission Permanente" ;;
            "DELEG") desc="Délégation" ;;
            "ORGEXTPARL") desc="Organe Extra-Parlementaire" ;;
            "PARPOL") desc="Parti Politique" ;;
            "CNPS") desc="Conseil National du Parti Socialiste" ;;
            "ASSEMBLEE") desc="Assemblée" ;;
            "COMNL") desc="Commission Spéciale" ;;
            "GE") desc="Groupe d'Études" ;;
            "GA") desc="Groupe d'Amitié" ;;
            *) desc="$type" ;;
        esac
        printf "     %-15s (%-40s) : %4d\n" "$type" "$desc" "$count"
    done
    echo ""
fi

# 7. FOCUS LÉGISLATURE 17
echo "═══════════════════════════════════════════════════════════════"
echo "🎯  FOCUS SUR LA LÉGISLATURE 17 (2024-2029)"
echo "═══════════════════════════════════════════════════════════════"
echo ""

if command -v jq &> /dev/null; then
    LEG17_SCRUTINS=$(find "$DATA_DIR/scrutins" -name "*.json" -exec jq -r 'select(.scrutin.legislature == "17") | .scrutin.uid' {} \; 2>/dev/null | wc -l)
    LEG17_MANDATS=$(find "$DATA_DIR/mandat" -name "*.json" -exec jq -r 'select(.mandat.legislature == "17") | .mandat.uid' {} \; 2>/dev/null | wc -l)
    LEG17_AMENDEMENTS=$(find "$DATA_DIR/amendements" -name "*.json" -type f -exec jq -r 'select(.amendement.legislature == "17") | .amendement.uid' {} \; 2>/dev/null | wc -l)
    
    printf "  🗳️  Scrutins         : %5d\n" "$LEG17_SCRUTINS"
    printf "  📋  Mandats          : %5d\n" "$LEG17_MANDATS"
    printf "  📝  Amendements      : %5d\n" "$LEG17_AMENDEMENTS"
    echo ""
fi

# 8. RELATIONS CLÉS
echo "═══════════════════════════════════════════════════════════════"
echo "🔗  SCHÉMA DES RELATIONS PRINCIPALES"
echo "═══════════════════════════════════════════════════════════════"
echo ""

cat << 'EOF'
  ┌─────────────┐
  │   ACTEUR    │ (PA)
  │  (Député)   │
  └──────┬──────┘
         │
         │ acteurRef
         │
         ├────────────────────────────────────┐
         │                                    │
         ▼                                    ▼
  ┌─────────────┐                    ┌─────────────┐
  │   MANDAT    │ (PM)               │ AMENDEMENT  │ (AMAN)
  │             │                    │             │
  │ • Député    │                    │ • auteur    │
  │ • Commission│                    │ • cosig[]   │
  │ • Groupe    │                    └──────┬──────┘
  └──────┬──────┘                           │
         │                                   │ texteLegislatifRef
         │ organeRef                        │
         │                                   ▼
         ▼                            ┌─────────────┐
  ┌─────────────┐                    │  DOSSIER    │ (DLR)
  │   ORGANE    │ (PO)               │ LÉGISLATIF  │
  │             │                    └─────────────┘
  │ • Groupe GP │
  │ • Comm COMP │
  │ • Délég DEL │
  └──────┬──────┘
         │
         │ organeRef
         │
         ▼
  ┌─────────────┐
  │   SCRUTIN   │ (VTAN)
  │             │
  │ ventilationVotes:
  │  • acteurRef → ACTEUR
  │  • mandatRef → MANDAT
  │  • groupeRef → ORGANE
  │  • position: pour/contre/abs
  └─────────────┘

EOF

echo ""

# 9. RECOMMANDATIONS
echo "═══════════════════════════════════════════════════════════════"
echo "💡  RECOMMANDATIONS POUR LA MODÉLISATION BDD"
echo "═══════════════════════════════════════════════════════════════"
echo ""

cat << 'EOF'
📋  TABLES PRINCIPALES À CRÉER :

1️⃣  acteurs_an
    - uid (PK)
    - civilite, prenom, nom, trigramme
    - date_naissance, lieu_naissance
    - profession, categorie_socio_pro
    - url_hatvp
    - adresses (JSON)

2️⃣  mandats_an
    - uid (PK)
    - acteur_ref (FK → acteurs_an)
    - organe_ref (FK → organes_an)
    - legislature
    - type_organe (ASSEMBLEE, COMPER, GP, DELEG)
    - qualite (Député, Président, Membre)
    - date_debut, date_fin

3️⃣  organes_an
    - uid (PK)
    - code_type (GP, COMPER, DELEG, etc.)
    - libelle, libelle_abrege
    - legislature
    - date_debut, date_fin

4️⃣  scrutins_an
    - uid (PK)
    - numero, legislature
    - date_scrutin
    - type_vote, resultat
    - titre (TEXT)
    - nombre_votants, pour, contre, abstentions

5️⃣  votes_individuels_an
    - id (PK auto)
    - scrutin_ref (FK → scrutins_an)
    - acteur_ref (FK → acteurs_an)
    - mandat_ref (FK → mandats_an)
    - groupe_ref (FK → organes_an)
    - position (pour/contre/abstention/non_votant)
    - position_groupe
    - par_delegation (BOOLEAN)

6️⃣  dossiers_legislatifs_an
    - uid (PK) - ex: DLR5L17N51035
    - legislature
    - titre
    - date_creation

7️⃣  textes_legislatifs_an
    - uid (PK) - ex: PIONANR5L17B0689
    - dossier_ref (FK → dossiers_legislatifs_an)
    - type_texte (PION, PRJL, etc.)
    - titre
    - legislature

8️⃣  amendements_an ⭐ (68 539 !)
    - uid (PK)
    - texte_legislatif_ref (FK → textes_legislatifs_an)
    - examen_ref
    - legislature
    - numero_long, numero_ordre_depot
    - auteur_acteur_ref (FK → acteurs_an)
    - auteur_groupe_ref (FK → organes_an)
    - cosignataires_acteur_refs (JSON array)
    - article_vise, division_titre
    - date_depot, date_publication
    - etat_code (ADO, REJ, IRR45, etc.)
    - etat_libelle
    - sort
    - dispositif (TEXT)
    - expose (TEXT)

9️⃣  reunions_an
    - uid (PK)
    - organe_ref (FK → organes_an)
    - date_reunion
    - type_reunion
    - presences (JSON)
    - interventions (JSON)

🔟  deports_an
    - uid (PK)
    - acteur_ref (FK → acteurs_an)
    - scrutin_ref (FK → scrutins_an) [si applicable]
    - legislature
    - raison
    - details (JSON)

EOF

echo ""

# 10. PROCHAINES ÉTAPES
echo "═══════════════════════════════════════════════════════════════"
echo "🚀  PROCHAINES ÉTAPES PROPOSÉES"
echo "═══════════════════════════════════════════════════════════════"
echo ""

cat << 'EOF'
OPTION A : Import Complet (Législature 17 uniquement)
  ⏱️  Durée estimée : 10-12h de dev
  📦  Volumétrie :
      • 603 acteurs
      • ~6 000 mandats (L17)
      • ~100 organes actifs
      • ~3 200 scrutins (L17)
      • ~68 000 amendements (L17)
  
  ✅  Avantages :
      • Données actuelles (2024-2029)
      • Base solide pour features avancées
      • Performances optimales
  
  ⚠️  Contraintes :
      • Pas d'historique (uniquement L17)
      • Nécessite migrations complexes

OPTION B : Proof of Concept (Échantillon)
  ⏱️  Durée estimée : 3-4h de dev
  📦  Volumétrie :
      • 603 acteurs (tous)
      • 100 derniers scrutins (L17)
      • 1 000 amendements échantillon
  
  ✅  Avantages :
      • Rapide à implémenter
      • Permet de valider le modèle
      • Tests fonctionnels immédiats
  
  ⚠️  Contraintes :
      • Données incomplètes
      • Nécessitera re-import complet après

OPTION C : Import Historique Complet
  ⏱️  Durée estimée : 15-20h de dev
  📦  Volumétrie :
      • 603 acteurs
      • 13 184 mandats (toutes législatures)
      • 8 957 organes
      • 3 876 scrutins
      • 68 539 amendements
  
  ✅  Avantages :
      • Historique complet (L9 → L17)
      • Analyses temporelles possibles
      • Comparaisons inter-législatures
  
  ⚠️  Contraintes :
      • Import très long
      • BDD volumineuse (~2-3 GB)
      • Complexité accrue

EOF

echo ""
echo "═══════════════════════════════════════════════════════════════"
echo "✅  ANALYSE TERMINÉE !"
echo "═══════════════════════════════════════════════════════════════"
echo ""
echo "📖  Consulter : ANALYSE_DONNEES_AN.md"
echo "🎯  Décision : Quelle option choisir ?"
echo ""

