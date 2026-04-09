<?php

namespace App\Console\Commands;

use App\Models\QuestionAN;
use App\Models\VideoChapter;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class MatchVideoChaptersAN extends Command
{
    protected $signature = 'match:video-chapters-an
        {--type=all : Type to match (all, qag, amendements)}
        {--dry-run : Show matches without writing}';

    protected $description = 'Match video chapters with QAG questions and amendments by speaker/date/number';

    private int $qagMatched = 0;

    private int $qagUnmatched = 0;

    private int $adtMatched = 0;

    private int $adtUnmatched = 0;

    public function handle(): int
    {
        $type = $this->option('type');
        $dryRun = $this->option('dry-run');

        $this->info('=== Match Video Chapters with AN Data ===');

        if (in_array($type, ['all', 'qag'])) {
            $this->matchQag($dryRun);
        }

        if (in_array($type, ['all', 'amendements'])) {
            $this->matchAmendements($dryRun);
        }

        $this->newLine();
        $this->table(['Metric', 'Count'], [
            ['QAG matched', $this->qagMatched],
            ['QAG unmatched', $this->qagUnmatched],
            ['Amendment ranges matched', $this->adtMatched],
            ['Amendment ranges unmatched', $this->adtUnmatched],
        ]);

        return self::SUCCESS;
    }

    private function matchQag(bool $dryRun): void
    {
        $this->info('Matching QAG chapters...');

        $questionChapters = VideoChapter::where('chapter_type_key', 4)
            ->whereNotNull('speaker_an_uid')
            ->whereNull('question_uid')
            ->get();

        $this->info("  {$questionChapters->count()} unmatched Question chapters");

        $chaptersByVideo = $questionChapters->groupBy('video_id');

        foreach ($chaptersByVideo as $videoId => $chapters) {
            $reunion = $chapters->first()->reunion;
            $date = $reunion?->date_debut?->toDateString();

            if (! $date) {
                $date = $this->guessDateFromVideoChapter($chapters->first());
            }

            if (! $date) {
                $this->qagUnmatched += $chapters->count();

                continue;
            }

            $questionsOnDate = QuestionAN::where('type', 'QG')
                ->whereDate('date_question', $date)
                ->get()
                ->keyBy('acteur_ref');

            foreach ($chapters as $chapter) {
                $acteurRef = $chapter->speaker_an_uid;
                $question = $questionsOnDate->get($acteurRef);

                if ($question) {
                    if ($dryRun) {
                        $this->line("  [DRY] {$chapter->label} -> {$question->uid}");
                    } else {
                        $chapter->update(['question_uid' => $question->uid]);
                    }
                    $this->qagMatched++;
                } else {
                    $this->qagUnmatched++;
                }
            }
        }
    }

    private function matchAmendements(bool $dryRun): void
    {
        $this->info('Matching amendment chapters...');

        $adtChapters = VideoChapter::where('chapter_type_key', 25)
            ->whereNotNull('reunion_uid')
            ->get();

        $this->info("  {$adtChapters->count()} Adt chapters to analyze");

        foreach ($adtChapters as $chapter) {
            $numbers = $this->parseAmendmentNumbers($chapter->label);

            if (empty($numbers)) {
                $this->adtUnmatched++;

                continue;
            }

            $matchCount = DB::table('amendements_an')
                ->whereIn('numero_long', $numbers)
                ->count();

            if ($matchCount > 0) {
                $this->adtMatched++;
                if ($dryRun) {
                    $this->line("  [DRY] {$chapter->label}: {$matchCount} amendements matched");
                }
            } else {
                $this->adtUnmatched++;
            }
        }
    }

    private function parseAmendmentNumbers(string $label): array
    {
        $numbers = [];

        if (preg_match_all('/\b(\d{1,5})\b/', $label, $m)) {
            foreach ($m[1] as $num) {
                $numbers[] = $num;
            }
        }

        return array_unique($numbers);
    }

    private function guessDateFromVideoChapter(VideoChapter $chapter): ?string
    {
        $reunion = DB::table('reunions_an')
            ->where('uid', $chapter->reunion_uid)
            ->first();

        return $reunion?->date_debut
            ? substr($reunion->date_debut, 0, 10)
            : null;
    }
}
