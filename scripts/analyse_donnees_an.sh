#!/bin/bash

##############################################################################
# 🔍 Script d'analyse des données JSON de l'Assemblée Nationale
##############################################################################
# 
# Ce script permet d'explorer les données JSON et de comprendre leur structure
# sans nécessiter d'import complet en base de données.
#
# Usage:
#   bash scripts/analyse_donnees_an.sh
#
##############################################################################

set -e

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(dirname "$SCRIPT_DIR")"
DATA_DIR="$PROJECT_ROOT/public/data"

echo "═══════════════════════════════════════════════════════════════"
echo "🔍  ANALYSE DES DONNÉES JSON DE L'ASSEMBLÉE NATIONALE"
echo "═══════════════════════════════════════════════════════════════"
echo ""

# Fonction pour compter les fichiers
count_files() {
    local dir=$1
    local count=$(find "$dir" -name "*.json" 2>/dev/null | wc -l)
    echo "$count"
}

# 1. INVENTAIRE
echo "📊  INVENTAIRE DES FICHIERS"
echo "───────────────────────────────────────────────────────────────"
echo ""
echo "  📁 acteur/     : $(count_files "$DATA_DIR/acteur") fichiers"
echo "  📁 mandat/     : $(count_files "$DATA_DIR/mandat") fichiers"
echo "  📁 organe/     : $(count_files "$DATA_DIR/organe") fichiers"
echo "  📁 scrutins/   : $(count_files "$DATA_DIR/scrutins") fichiers"
echo "  📁 reunion/    : $(count_files "$DATA_DIR/reunion") fichiers"
echo "  📁 deport/     : $(count_files "$DATA_DIR/deport") fichiers"
echo "  📁 pays/       : $(count_files "$DATA_DIR/pays") fichiers"
echo ""

# 2. ÉCHANTILLONS
echo "═══════════════════════════════════════════════════════════════"
echo "🔬  ÉCHANTILLONS DE DONNÉES"
echo "═══════════════════════════════════════════════════════════════"
echo ""

# 2.1 Acteur
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "👤  EXEMPLE D'ACTEUR"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
ACTEUR_FILE=$(find "$DATA_DIR/acteur" -name "*.json" | head -1)
if [ -f "$ACTEUR_FILE" ]; then
    echo "📄 Fichier : $(basename "$ACTEUR_FILE")"
    echo ""
    cat "$ACTEUR_FILE" | python3 -c "
