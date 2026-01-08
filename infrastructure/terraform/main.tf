# =============================================================================
# CivicDash - Infrastructure Proxmox VE 9.1
# =============================================================================
# Déploiement automatisé via Terraform
# Utilise les images OCI pour créer des containers LXC
# =============================================================================

terraform {
  required_version = ">= 1.6.0"

  required_providers {
    proxmox = {
      source  = "bpg/proxmox"
      version = "~> 0.50"
    }
  }

  # Backend pour stocker l'état (optionnel, recommandé en équipe)
  # backend "s3" {
  #   bucket = "civicdash-terraform-state"
  #   key    = "proxmox/terraform.tfstate"
  #   region = "eu-west-3"
  # }
}

# -----------------------------------------------------------------------------
# Provider Proxmox
# -----------------------------------------------------------------------------
provider "proxmox" {
  endpoint  = var.proxmox_api_url
  api_token = var.proxmox_api_token
  insecure  = var.proxmox_insecure_ssl

  ssh {
    agent    = true
    username = "root"
  }
}

# -----------------------------------------------------------------------------
# Données locales
# -----------------------------------------------------------------------------
locals {
  # Tags communs
  common_tags = ["civicdash", "production", "terraform"]

  # Configuration réseau interne
  internal_network = "10.10.10"
  gateway          = "${local.internal_network}.1"

  # Mapping des containers
  containers = {
    traefik = {
      vmid     = 100
      ip       = "${local.internal_network}.10"
      memory   = 512
      cores    = 1
      disk     = 5
      image    = "docker://traefik:v3.0"
      startup  = "order=1"
    }
    civicdash = {
      vmid     = 101
      ip       = "${local.internal_network}.11"
      memory   = var.app_memory
      cores    = var.app_cores
      disk     = 30
      image    = "docker://ghcr.io/civicdash/app:${var.app_version}"
      startup  = "order=3"
    }
    postgres = {
      vmid     = 102
      ip       = "${local.internal_network}.12"
      memory   = var.db_memory
      cores    = var.db_cores
      disk     = 50
      image    = "docker://postgres:16-alpine"
      startup  = "order=2"
    }
    redis = {
      vmid     = 103
      ip       = "${local.internal_network}.13"
      memory   = 512
      cores    = 1
      disk     = 5
      image    = "docker://redis:7-alpine"
      startup  = "order=2"
    }
    meilisearch = {
      vmid     = 104
      ip       = "${local.internal_network}.14"
      memory   = 1024
      cores    = 2
      disk     = 20
      image    = "docker://getmeili/meilisearch:v1.6"
      startup  = "order=2"
    }
  }
}

# -----------------------------------------------------------------------------
# Réseau virtuel (Bridge interne)
# -----------------------------------------------------------------------------
resource "proxmox_virtual_environment_network_linux_bridge" "internal" {
  node_name = var.proxmox_node
  name      = "vmbr1"

  comment = "CivicDash internal network"

  # Pas d'interface physique, réseau purement virtuel
  autostart = true
}

# -----------------------------------------------------------------------------
# Container Traefik (Reverse Proxy)
# -----------------------------------------------------------------------------
resource "proxmox_virtual_environment_container" "traefik" {
  node_name = var.proxmox_node
  vm_id     = local.containers.traefik.vmid

  description = "Traefik reverse proxy for CivicDash"
  tags        = local.common_tags
  started     = true

  operating_system {
    template_file_id = local.containers.traefik.image
    type             = "alpine"
  }

  initialization {
    hostname = "traefik"

    ip_config {
      ipv4 {
        address = "${local.containers.traefik.ip}/24"
        gateway = local.gateway
      }
    }

    # Configuration Traefik via mount
    user_account {
      keys = [var.ssh_public_key]
    }
  }

  cpu {
    cores = local.containers.traefik.cores
  }

  memory {
    dedicated = local.containers.traefik.memory
  }

  disk {
    datastore_id = var.storage_pool
    size         = local.containers.traefik.disk
  }

  network_interface {
    name   = "eth0"
    bridge = "vmbr0"  # Accès externe
  }

  network_interface {
    name   = "eth1"
    bridge = proxmox_virtual_environment_network_linux_bridge.internal.name
  }

  startup {
    order = 1
  }

  features {
    nesting = true
  }
}

# -----------------------------------------------------------------------------
# Container PostgreSQL
# -----------------------------------------------------------------------------
resource "proxmox_virtual_environment_container" "postgres" {
  node_name = var.proxmox_node
  vm_id     = local.containers.postgres.vmid

  description = "PostgreSQL 16 database for CivicDash"
  tags        = local.common_tags
  started     = true

  operating_system {
    template_file_id = local.containers.postgres.image
    type             = "alpine"
  }

  initialization {
    hostname = "postgres"

    ip_config {
      ipv4 {
        address = "${local.containers.postgres.ip}/24"
        gateway = local.gateway
      }
    }

    user_account {
      keys = [var.ssh_public_key]
    }
  }

  cpu {
    cores = local.containers.postgres.cores
  }

  memory {
    dedicated = local.containers.postgres.memory
  }

  disk {
    datastore_id = var.storage_pool
    size         = local.containers.postgres.disk
  }

  network_interface {
    name   = "eth0"
    bridge = proxmox_virtual_environment_network_linux_bridge.internal.name
  }

  startup {
    order = 2
  }

  # Variables d'environnement PostgreSQL
  # Note: À configurer via cloud-init ou script post-création
}

