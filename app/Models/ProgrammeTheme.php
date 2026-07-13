<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProgrammeTheme extends Model
{
    use HasFactory;

    protected $table = 'programme_themes';

    protected $fillable = [
        'slug', 'nom', 'icone', 'description', 'sources_taxonomie', 'ordre', 'actif',
    ];

    protected $casts = [
        'actif' => 'boolean',
        'ordre' => 'integer',
    ];

    public function mesures(): HasMany
    {
        return $this->hasMany(ProgrammeMesure::class, 'theme_id');
    }

    public function scopeActif($query)
    {
        return $query->where('actif', true);
    }

    public function scopeOrdonne($query)
    {
        return $query->orderBy('ordre')->orderBy('nom');
    }
}
