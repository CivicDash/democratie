#!/bin/bash

#######################################################################
# Script: Import des données Wikipedia pour les députés AN
# Description: Enrichit les acteurs AN avec URL, photo et extrait Wikipedia
# Auteur: CivicDash Team
# Date: 2025-11-20
#######################################################################

set -e

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_DIR="$(dirname "$SCRIPT_DIR")"

# Couleurs
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

echo -e "${BLUE}════════════════════════════════════════════════════════════════${NC}"
echo -e "${BLUE}   🏛️  IMPORT WIKIPEDIA - DÉPUTÉS ASSEMBLÉE NATIONALE${NC}"
echo -e "${BLUE}════════════════════════════════════════════════════════════════${NC}"
echo ""

# Menu interactif
echo -e "${YELLOW}Choisissez le mode d'import :${NC}"
echo ""
echo "1) 🧪 TEST (--limit=10 --dry-run)"
echo "2) 🔍 SIMULATION COMPLÈTE (--dry-run)"
echo "3) ✅ IMPORT COMPLET (tous les députés sans données Wikipedia)"
echo "4) 🔄 RÉIMPORT FORCÉ (tous les députés, même ceux déjà synchronisés)"
echo "5) 🎯 IMPORT LIMITÉ (spécifier le nombre)"
echo ""
read -p "Votre choix [1-5]: " choice

case $choice in
    1)
        echo -e "\n${BLUE}🧪 Mode TEST activé${NC}"
        CMD="docker compose exec app php artisan import:deputes-wikipedia --limit=10 --dry-run"
        ;;
    2)
        echo -e "\n${BLUE}🔍 Mode SIMULATION COMPLÈTE activé${NC}"
        CMD="docker compose exec app php artisan import:deputes-wikipedia --dry-run"
        ;;
    3)
        echo -e "\n${GREEN}✅ Mode IMPORT COMPLET activé${NC}"
        echo -e "${YELLOW}⚠️  Cela va modifier la base de données !${NC}"
        read -p "Confirmer ? (oui/non): " confirm
        if [[ "$confirm" != "oui" ]]; then
            echo -e "${RED}❌ Import annulé${NC}"
            exit 0
        fi
        CMD="docker compose exec app php artisan import:deputes-wikipedia"
        ;;
    4)
        echo -e "\n${GREEN}🔄 Mode RÉIMPORT FORCÉ activé${NC}"
        echo -e "${YELLOW}⚠️  Cela va écraser les données existantes !${NC}"
        read -p "Confirmer ? (oui/non): " confirm
        if [[ "$confirm" != "oui" ]]; then
            echo -e "${RED}❌ Import annulé${NC}"
            exit 0
        fi
        CMD="docker compose exec app php artisan import:deputes-wikipedia --force"
        ;;
    5)
        echo -e "\n${BLUE}🎯 Mode IMPORT LIMITÉ${NC}"
        read -p "Nombre de députés à traiter: " limit
        echo ""
        echo "1) Mode simulation (--dry-run)"
        echo "2) Mode réel (écriture en base)"
        read -p "Choix [1-2]: " mode
        
        if [[ "$mode" == "1" ]]; then
            CMD="docker compose exec app php artisan import:deputes-wikipedia --limit=$limit --dry-run"
        else
            CMD="docker compose exec app php artisan import:deputes-wikipedia --limit=$limit"
        fi
        ;;
    *)
        echo -e "${RED}❌ Choix invalide${NC}"
        exit 1
        ;;
esac

echo ""
echo -e "${BLUE}════════════════════════════════════════════════════════════════${NC}"
echo -e "${GREEN}▶ Commande: $CMD${NC}"
echo -e "${BLUE}════════════════════════════════════════════════════════════════${NC}"
echo ""

# Exécuter la commande
eval $CMD

EXIT_CODE=$?

echo ""
echo -e "${BLUE}════════════════════════════════════════════════════════════════${NC}"

if [ $EXIT_CODE -eq 0 ]; then
    echo -e "${GREEN}✅ Import Wikipedia terminé avec succès !${NC}"
    echo ""
    echo -e "${BLUE}📊 Vérifier les résultats :${NC}"
    echo ""
    echo -e "  ${YELLOW}# Compter les députés avec données Wikipedia${NC}"
    echo "  docker compose exec postgres psql -U civicdash -d civicdash -c \\"
    echo "    \"SELECT COUNT(*) as total, \\"
    echo "      COUNT(wikipedia_url) as avec_wikipedia, \\"
    echo "      COUNT(photo_wikipedia_url) as avec_photo \\"
    echo "    FROM acteurs_an WHERE wikipedia_url IS NOT NULL;\""
    echo ""
    echo -e "  ${YELLOW}# Exemples de députés enrichis${NC}"
    echo "  docker compose exec postgres psql -U civicdash -d civicdash -c \\"
    echo "    \"SELECT nom, prenom, wikipedia_url, \\"
    echo "      CASE WHEN photo_wikipedia_url IS NOT NULL THEN '✅' ELSE '❌' END as photo \\"
    echo "    FROM acteurs_an WHERE wikipedia_url IS NOT NULL LIMIT 10;\""
else
    echo -e "${RED}❌ Erreur lors de l'import (code $EXIT_CODE)${NC}"
    echo ""
    echo -e "${YELLOW}💡 Conseils de débogage :${NC}"
    echo "  - Vérifier les logs Laravel: storage/logs/laravel.log"
    echo "  - Vérifier la connexion Internet"
    echo "  - Relancer en mode --dry-run pour diagnostiquer"
fi

echo -e "${BLUE}════════════════════════════════════════════════════════════════${NC}"

exit $EXIT_CODE