# -----------------------------------------------------------------------------
# Container Redis
# -----------------------------------------------------------------------------
resource "proxmox_virtual_environment_container" "redis" {
  node_name = var.proxmox_node
  vm_id     = local.containers.redis.vmid

  description = "Redis cache for CivicDash"
  tags        = local.common_tags
  started     = true

  operating_system {
    template_file_id = local.containers.redis.image
    type             = "alpine"
  }

  initialization {
    hostname = "redis"

    ip_config {
      ipv4 {
        address = "${local.containers.redis.ip}/24"
        gateway = local.gateway
      }
    }

    user_account {
      keys = [var.ssh_public_key]
    }
  }

  cpu {
    cores = local.containers.redis.cores
  }

  memory {
    dedicated = local.containers.redis.memory
  }

  disk {
    datastore_id = var.storage_pool
    size         = local.containers.redis.disk
  }

  network_interface {
    name   = "eth0"
    bridge = proxmox_virtual_environment_network_linux_bridge.internal.name
  }

  startup {
    order = 2
  }
}

# -----------------------------------------------------------------------------
# Container Meilisearch
# -----------------------------------------------------------------------------
resource "proxmox_virtual_environment_container" "meilisearch" {
  node_name = var.proxmox_node
  vm_id     = local.containers.meilisearch.vmid

  description = "Meilisearch for CivicDash full-text search"
  tags        = local.common_tags
  started     = true

  operating_system {
    template_file_id = local.containers.meilisearch.image
    type             = "alpine"
  }

  initialization {
    hostname = "meilisearch"

    ip_config {
      ipv4 {
        address = "${local.containers.meilisearch.ip}/24"
        gateway = local.gateway
      }
    }

    user_account {
      keys = [var.ssh_public_key]
    }
  }

  cpu {
    cores = local.containers.meilisearch.cores
  }

  memory {
    dedicated = local.containers.meilisearch.memory
  }

  disk {
    datastore_id = var.storage_pool
    size         = local.containers.meilisearch.disk
  }

  network_interface {
    name   = "eth0"
    bridge = proxmox_virtual_environment_network_linux_bridge.internal.name
  }

  startup {
    order = 2
  }
}

# -----------------------------------------------------------------------------
# Container CivicDash (Application principale)
# -----------------------------------------------------------------------------
resource "proxmox_virtual_environment_container" "civicdash" {
  node_name = var.proxmox_node
  vm_id     = local.containers.civicdash.vmid

  description = "CivicDash Laravel application"
  tags        = local.common_tags
  started     = true

  # Dépendances - attendre que les services soient prêts
  depends_on = [
    proxmox_virtual_environment_container.postgres,
    proxmox_virtual_environment_container.redis,
    proxmox_virtual_environment_container.meilisearch,
  ]

  operating_system {
    template_file_id = local.containers.civicdash.image
    type             = "debian"
  }

  initialization {
    hostname = "civicdash"

    ip_config {
      ipv4 {
        address = "${local.containers.civicdash.ip}/24"
        gateway = local.gateway
      }
    }

    user_account {
      keys = [var.ssh_public_key]
    }
  }

  cpu {
    cores = local.containers.civicdash.cores
  }

  memory {
    dedicated = local.containers.civicdash.memory
  }

  disk {
    datastore_id = var.storage_pool
    size         = local.containers.civicdash.disk
  }

  network_interface {
    name   = "eth0"
    bridge = proxmox_virtual_environment_network_linux_bridge.internal.name
  }

  startup {
    order = 3
  }

  features {
    nesting = true
  }

  # Mount pour les fichiers persistants (uploads, etc.)
  mount_point {
    volume = "${var.storage_pool}:civicdash-storage"
    path   = "/var/www/html/storage/app/public"
  }
}

# -----------------------------------------------------------------------------
# Firewall Rules
# -----------------------------------------------------------------------------
resource "proxmox_virtual_environment_firewall_rules" "traefik" {
  node_name    = var.proxmox_node
  container_id = proxmox_virtual_environment_container.traefik.vm_id

  rule {
    type    = "in"
    action  = "ACCEPT"
    proto   = "tcp"
    dport   = "80"
    comment = "HTTP"
  }

  rule {
    type    = "in"
    action  = "ACCEPT"
    proto   = "tcp"
    dport   = "443"
    comment = "HTTPS"
  }
}
