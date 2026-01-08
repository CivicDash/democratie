#!/bin/bash
# =============================================================================
# CivicDash - Script de déploiement Proxmox
# =============================================================================
# Usage: ./deploy.sh [--version VERSION] [--plan-only] [--destroy]
# =============================================================================

set -euo pipefail

# Couleurs
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
CYAN='\033[0;36m'
NC='\033[0m' # No Color

# Variables
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
INFRA_DIR="$(dirname "$SCRIPT_DIR")"
TERRAFORM_DIR="$INFRA_DIR/terraform"
VERSION="latest"
PLAN_ONLY=false
DESTROY=false

# =============================================================================
# Fonctions
# =============================================================================

log_info() {
    echo -e "${BLUE}ℹ${NC} $1"
}

log_success() {
    echo -e "${GREEN}✅${NC} $1"
}

log_warning() {
    echo -e "${YELLOW}⚠️${NC} $1"
}

log_error() {
    echo -e "${RED}❌${NC} $1"
}

log_step() {
    echo -e "\n${CYAN}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
    echo -e "${CYAN}🚀 $1${NC}"
    echo -e "${CYAN}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}\n"
}

usage() {
    echo "Usage: $0 [OPTIONS]"
    echo ""
    echo "Options:"
    echo "  --version VERSION    Version de l'application à déployer (default: latest)"
    echo "  --plan-only          Afficher le plan sans appliquer"
    echo "  --destroy            Détruire l'infrastructure"
    echo "  --help               Afficher cette aide"
    echo ""
    echo "Exemples:"
    echo "  $0                           # Déploiement avec version 'latest'"
    echo "  $0 --version v1.1.1          # Déploiement avec version spécifique"
    echo "  $0 --plan-only               # Voir le plan uniquement"
    echo "  $0 --destroy                 # Détruire l'infrastructure"
}

check_requirements() {
    log_step "Vérification des prérequis"

    # Terraform
    if ! command -v terraform &> /dev/null; then
        log_error "Terraform n'est pas installé"
        echo "  → Installation: https://developer.hashicorp.com/terraform/downloads"
        exit 1
    fi
    log_success "Terraform $(terraform version -json | jq -r '.terraform_version') installé"

    # Fichier de variables
    if [[ ! -f "$TERRAFORM_DIR/terraform.tfvars" ]]; then
        log_error "Fichier terraform.tfvars manquant"
        echo "  → Copier et configurer: cp terraform.tfvars.example terraform.tfvars"
        exit 1
    fi
    log_success "Fichier terraform.tfvars présent"

    # Vérifier les variables sensibles
    if grep -q "change-me\|xxxxxxxx" "$TERRAFORM_DIR/terraform.tfvars"; then
        log_warning "Des valeurs par défaut sont encore présentes dans terraform.tfvars"
        echo "  → Vérifiez les mots de passe et tokens"
    fi
}

init_terraform() {
    log_step "Initialisation Terraform"
    
    cd "$TERRAFORM_DIR"
    
    terraform init -upgrade
    
    log_success "Terraform initialisé"
}

plan_infrastructure() {
    log_step "Planification de l'infrastructure"
    
    cd "$TERRAFORM_DIR"
    
    terraform plan \
        -var="app_version=$VERSION" \
        -out=tfplan \
        -detailed-exitcode || true
    
    log_success "Plan généré (tfplan)"
}

apply_infrastructure() {
    log_step "Application de l'infrastructure"
    
    cd "$TERRAFORM_DIR"
    
    if [[ ! -f "tfplan" ]]; then
        log_error "Fichier de plan manquant. Exécutez d'abord sans --plan-only"
        exit 1
    fi
    
    terraform apply tfplan
    
    # Nettoyer le plan
    rm -f tfplan
    
    log_success "Infrastructure déployée !"
}

destroy_infrastructure() {
    log_step "Destruction de l'infrastructure"
    
    cd "$TERRAFORM_DIR"
    
    log_warning "ATTENTION: Cette action va détruire tous les containers !"
    echo ""
    read -p "Êtes-vous sûr ? Tapez 'yes' pour confirmer: " confirm
    
    if [[ "$confirm" != "yes" ]]; then
        log_info "Opération annulée"
        exit 0
    fi
    
    terraform destroy -auto-approve
    
    log_success "Infrastructure détruite"
}

show_outputs() {
    log_step "Informations de déploiement"
    
    cd "$TERRAFORM_DIR"
    
    terraform output
}

# =============================================================================
# Parsing des arguments
# =============================================================================

while [[ $# -gt 0 ]]; do
    case $1 in
        --version)
            VERSION="$2"
            shift 2
            ;;
        --plan-only)
            PLAN_ONLY=true
            shift
            ;;
        --destroy)
            DESTROY=true
            shift
            ;;
        --help)
            usage
            exit 0
            ;;
        *)
            log_error "Option inconnue: $1"
            usage
            exit 1
            ;;
    esac
done

# =============================================================================
# Exécution principale
# =============================================================================

echo ""
echo -e "${CYAN}╔═══════════════════════════════════════════════════════════════╗${NC}"
echo -e "${CYAN}║                                                               ║${NC}"
echo -e "${CYAN}║   🏛️  CivicDash - Déploiement Infrastructure Proxmox         ║${NC}"
echo -e "${CYAN}║                                                               ║${NC}"
echo -e "${CYAN}╚═══════════════════════════════════════════════════════════════╝${NC}"
echo ""

log_info "Version: $VERSION"
log_info "Mode: $(if $DESTROY; then echo "DESTRUCTION"; elif $PLAN_ONLY; then echo "Plan uniquement"; else echo "Déploiement complet"; fi)"

# Vérifications
check_requirements

# Initialisation
init_terraform

if $DESTROY; then
    destroy_infrastructure
else
    # Plan
    plan_infrastructure
    
    if ! $PLAN_ONLY; then
        # Confirmation
        echo ""
        read -p "Appliquer ce plan ? (y/N) " confirm
        
        if [[ "$confirm" =~ ^[Yy]$ ]]; then
            apply_infrastructure
            show_outputs
        else
            log_info "Déploiement annulé"
            rm -f "$TERRAFORM_DIR/tfplan"
        fi
    else
        log_info "Mode plan-only - aucune modification appliquée"
    fi
fi

echo ""
log_success "Terminé !"
