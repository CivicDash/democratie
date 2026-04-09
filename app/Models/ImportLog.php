<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ImportLog extends Model
{
    protected $fillable = [
        'command',
        'source',
        'status',
        'records_created',
        'records_updated',
        'records_skipped',
        'errors_count',
        'started_at',
        'finished_at',
        'duration_seconds',
        'exit_code',
        'options',
        'error_message',
        'error_details',
        'output_tail',
        'user_id',
        'triggered_by',
        'schedule_expression',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
        'options' => 'array',
        'error_details' => 'array',
    ];

    public const TRIGGERED_MANUAL = 'manual';

    public const TRIGGERED_SCHEDULER = 'scheduler';

    // Statuts
    public const STATUS_RUNNING = 'running';

    public const STATUS_SUCCESS = 'success';

    public const STATUS_FAILED = 'failed';

    public const STATUS_PARTIAL = 'partial';

    // Sources
    public const SOURCES = [
        'an' => ['label' => 'Assemblée nationale', 'color' => '#0055A4', 'icon' => '🔵'],
        'senat' => ['label' => 'Sénat', 'color' => '#DC143C', 'icon' => '🔴'],
        'elysee' => ['label' => 'Élysée', 'color' => '#FFD700', 'icon' => '🟡'],
        'hatvp' => ['label' => 'HATVP', 'color' => '#4CAF50', 'icon' => '🟢'],
        'wikipedia' => ['label' => 'Wikipedia', 'color' => '#9E9E9E', 'icon' => '⚪'],
    ];

    // Relations
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // Scopes
    public function scopeRecent($query, int $days = 7)
    {
        return $query->where('started_at', '>=', now()->subDays($days));
    }

    public function scopeRunning($query)
    {
        return $query->where('status', self::STATUS_RUNNING);
    }

    public function scopeFailed($query)
    {
        return $query->where('status', self::STATUS_FAILED);
    }

    public function scopeSource($query, string $source)
    {
        return $query->where('source', $source);
    }

    // Accesseurs
    public function getSourceInfoAttribute(): array
    {
        return self::SOURCES[$this->source] ?? [
            'label' => ucfirst($this->source),
            'color' => '#6B7280',
            'icon' => '📦',
        ];
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_RUNNING => '🔄 En cours',
            self::STATUS_SUCCESS => '✅ Succès',
            self::STATUS_FAILED => '❌ Échec',
            self::STATUS_PARTIAL => '⚠️ Partiel',
            default => $this->status,
        };
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_RUNNING => 'blue',
            self::STATUS_SUCCESS => 'green',
            self::STATUS_FAILED => 'red',
            self::STATUS_PARTIAL => 'yellow',
            default => 'gray',
        };
    }

    public function getDurationFormattedAttribute(): ?string
    {
        if (! $this->duration_seconds) {
            return null;
        }

        if ($this->duration_seconds < 60) {
            return $this->duration_seconds.'s';
        }

        $minutes = intdiv($this->duration_seconds, 60);
        $seconds = $this->duration_seconds % 60;

        if ($minutes < 60) {
            return "{$minutes}m {$seconds}s";
        }

        $hours = intdiv($minutes, 60);
        $minutes = $minutes % 60;

        return "{$hours}h {$minutes}m";
    }

    public function getTotalRecordsAttribute(): int
    {
        return $this->records_created + $this->records_updated;
    }

    // Méthodes statiques
    public static function start(string $command, string $source, array $options = [], ?int $userId = null): self
    {
        return self::create([
            'command' => $command,
            'source' => $source,
            'status' => self::STATUS_RUNNING,
            'started_at' => now(),
            'options' => $options,
            'user_id' => $userId,
            'triggered_by' => $userId ? self::TRIGGERED_MANUAL : self::TRIGGERED_SCHEDULER,
        ]);
    }

    public function finish(
        int $created = 0,
        int $updated = 0,
        int $skipped = 0,
        int $errors = 0,
        ?int $exitCode = null,
        ?string $outputTail = null
    ): self {
        $duration = max(0, (int) now()->diffInSeconds($this->started_at));

        $this->update([
            'status' => $errors > 0 ? self::STATUS_PARTIAL : self::STATUS_SUCCESS,
            'records_created' => $created,
            'records_updated' => $updated,
            'records_skipped' => $skipped,
            'errors_count' => $errors,
            'finished_at' => now(),
            'duration_seconds' => $duration,
            'exit_code' => $exitCode,
            'output_tail' => $outputTail,
        ]);

        return $this;
    }

    public function fail(string $message, ?array $details = null, ?int $exitCode = null, ?string $outputTail = null): self
    {
        $duration = max(0, (int) now()->diffInSeconds($this->started_at));

        $this->update([
            'status' => self::STATUS_FAILED,
            'error_message' => \Illuminate\Support\Str::limit($message, 65000),
            'error_details' => $details,
            'finished_at' => now(),
            'duration_seconds' => $duration,
            'exit_code' => $exitCode,
            'output_tail' => $outputTail,
        ]);

        return $this;
    }

    public static function shouldLogCommand(?string $command): bool
    {
        if (! $command) {
            return false;
        }

        $allowedPrefixes = [
            'import:',
            'sync:',
            'extract:',
            'enrich:',
            'calculate:',
            'dashboard:',
            'elu:',
            'scrutins:',
            'candidatures:',
            'senat:',
            'an:',
        ];

        foreach ($allowedPrefixes as $prefix) {
            if (str_starts_with($command, $prefix)) {
                return true;
            }
        }

        return in_array($command, ['sync:all'], true);
    }

    public static function detectSource(string $command): string
    {
        return match (true) {
            str_contains($command, 'senat') => 'senat',
            str_contains($command, 'an') => 'an',
            str_contains($command, 'elysee') => 'elysee',
            str_contains($command, 'hatvp') => 'hatvp',
            str_contains($command, 'wikipedia') => 'wikipedia',
            default => 'system',
        };
    }
}
