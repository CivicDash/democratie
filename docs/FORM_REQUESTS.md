# Form Requests CivicDash

Cette documentation décrit tous les **Form Requests** de validation pour CivicDash.

## 📁 Form Requests créés

```
app/Http/Requests/
├── Topic/
│   ├── StoreTopicRequest.php
│   ├── UpdateTopicRequest.php
│   └── CreateBallotRequest.php
├── Post/
│   ├── StorePostRequest.php
│   ├── UpdatePostRequest.php
│   └── VotePostRequest.php
├── Vote/
│   ├── RequestBallotTokenRequest.php
│   └── CastVoteRequest.php
├── Budget/
│   ├── AllocateBudgetRequest.php
│   └── BulkAllocateBudgetRequest.php
├── Moderation/
│   ├── StoreReportRequest.php
│   ├── ResolveReportRequest.php
│   └── StoreSanctionRequest.php
└── Document/
    ├── UploadDocumentRequest.php
    └── VerifyDocumentRequest.php
```

**Total : 15 Form Requests**

---

## 📝 Topics

### StoreTopicRequest

Validation pour la création d'un nouveau topic.

**Autorisation** : `$user->can('create', Topic::class)`

**Règles** :
| Champ | Règles | Description |
|-------|--------|-------------|
| `title` | required, string, max:255, min:10 | Titre du topic |
| `description` | required, string, min:50 | Description détaillée |
| `type` | required, in:debate,bill,referendum | Type de topic |
| `status` | sometimes, in:draft,open | Statut initial |
| `scope` | required, in:national,region,dept | Portée territoriale |
| `region_id` | required_if:scope,region, exists | Région (si scope régional) |
| `department_id` | required_if:scope,dept, exists | Département (si scope départemental) |

**Validation personnalisée** :
- Les bills nécessitent la permission `topics.bill`

### UpdateTopicRequest

Validation pour la mise à jour d'un topic.

**Autorisation** : `$user->can('update', $topic)`

**Règles** : Identiques à `StoreTopicRequest` mais avec `sometimes` (champs optionnels).

### CreateBallotRequest

Validation pour créer un scrutin sur un topic.

**Autorisation** : `$user->can('createBallot', $topic)`

**Règles** :
| Champ | Règles | Description |
|-------|--------|-------------|
| `ballot_type` | required, in:yes_no,multiple_choice,ranked | Type de scrutin |
| `ballot_options` | required_if, array, min:2 | Options (si multiple_choice) |
| `voting_opens_at` | required, date, after:now | Date d'ouverture |
| `voting_deadline_at` | required, date, after:voting_opens_at | Date de fermeture |
| `allow_abstention` | sometimes, boolean | Permettre l'abstention |

---

## 💬 Posts

### StorePostRequest

Validation pour créer un post.

**Autorisation** : `$user->can('create', [Post::class, $topic])`

**Règles** :
| Champ | Règles | Description |
|-------|--------|-------------|
| `content` | required, string, min:10, max:10000 | Contenu du post |
| `parent_id` | nullable, exists:posts,id | Post parent (si réponse) |

**Validation personnalisée** :
- Le `parent_id` doit appartenir au même topic

### UpdatePostRequest

Validation pour mettre à jour un post.

**Autorisation** : `$user->can('update', $post)`

**Règles** : Identiques à `StorePostRequest` mais sans `parent_id`.

### VotePostRequest

Validation pour voter sur un post.

**Autorisation** : `$user->can('vote', $post)`

**Règles** :
| Champ | Règles | Description |
|-------|--------|-------------|
| `vote` | required, in:upvote,downvote | Type de vote |

---

## 🗳️ Vote/Ballot

### RequestBallotTokenRequest

Validation pour demander un token de vote.

**Autorisation** : `$user->can('vote', $topic)`