import json, sys
data = json.load(sys.stdin)
acteur = data['acteur']
print(f\"  UID         : {acteur['uid']['#text'] if isinstance(acteur['uid'], dict) else acteur['uid']}\")
print(f\"  Nom         : {acteur['etatCivil']['ident']['prenom']} {acteur['etatCivil']['ident']['nom']}\")
print(f\"  Naissance   : {acteur['etatCivil']['infoNaissance']['dateNais']}\")
if 'profession' in acteur:
    print(f\"  Profession  : {acteur['profession'].get('libelleCourant', 'N/A')}\")
" 2>/dev/null || echo "  ⚠️  Erreur lors du parsing"
fi
echo ""

# 2.2 Scrutin
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "🗳️  EXEMPLE DE SCRUTIN"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
SCRUTIN_FILE=$(find "$DATA_DIR/scrutins" -name "*.json" | head -1)
if [ -f "$SCRUTIN_FILE" ]; then
    echo "📄 Fichier : $(basename "$SCRUTIN_FILE")"
    echo ""
    cat "$SCRUTIN_FILE" | python3 -c "
import json, sys
data = json.load(sys.stdin)
scrutin = data['scrutin']
print(f\"  UID         : {scrutin['uid']}\")
print(f\"  Numéro      : {scrutin['numero']}\")
print(f\"  Législature : {scrutin['legislature']}\")
print(f\"  Date        : {scrutin['dateScrutin']}\")
print(f\"  Type        : {scrutin['typeVote']['libelleTypeVote']}\")
print(f\"  Résultat    : {scrutin['sort']['libelle']}\")
print(f\"  Votants     : {scrutin['syntheseVote']['nombreVotants']}\")
print(f\"  Pour        : {scrutin['syntheseVote']['decompte']['pour']}\")
print(f\"  Contre      : {scrutin['syntheseVote']['decompte']['contre']}\")
print(f\"  Abstentions : {scrutin['syntheseVote']['decompte']['abstentions']}\")
print(f\"  Titre       : {scrutin['titre'][:100]}...\")
" 2>/dev/null || echo "  ⚠️  Erreur lors du parsing"
fi
echo ""

# 2.3 Organe
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "🏛️  EXEMPLE D'ORGANE"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
ORGANE_FILE=$(find "$DATA_DIR/organe" -name "*.json" | grep -v "ORGEXTPARL" | head -1)
if [ -f "$ORGANE_FILE" ]; then
    echo "📄 Fichier : $(basename "$ORGANE_FILE")"
    echo ""
    cat "$ORGANE_FILE" | python3 -c "
import json, sys
data = json.load(sys.stdin)
organe = data['organe']
print(f\"  UID         : {organe['uid']}\")
print(f\"  Type        : {organe.get('codeType', 'N/A')}\")
print(f\"  Libellé     : {organe['libelle']}\")
if 'libelleAbrege' in organe and organe['libelleAbrege']:
    print(f\"  Abréviation : {organe['libelleAbrege']}\")
if 'viMoDe' in organe:
    print(f\"  Date début  : {organe['viMoDe']['dateDebut']}\")
    if organe['viMoDe'].get('dateFin'):
        print(f\"  Date fin    : {organe['viMoDe']['dateFin']}\")
if 'legislature' in organe and organe['legislature']:
    print(f\"  Législature : {organe['legislature']}\")
" 2>/dev/null || echo "  ⚠️  Erreur lors du parsing"
fi
echo ""

# 3. ANALYSE DES LÉGISLATURES
echo "═══════════════════════════════════════════════════════════════"
echo "📈  ANALYSE PAR LÉGISLATURE"
echo "═══════════════════════════════════════════════════════════════"
echo ""

if command -v jq &> /dev/null; then
    echo "🔍  Répartition des scrutins par législature :"
    echo ""
    find "$DATA_DIR/scrutins" -name "*.json" -exec jq -r '.scrutin.legislature' {} \; 2>/dev/null | sort | uniq -c | sort -rn | head -10 | while read count leg; do
        echo "  📊 Législature $leg : $count scrutins"
    done
    echo ""
    
    echo "🔍  Répartition des mandats par législature :"
    echo ""
    find "$DATA_DIR/mandat" -name "*.json" -exec jq -r '.mandat.legislature // "N/A"' {} \; 2>/dev/null | sort | uniq -c | sort -rn | head -10 | while read count leg; do
        if [ "$leg" != "N/A" ]; then
            echo "  📊 Législature $leg : $count mandats"
        fi
    done
    echo ""
else
    echo "⚠️  jq non installé, analyse détaillée impossible"
    echo "   Installation : sudo apt-get install jq"
    echo ""
fi

# 4. TYPES D'ORGANES
echo "═══════════════════════════════════════════════════════════════"
echo "🏛️  TYPES D'ORGANES"
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
            *) desc="$type" ;;
        esac
        printf "  📂 %-20s : %4d organes\n" "$desc" "$count"
    done
    echo ""
fi

# 5. RECOMMANDATIONS
echo "═══════════════════════════════════════════════════════════════"
echo "💡  RECOMMANDATIONS"
echo "═══════════════════════════════════════════════════════════════"
echo ""
echo "  1️⃣  Commencer par importer les ACTEURS (603 députés/sénateurs)"
echo "  2️⃣  Puis importer les ORGANES (groupes + commissions)"
echo "  3️⃣  Ensuite importer les MANDATS (relations acteurs ↔ organes)"
echo "  4️⃣  Enfin importer les SCRUTINS (votes nominatifs)"
echo ""
echo "  📋 Ordre suggéré des commandes Laravel :"
echo "     php artisan import:acteurs-an --legislature=17"
echo "     php artisan import:organes-an --legislature=17"
echo "     php artisan import:mandats-an --legislature=17"
echo "     php artisan import:scrutins-an --legislature=17 --limit=100"
echo ""

# 6. STATISTIQUES RAPIDES
echo "═══════════════════════════════════════════════════════════════"
echo "📊  STATISTIQUES RAPIDES"
echo "═══════════════════════════════════════════════════════════════"
echo ""

TOTAL_FILES=$(find "$DATA_DIR" -name "*.json" | wc -l)
TOTAL_SIZE=$(du -sh "$DATA_DIR" | cut -f1)

echo "  📁 Total de fichiers JSON : $TOTAL_FILES"
echo "  💾 Taille totale          : $TOTAL_SIZE"
echo ""

if command -v jq &> /dev/null; then
    # Compter les scrutins de la législature 17
    LEG17_SCRUTINS=$(find "$DATA_DIR/scrutins" -name "*.json" -exec jq -r 'select(.scrutin.legislature == "17") | .scrutin.uid' {} \; 2>/dev/null | wc -l)
    echo "  🗳️  Scrutins législature 17 : $LEG17_SCRUTINS"
    
    # Compter les mandats de la législature 17
    LEG17_MANDATS=$(find "$DATA_DIR/mandat" -name "*.json" -exec jq -r 'select(.mandat.legislature == "17") | .mandat.uid' {} \; 2>/dev/null | wc -l)
    echo "  📋 Mandats législature 17   : $LEG17_MANDATS"
    echo ""
fi

echo "═══════════════════════════════════════════════════════════════"
echo "✅  ANALYSE TERMINÉE !"
echo "═══════════════════════════════════════════════════════════════"
echo ""
echo "📖  Consulter le document complet : ANALYSE_DONNEES_AN.md"
echo ""

