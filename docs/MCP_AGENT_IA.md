# 🤖 Plan MCP - Agent IA CivicDash

## 📋 Vue d'ensemble

**Objectif** : Implémenter un agent IA local utilisant le Model Context Protocol (MCP) pour répondre aux questions des utilisateurs sur le fonctionnement de CivicDash et les données politiques françaises.

**Statut** : 📋 Planifié (T3-T4 2026)  
**Priorité** : 🟢 Moyenne (infrastructure à préparer)

---

## 🎯 Cas d'usage

### Questions utilisateurs supportées

```
👤 "Comment fonctionne le vote citoyen ?"
👤 "Qui est mon député ? (code postal 75001)"
👤 "Que dit la loi sur les retraites ?"
👤 "Comment interpeller un élu ?"
👤 "Quels sont les derniers scrutins ?"
👤 "Comment fonctionne la navette parlementaire ?"
```

### Fonctionnalités IA

| Fonction | Description | Priorité |
|----------|-------------|----------|
| FAQ dynamique | Réponses sur le fonctionnement du site | 🔴 Haute |
| Recherche sémantique | Trouver des lois/élus par description | 🔴 Haute |
| Explications pédagogiques | Vulgarisation du processus législatif | 🟡 Moyenne |
| Résumé de lois | Synthèse accessible des textes | 🟡 Moyenne |
| Comparaison de votes | Analyse des positions politiques | 🟢 Basse |

---

## 🏗️ Architecture technique

### Vue d'ensemble

```
┌─────────────────────────────────────────────────────────────────┐
│                        Frontend Vue.js                          │
│  ┌─────────────────────────────────────────────────────────┐   │
│  │              💬 Chat Widget (FloatingChat.vue)           │   │
│  └─────────────────────────────────────────────────────────┘   │
└─────────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────────┐
│                      Laravel Backend                            │
│  ┌─────────────┐  ┌─────────────┐  ┌─────────────────────────┐ │
│  │ ChatController│  │ MCPClient   │  │ Cache Redis (réponses) │ │
│  └─────────────┘  └─────────────┘  └─────────────────────────┘ │
└─────────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────────┐
│                     MCP Server (Node.js)                        │
│  ┌─────────────────────────────────────────────────────────┐   │
│  │                    Resources                             │   │
│  │  • elus (députés, sénateurs, maires, ministres)         │   │
│  │  • lois (textes, parcours, amendements)                 │   │
│  │  • scrutins (votes, résultats)                          │   │
│  │  • topics (idées citoyennes, interpellations)           │   │
│  │  • faq (aide, tutoriels)                                │   │
│  └─────────────────────────────────────────────────────────┘   │
│  ┌─────────────────────────────────────────────────────────┐   │
│  │                      Tools                               │   │
│  │  • search_elus(query, type, departement)                │   │
│  │  • search_lois(query, etat, thematique)                 │   │
│  │  • get_votes(elu_id, loi_id)                            │   │
│  │  • explain_process(topic)                               │   │
│  │  • find_representative(code_postal)                     │   │
│  └─────────────────────────────────────────────────────────┘   │
└─────────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────────┐
│                    Agent IA Local                               │
│  ┌─────────────────────────────────────────────────────────┐   │
│  │           Ollama / llama.cpp / vLLM                      │   │
│  │                                                          │   │
│  │  Modèles recommandés :                                   │   │
│  │  • Llama 3.1 8B (léger, rapide)                         │   │
│  │  • Mistral 7B (bon équilibre)                           │   │
│  │  • Phi-3 Medium (Microsoft, compact)                    │   │
│  │  • Llama 3.1 70B (haute qualité, GPU requis)            │   │
│  └─────────────────────────────────────────────────────────┘   │
└─────────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────────┐
│                    Base de données                              │
│  PostgreSQL + Meilisearch (recherche sémantique)               │
└─────────────────────────────────────────────────────────────────┘
```

---

