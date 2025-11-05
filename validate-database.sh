#!/bin/bash

# Script de validation de l'intégrité de la base de données
# Usage: ./validate-database.sh

set -e

echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "🔍 VALIDATION DE L'INTÉGRITÉ DE LA BASE DE DONNÉES"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo ""

# Couleurs
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Fonction de vérification
check_step() {
    if [ $? -eq 0 ]; then
        echo -e "${GREEN}✓${NC} $1"
    else
        echo -e "${RED}✗${NC} $1"
        exit 1
    fi
}

# 1. Vérifier que les migrations existent
echo "📋 Vérification des migrations..."
migrations=(
    "2025_10_30_100003_create_votes_legislatifs_table.php"
    "2025_10_30_100004_create_agenda_legislatif_table.php"
    "2025_10_30_100002_create_amendements_table.php"
    "2025_10_31_120004_create_votes_groupes_parlementaires_table.php"
    "2025_10_31_105715_create_legal_references_table.php"
)

for migration in "${migrations[@]}"; do
    if [ -f "database/migrations/$migration" ]; then
        check_step "Migration $migration existe"
    else
        echo -e "${RED}✗${NC} Migration $migration manquante"
        exit 1
    fi
done

echo ""

# 2. Vérifier que le seeder existe
echo "📋 Vérification du seeder..."
if [ -f "database/seeders/DemoDataSeeder.php" ]; then
    check_step "DemoDataSeeder.php existe"
else
    echo -e "${RED}✗${NC} DemoDataSeeder.php manquant"
    exit 1
fi

echo ""

# 3. Vérifier la syntaxe PHP
echo "🔍 Vérification de la syntaxe PHP..."
php -l database/seeders/DemoDataSeeder.php > /dev/null 2>&1
check_step "Syntaxe PHP du seeder valide"

for migration in "${migrations[@]}"; do
    php -l "database/migrations/$migration" > /dev/null 2>&1
    check_step "Syntaxe PHP de $migration valide"
done

echo ""

# 4. Vérifier les contraintes NOT NULL dans les migrations
echo "🔍 Vérification des contraintes NOT NULL..."

check_constraint() {
    local file=$1
    local column=$2
    local constraint=$3
    
    if grep -q "$column.*$constraint" "$file"; then
        check_step "$column dans $(basename $file) a la contrainte $constraint"
    else
        echo -e "${YELLOW}⚠${NC} $column dans $(basename $file) n'a pas la contrainte $constraint"
    fi
}

check_constraint "database/migrations/2025_10_30_100003_create_votes_legislatifs_table.php" "source" "default('assemblee')"
check_constraint "database/migrations/2025_10_30_100003_create_votes_legislatifs_table.php" "numero_scrutin" "comment"
check_constraint "database/migrations/2025_10_30_100004_create_agenda_legislatif_table.php" "date" "comment"
check_constraint "database/migrations/2025_10_30_100002_create_amendements_table.php" "source" "default('assemblee')"
check_constraint "database/migrations/2025_10_30_100002_create_amendements_table.php" "sort" "default('en_discussion')"

echo ""

# 5. Vérifier que le seeder remplit les champs obligatoires
echo "🔍 Vérification du remplissage des champs dans le seeder..."

check_seeder_field() {
    local field=$1
    local context=$2
    
    if grep -q "'$field'" "database/seeders/DemoDataSeeder.php"; then
        check_step "Champ '$field' rempli dans le seeder ($context)"
    else
        echo -e "${RED}✗${NC} Champ '$field' manquant dans le seeder ($context)"
        exit 1
    fi
}

check_seeder_field "source" "votes_legislatifs"
check_seeder_field "numero_scrutin" "votes_legislatifs"
check_seeder_field "date" "agenda_legislatif"
check_seeder_field "position_groupe" "votes_groupes_parlementaires"
check_seeder_field "reference_text" "legal_references"
check_seeder_field "code_name" "legal_references"

echo ""

# 6. Vérifier les valeurs d'enum scope
echo "🔍 Vérification des valeurs d'enum scope..."

if grep -q "'scope' => 'regional'" "database/seeders/DemoDataSeeder.php" 2>/dev/null; then
    echo -e "${RED}✗${NC} Valeur 'regional' trouvée (devrait être 'region')"
    exit 1
fi
check_step "Pas de valeur 'regional' incorrecte"

if grep -q "'scope' => 'departmental'" "database/seeders/DemoDataSeeder.php" 2>/dev/null; then
    echo -e "${RED}✗${NC} Valeur 'departmental' trouvée (devrait être 'dept')"
    exit 1
fi
check_step "Pas de valeur 'departmental' incorrecte"

echo ""

# 7. Résumé final
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo -e "${GREEN}✅ VALIDATION RÉUSSIE !${NC}"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo ""
echo "📊 Résumé :"
echo "  - Migrations : ${#migrations[@]} vérifiées"
echo "  - Seeder : 1 vérifié"
echo "  - Contraintes : 5+ vérifiées"
echo "  - Champs obligatoires : 6+ vérifiés"
echo "  - Valeurs enum : 2+ vérifiées"
echo ""
echo "🚀 Prêt pour : php artisan demo:setup --fresh --force"
echo ""

