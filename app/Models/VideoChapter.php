<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VideoChapter extends Model
{
    protected $table = 'video_chapters';

    protected $fillable = [
        'video_id',
        'reunion_uid',
        'chapter_nid',
        'parent_nid',
        'label',
        'chapter_type_key',
        'chapter_type_label',
        'theme_key',
        'theme_label',
        'speaker_vodalys_id',
        'speaker_an_uid',
        'speaker_name',
        'question_uid',
        'timecode_seconds',
        'sort_order',
    ];

    protected $casts = [
        'chapter_type_key' => 'integer',
        'theme_key' => 'integer',
        'timecode_seconds' => 'integer',
        'sort_order' => 'integer',
    ];

    public function reunion(): BelongsTo
    {
        return $this->belongsTo(ReunionAN::class, 'reunion_uid', 'uid');
    }

    public function speaker(): BelongsTo
    {
        return $this->belongsTo(ActeurAN::class, 'speaker_an_uid', 'uid');
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(QuestionAN::class, 'question_uid', 'uid');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_nid', 'chapter_nid')
            ->where('video_id', $this->video_id);
    }

    public function children()
    {
        return self::where('video_id', $this->video_id)
            ->where('parent_nid', $this->chapter_nid)
            ->orderBy('sort_order');
    }

    public function getVideoUrlAttribute(): ?string
    {
        if (! $this->video_id) {
            return null;
        }

        $base = "https://videos.assemblee-nationale.fr/video.{$this->video_id}";

        if ($this->chapter_nid) {
            return "{$base}?timecode={$this->chapter_nid}";
        }

        return $base;
    }

    public function scopeQuestions($query)
    {
        return $query->where('chapter_type_key', 4);
    }

    public function scopeReponses($query)
    {
        return $query->where('chapter_type_key', 6);
    }

    public function scopeAmendements($query)
    {
        return $query->where('chapter_type_key', 25);
    }

    public function scopeArticles($query)
    {
        return $query->where('chapter_type_key', 2);
    }

    public function scopeInterventions($query)
    {
        return $query->where('chapter_type_key', 7);
    }
}