## 📦 Stack technique

### Serveur MCP

| Composant | Technologie | Justification |
|-----------|-------------|---------------|
| Runtime | Node.js 20+ | SDK MCP officiel TypeScript |
| Framework | Express.js | API REST + WebSocket |
| Base de données | PostgreSQL (via Prisma) | Accès aux données CivicDash |
| Cache | Redis | Réponses fréquentes |
| Recherche | Meilisearch | Recherche sémantique rapide |

### Agent IA

| Option | RAM requise | GPU requis | Qualité | Latence |
|--------|-------------|------------|---------|---------|
| Phi-3 Mini 3.8B | 4 GB | Non | ⭐⭐⭐ | ~200ms |
| Llama 3.1 8B Q4 | 6 GB | Non | ⭐⭐⭐⭐ | ~500ms |
| Mistral 7B Q4 | 6 GB | Non | ⭐⭐⭐⭐ | ~400ms |
| Llama 3.1 70B Q4 | 40 GB | Oui (A100) | ⭐⭐⭐⭐⭐ | ~1s |

### Infrastructure production

```yaml
# Kubernetes cluster recommandé
nodes:
  - type: cpu
    count: 3
    specs: 8 vCPU, 32 GB RAM
    usage: Laravel, MCP Server, Redis

  - type: gpu  
    count: 2
    specs: A10G ou RTX 4090 (24GB VRAM)
    usage: Agent IA (Llama 3.1 70B)

load_balancer:
  type: nginx-ingress
  replicas: 2

autoscaling:
  min_replicas: 2
  max_replicas: 10
  target_cpu: 70%
```

---

## 🗓️ Plan de mise en œuvre

### Phase 1 : Préparation (1-2 jours) ✅ À faire maintenant

```
📋 Tâches :
├── [ ] Documenter les endpoints API existants
├── [ ] Identifier les données exposables via MCP
├── [ ] Créer la structure du projet MCP Server
├── [ ] Définir le schéma des Resources et Tools
└── [ ] Estimer les coûts infrastructure GPU
```

### Phase 2 : MCP Server basique (3-4 jours)

```
📋 Tâches :
├── [ ] Initialiser projet Node.js avec SDK MCP
├── [ ] Implémenter Resources :
│   ├── elus (liste, détail)
│   ├── lois (liste, détail, parcours)
│   ├── scrutins (liste, résultats)
│   └── faq (contenu statique)
├── [ ] Implémenter Tools :
│   ├── search_elus
│   ├── search_lois
│   ├── find_representative
│   └── explain_process
├── [ ] Tests unitaires
└── [ ] Documentation OpenAPI
```

### Phase 3 : Agent IA local (3-5 jours)

```
📋 Tâches :
├── [ ] Installer Ollama sur serveur de dev
├── [ ] Télécharger modèle (Llama 3.1 8B ou Mistral 7B)
├── [ ] Créer prompt système spécialisé politique FR
├── [ ] Connecter agent au MCP Server
├── [ ] Tests de qualité des réponses
├── [ ] Fine-tuning prompt (itérations)
└── [ ] Benchmarks latence/qualité
```

### Phase 4 : Intégration Frontend (2-3 jours)

```
📋 Tâches :
├── [ ] Créer ChatController Laravel
├── [ ] API WebSocket pour streaming
├── [ ] Composant FloatingChat.vue
├── [ ] Historique conversations (localStorage)
├── [ ] Rate limiting par utilisateur
└── [ ] Tests E2E
```

### Phase 5 : Production (1-2 semaines)

```
📋 Tâches :
├── [ ] Provisionner cluster Kubernetes
├── [ ] Configurer nodes GPU (Lambda Labs, Replicate, ou on-premise)
├── [ ] Load balancer + autoscaling
├── [ ] Monitoring (Prometheus + Grafana)
├── [ ] Logs centralisés (Loki)
├── [ ] Tests de charge
├── [ ] Documentation utilisateur
└── [ ] Déploiement progressif (canary)
```

