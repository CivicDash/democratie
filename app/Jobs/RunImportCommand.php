<?php

namespace App\Jobs;

use App\Models\ImportLog;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Artisan;

class RunImportCommand implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 3600; // 1 heure max
    public int $tries = 1; // Pas de retry automatique

    public function __construct(
        public string $command,
        public string $source,
        public array $options = [],
        public ?int $userId = null,
        public ?int $importLogId = null
    ) {}

    public function handle(): void
    {
        // Récupérer ou créer le log
        $log = $this->importLogId 
            ? ImportLog::find($this->importLogId)
            : ImportLog::start($this->command, $this->source, $this->options, $this->userId);

        if (!$log) {
            return;
        }

        try {
            // Exécuter la commande
            $exitCode = Artisan::call($this->command, $this->options);
            $output = Artisan::output();
            $outputTail = $this->truncateOutput($output);

            // Parser le résultat
            $created = $this->parseOutputStat($output, 'créé|created|Créés|importé|Importés');
            $updated = $this->parseOutputStat($output, 'mis à jour|updated|Mis à jour|modifié');
            $skipped = $this->parseOutputStat($output, 'ignoré|skipped|Ignorés');
            $errors = $this->parseOutputStat($output, 'erreur|error|Erreurs|échoué');

            if ($exitCode === 0) {
                $log->finish($created, $updated, $skipped, $errors, $exitCode, $outputTail);
            } else {
                $log->fail("Exit code: {$exitCode}", ['output' => $outputTail], $exitCode, $outputTail);
            }

        } catch (\Exception $e) {
            $log->fail($e->getMessage(), [
                'exception' => get_class($e),
                'file' => $e->getFile() . ':' . $e->getLine(),
            ], 1);
        }
    }

    /**
     * Parser une statistique depuis l'output
     */
    private function parseOutputStat(string $output, string $pattern): int
    {
        if (preg_match('/(\d+)\s*(' . $pattern . ')/ui', $output, $matches)) {
            return (int) $matches[1];
        }
        // Chercher aussi le pattern inverse "Créés: 123"
        if (preg_match('/(' . $pattern . ')[\s:]+(\d+)/ui', $output, $matches)) {
            return (int) $matches[2];
        }
        return 0;
    }

    private function truncateOutput(string $output, int $maxLength = 5000): string
    {
        if (strlen($output) <= $maxLength) {
            return $output;
        }

        return substr($output, -1 * $maxLength);
    }

    /**
     * Handle failure
     */
    public function failed(\Throwable $exception): void
    {
        if ($this->importLogId) {
            $log = ImportLog::find($this->importLogId);
            if ($log) {
                $log->fail($exception->getMessage(), null, 1);
            }
        }
    }
}

