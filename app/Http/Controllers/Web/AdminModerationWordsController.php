<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\BannedWord;
use App\Models\NiceWord;
use App\Models\ModerationLog;
use App\Services\ContentModerationService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AdminModerationWordsController extends Controller
{
    public function __construct(
        protected ContentModerationService $moderationService
    ) {}

    /**
     * Dashboard modération
     */
    public function index(): Response
    {
        $stats = $this->moderationService->getStats();

        $bannedWords = BannedWord::orderBy('category')
            ->orderBy('severity', 'desc')
            ->orderBy('word')
            ->paginate(50);

        $niceWords = NiceWord::orderBy('category')
            ->orderBy('word')
            ->paginate(50);

        $recentLogs = ModerationLog::with('user')
            ->orderByDesc('created_at')
            ->limit(20)
            ->get();

        return Inertia::render('Admin/Moderation/Words', [
            'stats' => $stats,
            'bannedWords' => $bannedWords,
            'niceWords' => $niceWords,
            'recentLogs' => $recentLogs,
            'categories' => BannedWord::CATEGORIES,
            'severities' => BannedWord::SEVERITIES,
            'niceCategories' => NiceWord::CATEGORIES,
        ]);
    }

    /**
     * Ajouter un mot banni
     */
    public function storeBanned(Request $request)
    {
        $validated = $request->validate([
            'word' => 'required|string|max:100|unique:banned_words,word',
            'category' => 'required|string|in:' . implode(',', array_keys(BannedWord::CATEGORIES)),
            'severity' => 'required|string|in:low,medium,high',
            'is_regex' => 'boolean',
            'notes' => 'nullable|string|max:500',
        ]);

        BannedWord::create([
            ...$validated,
            'is_active' => true,
            'created_by' => auth()->id(),
        ]);

        $this->moderationService->clearCache();

        return back()->with('success', "Mot banni \"{$validated['word']}\" ajouté !");
    }

    /**
     * Modifier un mot banni
     */
    public function updateBanned(Request $request, BannedWord $bannedWord)
    {
        $validated = $request->validate([
            'word' => 'required|string|max:100|unique:banned_words,word,' . $bannedWord->id,
            'category' => 'required|string|in:' . implode(',', array_keys(BannedWord::CATEGORIES)),
            'severity' => 'required|string|in:low,medium,high',
            'is_active' => 'boolean',
            'is_regex' => 'boolean',
            'notes' => 'nullable|string|max:500',
        ]);

        $bannedWord->update($validated);
        $this->moderationService->clearCache();

        return back()->with('success', 'Mot banni mis à jour !');
    }

    /**
     * Supprimer un mot banni
     */
    public function destroyBanned(BannedWord $bannedWord)
    {
        $word = $bannedWord->word;
        $bannedWord->delete();
        $this->moderationService->clearCache();

        return back()->with('success', "Mot banni \"{$word}\" supprimé !");
    }

    /**
     * Ajouter un mot gentil
     */
    public function storeNice(Request $request)
    {
        $validated = $request->validate([
            'word' => 'required|string|max:100',
            'category' => 'required|string|in:' . implode(',', array_keys(NiceWord::CATEGORIES)),
        ]);

        NiceWord::create([
            ...$validated,
            'is_active' => true,
        ]);

        $this->moderationService->clearCache();

        return back()->with('success', "Mot gentil \"{$validated['word']}\" ajouté 💖");
    }

    /**
     * Modifier un mot gentil
     */
    public function updateNice(Request $request, NiceWord $niceWord)
    {
        $validated = $request->validate([
            'word' => 'required|string|max:100',
            'category' => 'required|string|in:' . implode(',', array_keys(NiceWord::CATEGORIES)),
            'is_active' => 'boolean',
        ]);

        $niceWord->update($validated);
        $this->moderationService->clearCache();

        return back()->with('success', 'Mot gentil mis à jour !');
    }

    /**
     * Supprimer un mot gentil
     */
    public function destroyNice(NiceWord $niceWord)
    {
        $word = $niceWord->word;
        $niceWord->delete();
        $this->moderationService->clearCache();

        return back()->with('success', "Mot gentil \"{$word}\" supprimé !");
    }

    /**
     * Tester la modération
     */
    public function test(Request $request)
    {
        $validated = $request->validate([
            'content' => 'required|string|max:1000',
        ]);

        $result = $this->moderationService->moderate($validated['content']);

        return response()->json($result);
    }

    /**
     * Réinitialiser avec les mots par défaut
     */
    public function seed()
    {
        $result = $this->moderationService->seedDefaultWords();

        return back()->with('success', "Initialisé : {$result['banned']} mots bannis, {$result['nice']} mots gentils");
    }
}