---

## 💰 Estimation des coûts

### Option 1 : Cloud managé (Lambda Labs, Replicate)

| Service | Coût/heure | Coût/mois (estimé) |
|---------|------------|---------------------|
| A10G GPU | ~$0.75/h | ~$540/mois |
| CPU nodes (3x) | ~$0.10/h | ~$216/mois |
| Redis | ~$50/mois | $50/mois |
| Total | - | **~$800/mois** |

### Option 2 : On-premise (RTX 4090)

| Élément | Coût initial | Coût/mois |
|---------|--------------|-----------|
| RTX 4090 (x2) | ~$3,200 | - |
| Serveur dédié | ~$2,000 | ~$100 (électricité) |
| Total | ~$5,200 | **~$100/mois** |

### Option 3 : API externe (Claude API, OpenAI)

| Service | Coût estimé |
|---------|-------------|
| Claude API (Haiku) | ~$0.25/1M tokens |
| OpenAI GPT-4o-mini | ~$0.15/1M tokens |
| Coût mensuel (10K requêtes) | **~$50-100/mois** |

**Recommandation** : Commencer avec l'Option 3 (API externe) pour valider le produit, puis migrer vers Option 1 ou 2 si le volume justifie l'investissement.

---

## 🔒 Sécurité

### Points d'attention

1. **Rate limiting** : 10 requêtes/minute/utilisateur
2. **Validation des entrées** : Sanitization des questions
3. **Pas de données personnelles** : L'IA n'a pas accès aux emails, mots de passe
4. **Logs anonymisés** : Pas de stockage des questions avec identité
5. **Modération** : Filtre des réponses inappropriées
6. **Disclaimer** : "Je suis un assistant IA, mes réponses peuvent contenir des erreurs"

### Données exposées via MCP

| Donnée | Accessible | Justification |
|--------|------------|---------------|
| Noms des élus | ✅ Oui | Données publiques |
| Votes des élus | ✅ Oui | Données publiques |
| Textes de loi | ✅ Oui | Données publiques |
| Emails utilisateurs | ❌ Non | Données personnelles |
| Mots de passe | ❌ Non | Données sensibles |
| Topics privés | ❌ Non | Données personnelles |

---

## 📊 Métriques de succès

| Métrique | Objectif | Mesure |
|----------|----------|--------|
| Latence réponse | < 3s | P95 |
| Satisfaction utilisateur | > 80% | Thumbs up/down |
| Taux de réponse correcte | > 90% | Évaluation manuelle |
| Uptime | > 99.5% | Monitoring |
| Requêtes/jour | 1000+ | Analytics |

---

## 📚 Ressources

### Documentation MCP

- [MCP Specification](https://modelcontextprotocol.io/specification)
- [MCP TypeScript SDK](https://github.com/modelcontextprotocol/typescript-sdk)
- [MCP Python SDK](https://github.com/modelcontextprotocol/python-sdk)

### Modèles IA

- [Ollama](https://ollama.ai/) - Gestion locale de LLMs
- [llama.cpp](https://github.com/ggerganov/llama.cpp) - Inférence CPU optimisée
- [vLLM](https://github.com/vllm-project/vllm) - Inférence GPU haute performance

### Infrastructure

- [Lambda Labs](https://lambdalabs.com/) - Cloud GPU
- [Replicate](https://replicate.com/) - API de modèles
- [RunPod](https://www.runpod.io/) - GPU serverless

---

## ✅ Prérequis avant implémentation

1. **Infrastructure GPU disponible** (ou budget cloud)
2. **Stabilisation de la plateforme** (bugs critiques résolus)
3. **Base utilisateurs suffisante** (justifier l'investissement)
4. **Équipe disponible** pour maintenance et amélioration continue

---

**Dernière mise à jour** : 3 janvier 2026  
**Auteur** : CivicDash Core Team
