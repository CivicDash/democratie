<?php

namespace App\Listeners;

use App\Models\ImportLog;
use Illuminate\Console\Events\ScheduledTaskFailed;
use Illuminate\Console\Events\ScheduledTaskFinished;
use Illuminate\Console\Events\ScheduledTaskStarting;
use Illuminate\Support\Facades\Cache;

class SchedulerTaskLogger
{
    private const CACHE_PREFIX = 'import_log_task:';

    public function starting(ScheduledTaskStarting $event): void
    {
        $command = $this->extractCommandName($event->task->command ?? null);
        if (!ImportLog::shouldLogCommand($command)) {
            return;
        }

        $source = ImportLog::detectSource($command);
        $log = ImportLog::start($command, $source, [], null);
        $log->update([
            'triggered_by' => ImportLog::TRIGGERED_SCHEDULER,
            'schedule_expression' => $event->task->getExpression(),
        ]);

        Cache::put($this->cacheKey($event->task), $log->id, now()->addHours(6));
    }

    public function finished(ScheduledTaskFinished $event): void
    {
        $command = $this->extractCommandName($event->task->command ?? null);
        if (!ImportLog::shouldLogCommand($command)) {
            return;
        }

        $log = $this->findLog($event->task);
        if (!$log) {
            return;
        }

        $exitCode = $event->task->exitCode ?? 0;

        if ($exitCode === 0) {
            $log->finish(0, 0, 0, 0, $exitCode);
        } else {
            $log->fail("Exit code: {$exitCode}", null, $exitCode);
        }

        if ($event->runtime) {
            $log->update(['duration_seconds' => (int) ceil($event->runtime)]);
        }
    }

    public function failed(ScheduledTaskFailed $event): void
    {
        $command = $this->extractCommandName($event->task->command ?? null);
        if (!ImportLog::shouldLogCommand($command)) {
            return;
        }

        $log = $this->findLog($event->task);
        if (!$log) {
            return;
        }

        $exitCode = $event->task->exitCode ?? 1;
        $log->fail($event->exception->getMessage(), [
            'exception' => get_class($event->exception),
            'file' => $event->exception->getFile() . ':' . $event->exception->getLine(),
        ], $exitCode);
    }

    private function extractCommandName(?string $command): ?string
    {
        if (!$command) {
            return null;
        }

        if (preg_match('/artisan[\'"\s]+([^\s\'\"]+)/', $command, $matches)) {
            return $matches[1];
        }

        $parts = preg_split('/\s+/', trim($command));
        return $parts[0] ?? null;
    }

    private function cacheKey($task): string
    {
        return self::CACHE_PREFIX . sha1($task->mutexName());
    }

    private function findLog($task): ?ImportLog
    {
        $logId = Cache::get($this->cacheKey($task));
        if (!$logId) {
            return null;
        }

        return ImportLog::find($logId);
    }
}
