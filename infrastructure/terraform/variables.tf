# =============================================================================
# Variables Terraform - CivicDash Infrastructure
# =============================================================================

# -----------------------------------------------------------------------------
# Proxmox Connection
# -----------------------------------------------------------------------------
variable "proxmox_api_url" {
  description = "URL de l'API Proxmox (ex: https://proxmox.local:8006/api2/json)"
  type        = string
}

variable "proxmox_api_token" {
  description = "Token API Proxmox (format: user@realm!tokenid=secret)"
  type        = string
  sensitive   = true
}

variable "proxmox_node" {
  description = "Nom du node Proxmox"
  type        = string
  default     = "pve"
}

variable "proxmox_insecure_ssl" {
  description = "Ignorer la vérification SSL (pour certificats auto-signés)"
  type        = bool
  default     = true
}

# -----------------------------------------------------------------------------
# Storage
# -----------------------------------------------------------------------------
variable "storage_pool" {
  description = "Pool de stockage Proxmox pour les containers"
  type        = string
  default     = "local-lvm"
}

# -----------------------------------------------------------------------------
# Network
# -----------------------------------------------------------------------------
variable "domain" {
  description = "Domaine principal de l'application"
  type        = string
  default     = "objectif2027.fr"
}

variable "public_ip" {
  description = "IP publique du serveur"
  type        = string
}

# -----------------------------------------------------------------------------
# SSH
# -----------------------------------------------------------------------------
variable "ssh_public_key" {
  description = "Clé SSH publique pour l'accès aux containers"
  type        = string
}

# -----------------------------------------------------------------------------
# Application
# -----------------------------------------------------------------------------
variable "app_version" {
  description = "Version de l'image CivicDash à déployer"
  type        = string
  default     = "latest"
}

variable "app_memory" {
  description = "RAM allouée à l'application (MB)"
  type        = number
  default     = 4096
}

variable "app_cores" {
  description = "Nombre de cores CPU pour l'application"
  type        = number
  default     = 4
}

# -----------------------------------------------------------------------------
# Database
# -----------------------------------------------------------------------------
variable "db_password" {
  description = "Mot de passe PostgreSQL"
  type        = string
  sensitive   = true
}

variable "db_memory" {
  description = "RAM allouée à PostgreSQL (MB)"
  type        = number
  default     = 2048
}

variable "db_cores" {
  description = "Nombre de cores CPU pour PostgreSQL"
  type        = number
  default     = 2
}

# -----------------------------------------------------------------------------
# Redis
# -----------------------------------------------------------------------------
variable "redis_password" {
  description = "Mot de passe Redis"
  type        = string
  sensitive   = true
}

# -----------------------------------------------------------------------------
# Meilisearch
# -----------------------------------------------------------------------------
variable "meili_master_key" {
  description = "Clé master Meilisearch"
  type        = string
  sensitive   = true
}

# -----------------------------------------------------------------------------
# Laravel
# -----------------------------------------------------------------------------
variable "app_key" {
  description = "Clé de l'application Laravel (APP_KEY)"
  type        = string
  sensitive   = true
}

variable "app_env" {
  description = "Environnement Laravel"
  type        = string
  default     = "production"
}

# -----------------------------------------------------------------------------
# Email
# -----------------------------------------------------------------------------
variable "mail_host" {
  description = "Serveur SMTP"
  type        = string
  default     = "smtp.mailgun.org"
}

variable "mail_username" {
  description = "Username SMTP"
  type        = string
  default     = ""
}

variable "mail_password" {
  description = "Password SMTP"
  type        = string
  sensitive   = true
  default     = ""
}

# -----------------------------------------------------------------------------
# Backup
# -----------------------------------------------------------------------------
variable "backup_enabled" {
  description = "Activer les backups automatiques"
  type        = bool
  default     = true
}

variable "backup_schedule" {
  description = "Planning des backups (format cron)"
  type        = string
  default     = "0 3 * * *"  # Tous les jours à 3h
}

variable "backup_retention" {
  description = "Nombre de backups à conserver"
  type        = number
  default     = 7
}
