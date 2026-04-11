<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Cache;

class CommunePage extends Model
{
    protected $fillable = [
        'code_insee',
        'ville_id',
        'image_couverture_path',
        'logo_path',
        'couleur_primaire',
        'couleur_secondaire',
        'description_courte',
        'mot_du_maire',
        'adresse_mairie',
        'telephone',
        'email_mairie',
        'site_officiel',
        'horaires_ouverture',
        'facebook_url',
        'twitter_url',
        'instagram_url',
        'youtube_url',
        'linkedin_url',
        'actus_actives',
        'evenements_actifs',
        'forum_actif',
        'notifications_actives',
        'statut',
        'reclamee_par',
        'reclamee_at',
        'verifiee_par',
        'verifiee_at',
        'verification_niveau',
        'verification_code',
        'verification_code_expire_at',
        'vues_totales',
        'abonnes_count',
    ];

    protected $casts = [
        'horaires_ouverture' => 'array',
        'actus_actives' => 'boolean',
        'evenements_actifs' => 'boolean',
        'forum_actif' => 'boolean',
        'notifications_actives' => 'boolean',
        'reclamee_at' => 'datetime',
        'verifiee_at' => 'datetime',
        'verification_code_expire_at' => 'datetime',
        'vues_totales' => 'integer',
        'abonnes_count' => 'integer',
    ];

    // ========================================================================
    // RELATIONS
    // ========================================================================

    public function ville(): BelongsTo
    {
        return $this->belongsTo(Ville::class);
    }

    public function reclameur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reclamee_par');
    }

    public function verificateur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verifiee_par');
    }

    public function admins(): HasMany
    {
        return $this->hasMany(CommuneAdmin::class);
    }

    public function articles(): HasMany
    {
        return $this->hasMany(CommuneArticle::class);
    }

    public function evenements(): HasMany
    {
        return $this->hasMany(CommuneEvenement::class);
    }

    public function abonnements(): HasMany
    {
        return $this->hasMany(CommuneAbonnement::class, 'commune_code_insee', 'code_insee');
    }

    public function galerieImages(): HasMany
    {
        return $this->hasMany(CommuneGalerieImage::class)->orderBy('ordre');
    }

    public function consultations(): HasMany
    {
        return $this->hasMany(CommuneConsultation::class);
    }

    public function topics(): HasMany
    {
        return $this->hasMany(Topic::class, 'commune_code_insee', 'code_insee');
    }

    // ========================================================================
    // SCOPES
    // ========================================================================

    public function scopeAutoGeneree($query)
    {
        return $query->where('statut', 'auto_generee');
    }

    public function scopeActive($query)
    {
        return $query->where('statut', 'active');
    }

    public function scopeReclamee($query)
    {
        return $query->where('statut', 'reclamee');
    }

    public function scopeGeree($query)
    {
        return $query->whereIn('statut', ['active', 'reclamee']);
    }

    // ========================================================================
    // ACCESSEURS
    // ========================================================================

    public function getEstReclameeAttribute(): bool
    {
        return in_array($this->statut, ['reclamee', 'active']);
    }

    public function getEstActiveAttribute(): bool
    {
        return $this->statut === 'active';
    }

    public function getImageCouvertureUrlAttribute(): ?string
    {
        return $this->image_couverture_path
            ? asset('storage/'.$this->image_couverture_path)
            : null;
    }

    public function getLogoUrlAttribute(): ?string
    {
        return $this->logo_path
            ? asset('storage/'.$this->logo_path)
            : null;
    }

    public function getReseauxSociauxAttribute(): array
    {
        return array_filter([
            'facebook' => $this->facebook_url,
            'twitter' => $this->twitter_url,
            'instagram' => $this->instagram_url,
            'youtube' => $this->youtube_url,
            'linkedin' => $this->linkedin_url,
        ]);
    }

    public function getSlugAttribute(): string
    {
        return $this->ville?->slug ?? $this->code_insee;
    }

    public function getSousDomaineAttribute(): string
    {
        return $this->slug.'.civicdash.fr';
    }

    // ========================================================================
    // MÉTHODES
    // ========================================================================

    public static function findByCodeInsee(string $codeInsee): ?self
    {
        return Cache::remember(
            "commune_page:{$codeInsee}",
            3600,
            fn () => static::with('ville')->where('code_insee', $codeInsee)->first()
        );
    }

    public static function findBySlug(string $slug): ?self
    {
        return Cache::remember(
            "commune_page_slug:{$slug}",
            3600,
            fn () => static::with('ville')
                ->whereHas('ville', fn ($q) => $q->where('slug', $slug))
                ->first()
        );
    }

    public function reclamer(User $user, string $niveau): void
    {
        $this->update([
            'statut' => 'reclamee',
            'reclamee_par' => $user->id,
            'reclamee_at' => now(),
            'verification_niveau' => $niveau,
        ]);

        Cache::forget("commune_page:{$this->code_insee}");
        Cache::forget("commune_page_slug:{$this->slug}");
    }

    public function activer(User $admin): void
    {
        $this->update([
            'statut' => 'active',
            'verifiee_par' => $admin->id,
            'verifiee_at' => now(),
            'actus_actives' => true,
            'evenements_actifs' => true,
            'notifications_actives' => true,
        ]);

        Cache::forget("commune_page:{$this->code_insee}");
        Cache::forget("commune_page_slug:{$this->slug}");
    }

    public function suspendre(): void
    {
        $this->update(['statut' => 'suspendue']);

        Cache::forget("commune_page:{$this->code_insee}");
        Cache::forget("commune_page_slug:{$this->slug}");
    }

    public function genererCodeVerification(): string
    {
        $code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        $this->update([
            'verification_code' => $code,
            'verification_code_expire_at' => now()->addHours(24),
        ]);

        return $code;
    }

    public function verifierCode(string $code): bool
    {
        return $this->verification_code === $code
            && $this->verification_code_expire_at?->isFuture();
    }

    public function incrementerVues(): void
    {
        $this->increment('vues_totales');
    }

    public function estAdministrePar(User $user): bool
    {
        return $this->admins()->where('user_id', $user->id)->exists();
    }

    public function roleAdmin(User $user): ?string
    {
        return $this->admins()->where('user_id', $user->id)->value('role');
    }
}
