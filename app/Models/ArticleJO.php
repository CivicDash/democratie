<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ArticleJO extends Model
{
    protected $table = 'articles_jo';

    protected $fillable = [
        'texte_jo_id',
        'jorf_article_id',
        'numero',
        'type',
        'contenu',
    ];

    // ==========================================
    // RELATIONS
    // ==========================================

    public function texte(): BelongsTo
    {
        return $this->belongsTo(TexteJO::class, 'texte_jo_id');
    }
}
