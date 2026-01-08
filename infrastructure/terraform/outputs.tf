# =============================================================================
# Outputs Terraform - CivicDash Infrastructure
# =============================================================================

output "infrastructure_summary" {
  description = "Résumé de l'infrastructure déployée"
  value = {
    proxmox_node = var.proxmox_node
    domain       = var.domain
    app_version  = var.app_version
  }
}

output "container_ips" {
  description = "Adresses IP des containers"
  value = {
    traefik     = proxmox_virtual_environment_container.traefik.initialization[0].ip_config[0].ipv4[0].address
    civicdash   = proxmox_virtual_environment_container.civicdash.initialization[0].ip_config[0].ipv4[0].address
    postgres    = proxmox_virtual_environment_container.postgres.initialization[0].ip_config[0].ipv4[0].address
    redis       = proxmox_virtual_environment_container.redis.initialization[0].ip_config[0].ipv4[0].address
    meilisearch = proxmox_virtual_environment_container.meilisearch.initialization[0].ip_config[0].ipv4[0].address
  }
}

output "container_ids" {
  description = "IDs des containers Proxmox"
  value = {
    traefik     = proxmox_virtual_environment_container.traefik.vm_id
    civicdash   = proxmox_virtual_environment_container.civicdash.vm_id
    postgres    = proxmox_virtual_environment_container.postgres.vm_id
    redis       = proxmox_virtual_environment_container.redis.vm_id
    meilisearch = proxmox_virtual_environment_container.meilisearch.vm_id
  }
}

output "access_urls" {
  description = "URLs d'accès aux services"
  value = {
    application      = "https://${var.domain}"
    traefik_dashboard = "https://traefik.${var.domain}"
    proxmox_gui      = var.proxmox_api_url
  }
}

output "ssh_commands" {
  description = "Commandes SSH pour accéder aux containers"
  value = {
    traefik     = "ssh root@${var.public_ip} pct enter 100"
    civicdash   = "ssh root@${var.public_ip} pct enter 101"
    postgres    = "ssh root@${var.public_ip} pct enter 102"
    redis       = "ssh root@${var.public_ip} pct enter 103"
    meilisearch = "ssh root@${var.public_ip} pct enter 104"
  }
}

output "next_steps" {
  description = "Prochaines étapes après le déploiement"
  value = <<-EOT

    ✅ Infrastructure déployée avec succès !

    Prochaines étapes :
    1. Configurer les DNS : ${var.domain} → ${var.public_ip}
    2. Vérifier les containers : ssh root@${var.public_ip}
    3. Lancer les migrations : pct exec 101 -- php artisan migrate
    4. Créer un admin : pct exec 101 -- php artisan user:create-admin

    Commandes utiles :
    - Voir les logs : pct exec 101 -- tail -f /var/www/html/storage/logs/laravel.log
    - Redémarrer l'app : pct exec 101 -- supervisorctl restart octane
    - Backup manuel : vzdump 101 102 103 104 --storage backup

  EOT
}
