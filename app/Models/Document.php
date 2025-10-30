<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Scout\Searchable;

/**
 * Document vérifié (uploadable par legislator/state)
 * 
 * @property int $id
 * @property string $title
 * @property string|null $description
 * @property string $filename
 * @property string $path
 * @property string $mime_type
 * @property int $size Taille en bytes
 * @property string $hash SHA256 du fichier
 * @property string $documentable_type
 * @property int $documentable_id
 * @property int $uploader_id
 * @property string $status pending|verified|rejected
 * @property bool $is_public
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 */
class Document extends Model
{
    use HasFactory, SoftDeletes, Searchable;

    protected $fillable = [
        'title',
        'description',
        'filename',
        'path',
        'mime_type',
        'size',
        'hash',
        'documentable_type',
        'documentable_id',
        'uploader_id',
        'status',
        'is_public',
    ];

    protected $casts = [
        'size' => 'integer',
        'is_public' => 'boolean',
    ];

    /**
     * Uploadeur (legislator/state/admin)
     */
    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploader_id');
    }

    /**
     * Contenu associé (polymorphic: topic, post, etc.)
     */
    public function documentable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Vérifications du document
     */
    public function verifications(): HasMany
    {
        return $this->hasMany(Verification::class);
    }

    /**
     * Hash un fichier (SHA256)
     */
    public static function hashFile(string $filepath): string
    {
        return hash_file('sha256', $filepath);
    }

    /**
     * Formatte la taille en lecture humaine
     */
    public function getHumanSizeAttribute(): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $size = $this->size;
        $unit = 0;

        while ($size >= 1024 && $unit < count($units) - 1) {
            $size /= 1024;
            $unit++;
        }

        return round($size, 2) . ' ' . $units[$unit];
    }

    /**
     * Vérifie si le document est vérifié
     */
    public function isVerified(): bool
    {
        return $this->status === 'verified';
    }

    /**
     * Vérifie si le document est rejeté
     */
    public function isRejected(): bool
    {
        return $this->status === 'rejected';
    }

    /**
     * Vérifie si le document est en attente
     */
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    /**
     * Scope: documents publics
     */
    public function scopePublic($query)
    {
        return $query->where('is_public', true);
    }

    /**
     * Scope: documents vérifiés
     */
    public function scopeVerified($query)
    {
        return $query->where('status', 'verified');
    }

    /**
     * Scope: documents en attente
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope: documents rejetés
     */
    public function scopeRejected($query)
    {
        return $query->where('status', 'rejected');
    }

    /**
     * Scope: documents par type MIME
     */
    public function scopeByMimeType($query, string $mimeType)
    {
        return $query->where('mime_type', $mimeType);
    }

    /**
     * Scope: documents PDF uniquement
     */
    public function scopePdf($query)
    {
        return $query->where('mime_type', 'application/pdf');
    }

    // ========================================================================
    // SCOUT / MEILISEARCH
    // ========================================================================

    /**
     * Get the indexable data array for the model.
     */
    public function toSearchableArray(): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'filename' => $this->filename,
            'mime_type' => $this->mime_type,
            'status' => $this->status,
            'is_public' => $this->is_public,
            'uploader_name' => $this->uploader?->name,
            'documentable_type' => $this->documentable_type,
            'created_at' => $this->created_at->timestamp,
        ];
    }

    /**
     * Get the name of the index associated with the model.
     */
    public function searchableAs(): string
    {
        return 'documents_index';
    }

    /**
     * Determine if the model should be searchable.
     */
    public function shouldBeSearchable(): bool
    {
        return $this->is_public && $this->status === 'verified' && !$this->trashed();
    }
}