**Règles** : Aucune (juste vérification de l'autorisation).

### CastVoteRequest

Validation pour voter sur un scrutin.

**Autorisation** : Utilisateur authentifié (le token vérifie le droit réel).

**Règles** :
| Champ | Règles | Description |
|-------|--------|-------------|
| `token` | required, string, size:128 | Token de vote (SHA512) |
| `vote` | required, array | Données du vote |
| `vote.choice` | required, string | Choix de vote |

**Validation personnalisée** :
- Le choix doit correspondre au type de ballot :
  - `yes_no` : yes, no, abstain
  - `multiple_choice` : une des options définies

---

## 💰 Budget

### AllocateBudgetRequest

Validation pour allouer à un secteur.

**Autorisation** : `$user->can('create', UserAllocation::class)`

**Règles** :
| Champ | Règles | Description |
|-------|--------|-------------|
| `sector_id` | required, exists:sectors,id | Secteur budgétaire |
| `allocated_percent` | required, numeric, min:0, max:100 | Pourcentage |

**Validation personnalisée** :
- Respect des contraintes min/max du secteur
- Total ne dépasse pas 100%

### BulkAllocateBudgetRequest

Validation pour répartition complète du budget.

**Autorisation** : `$user->can('create', UserAllocation::class)`

**Règles** :
| Champ | Règles | Description |
|-------|--------|-------------|
| `allocations` | required, array | Tableau d'allocations |
| `allocations.*` | required, numeric, min:0, max:100 | Pourcentage par secteur |

**Validation personnalisée** :
- Total = 100% (tolérance 0.01%)
- Contraintes min/max pour chaque secteur

---

## 🚨 Modération

### StoreReportRequest

Validation pour créer un signalement.

**Autorisation** : `$user->can('create', Report::class)`

**Règles** :
| Champ | Règles | Description |
|-------|--------|-------------|
| `reportable_type` | required, in:Post,Topic,User | Type de contenu |
| `reportable_id` | required, integer | ID du contenu |
| `reason` | required, string, min:20, max:1000 | Raison détaillée |

**Validation personnalisée** :
- Le contenu signalé doit exister

### ResolveReportRequest

Validation pour résoudre un signalement.

**Autorisation** : `$user->can('resolve', $report)`

**Règles** :
| Champ | Règles | Description |
|-------|--------|-------------|
| `notes` | nullable, string, max:1000 | Notes du modérateur |
| `apply_action` | sometimes, boolean | Appliquer action (masquer) |

### StoreSanctionRequest

Validation pour créer une sanction.

**Autorisation** : `$user->can('create', [Sanction::class, $targetUser])`

**Règles** :
| Champ | Règles | Description |
|-------|--------|-------------|
| `type` | required, in:warning,mute,ban | Type de sanction |
| `reason` | required, string, min:20, max:1000 | Raison détaillée |
| `duration_days` | nullable, integer, min:1, max:365 | Durée (si temporaire) |

**Validation personnalisée** :
- Un warning ne peut pas avoir de durée
- Ban permanent nécessite rôle admin

---

## 📄 Documents

### UploadDocumentRequest

Validation pour uploader un document.

**Autorisation** : `$user->can('upload', Document::class)`

**Règles** :
| Champ | Règles | Description |
|-------|--------|-------------|
| `file` | required, file, max:10240, mimes | Fichier (10MB max) |
| `documentable_type` | required, in:Topic,Post | Type de contenu |
| `documentable_id` | required, integer | ID du contenu |
| `description` | nullable, string, max:500 | Description |

**Types MIME autorisés** : pdf, doc, docx, jpg, jpeg, png, txt, zip

**Validation personnalisée** :
- Le contenu associé doit exister

### VerifyDocumentRequest

Validation pour vérifier un document.

**Autorisation** : `$user->can('verify', $document)`

**Règles** :
| Champ | Règles | Description |
|-------|--------|-------------|
| `status` | required, in:verified,rejected | Résultat vérification |
| `notes` | nullable, string, max:1000 | Notes du vérificateur |

**Validation personnalisée** :
- Notes obligatoires si rejected

---

## 🎯 Utilisation dans les Controllers

### Exemple basique

```php
use App\Http\Requests\Topic\StoreTopicRequest;

class TopicController extends Controller
{
    public function store(StoreTopicRequest $request)
    {
        // La validation et l'autorisation sont déjà faites !
        $topic = Topic::create($request->validated());
        
        return response()->json($topic, 201);
    }
}
```

### Avec service

```php
use App\Http\Requests\Budget\BulkAllocateBudgetRequest;
use App\Services\BudgetService;

class BudgetController extends Controller
{
    public function __construct(
        protected BudgetService $budgetService
    ) {}

    public function allocate(BulkAllocateBudgetRequest $request)
    {
        $allocations = $this->budgetService->bulkAllocate(
            $request->user(),
            $request->validated('allocations')
        );
        
        return response()->json($allocations);
    }
}
```

### Gestion d'erreurs

```php
try {
    $validated = $request->validated();
    // ... logique
} catch (ValidationException $e) {
    return response()->json([
        'message' => 'Validation failed',
        'errors' => $e->errors()
    ], 422);
}
```

---

## 📝 Messages de validation

### Messages par défaut en français

Tous les Form Requests incluent des messages personnalisés en français :

```php
public function messages(): array
{
    return [
        'title.required' => 'Le titre du topic est obligatoire.',
        'title.min' => 'Le titre doit contenir au moins :min caractères.',
        'description.required' => 'La description du topic est obligatoire.',
        // ...
    ];
}
```

### Attributs personnalisés

```php
public function attributes(): array
{
    return [
        'title' => 'titre',
        'description' => 'description',
        'type' => 'type',
        // ...
    ];
}
```

---

## 🔧 Validation personnalisée

### Méthode `withValidator()`

Utilisée pour ajouter des validations complexes après les règles de base :

```php
public function withValidator($validator): void
{
    $validator->after(function ($validator) {
        // Vérifier le total = 100%
        $total = array_sum($this->allocations ?? []);
        if (abs($total - 100.0) > 0.01) {
            $validator->errors()->add(
                'allocations',
                "Le total doit être égal à 100% (actuel: {$total}%)."
            );
        }
    });
}
```

### Méthode `authorize()`

Intégration avec les policies :

```php
public function authorize(): bool
{
    $topic = $this->route('topic');
    return $this->user()->can('update', $topic);
}
```

---

## 🧪 Tests des Form Requests

Les Form Requests peuvent être testés :

```php
test('store topic request validates correctly', function () {
    $user = User::factory()->citizen()->create();
    
    $data = [
        'title' => 'Test',  // Trop court (min:10)
        'description' => 'Test description', // Trop court (min:50)
        'type' => 'invalid', // Type invalide
        'scope' => 'national',
    ];
    
    actingAs($user)
        ->postJson('/api/topics', $data)
        ->assertStatus(422)
        ->assertJsonValidationErrors(['title', 'description', 'type']);
});

test('store topic request authorizes correctly', function () {
    $mutedUser = User::factory()->citizen()->create();
    Sanction::factory()->mute()->create(['user_id' => $mutedUser->id]);
    
    actingAs($mutedUser)
        ->postJson('/api/topics', validTopicData())
        ->assertStatus(403);
});
```

---

## 📊 Récapitulatif

| Catégorie | Form Requests | Fonctionnalités |
|-----------|---------------|-----------------|
| **Topics** | 3 | Création, mise à jour, scrutins |
| **Posts** | 3 | Création, mise à jour, votes |
| **Vote/Ballot** | 2 | Token, vote anonyme |
| **Budget** | 2 | Allocation simple et en masse |
| **Modération** | 3 | Signalements, résolution, sanctions |
| **Documents** | 2 | Upload, vérification |
| **TOTAL** | **15** | **Validation complète** |

---

## ✅ Avantages des Form Requests

1. **Séparation des responsabilités** : Validation hors du controller
2. **Réutilisabilité** : Même validation pour API et Web
3. **Autorisation intégrée** : Policies directement dans `authorize()`
4. **Messages personnalisés** : Erreurs en français
5. **Validation complexe** : `withValidator()` pour logique avancée
6. **Testabilité** : Tests isolés possibles
7. **Documentation** : Code auto-documenté

---

## 🔗 Liens utiles

- [Laravel Validation](https://laravel.com/docs/11.x/validation)
- [Form Request Validation](https://laravel.com/docs/11.x/validation#form-request-validation)
- [Policies](../docs/POLICIES.md)
- [Services](../docs/SERVICES.md)

