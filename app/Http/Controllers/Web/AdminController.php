<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\ImportLog;
use App\Models\User;
use App\Models\ActeurAN;
use App\Models\Senateur;
use App\Models\AmendementAN;
use App\Models\ScrutinAN;
use App\Models\EvenementLegislatif;
use App\Models\Report;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class AdminController extends Controller
{
    /**
     * Dashboard Admin principal
     */
    public function dashboard()
    {
        // Statistiques utilisateurs
        $userStats = Cache::remember('admin_user_stats', 300, function () {
            return [
                'total' => User::count(),
                'today' => User::whereDate('created_at', today())->count(),
                'week' => User::where('created_at', '>=', now()->subWeek())->count(),
                'month' => User::where('created_at', '>=', now()->subMonth())->count(),
                'admins' => User::role('admin')->count(),
                'moderators' => User::role('moderator')->count(),
            ];
        });

        // Statistiques données parlementaires
        $dataStats = Cache::remember('admin_data_stats', 300, function () {
            return [
                'deputes' => ActeurAN::count(),
                'senateurs' => Senateur::count(),
                'amendements_an' => AmendementAN::count(),
                'scrutins' => ScrutinAN::count(),
                'evenements' => EvenementLegislatif::count(),
                'evenements_an' => EvenementLegislatif::an()->count(),
                'evenements_senat' => EvenementLegislatif::senat()->count(),
                'evenements_elysee' => EvenementLegislatif::elysee()->count(),
            ];
        });

        // Derniers imports
        $recentImports = ImportLog::with('user')
            ->orderByDesc('started_at')
            ->limit(10)
            ->get()
            ->map(fn($log) => [
                'id' => $log->id,
                'command' => $log->command,
                'source' => $log->source,
                'sourceInfo' => $log->source_info,
                'status' => $log->status,
                'statusLabel' => $log->status_label,
                'statusColor' => $log->status_color,
                'recordsCreated' => $log->records_created,
                'recordsUpdated' => $log->records_updated,
                'errorsCount' => $log->errors_count,
                'duration' => $log->duration_formatted,
                'startedAt' => $log->started_at?->format('d/m/Y H:i'),
                'user' => $log->user?->name,
                'errorMessage' => $log->error_message,
            ]);

        // Imports en cours
        $runningImports = ImportLog::running()->get();

        // Alertes (imports échoués récemment)
        $failedImports = ImportLog::failed()
            ->recent(3)
            ->orderByDesc('started_at')
            ->limit(5)
            ->get();

        // Statistiques modération
        $moderationStats = [
            'pending' => Report::where('status', 'pending')->count(),
            'today' => Report::whereDate('created_at', today())->count(),
        ];

        // Commandes disponibles organisées par catégorie
        $availableCommands = [
            // === SYNC GLOBAL ===
            ['name' => 'sync:all', 'label' => 'Sync complète', 'description' => 'Toutes les données (AN, Sénat, HATVP, Wikipedia)', 'icon' => '🔄', 'category' => 'global', 'dangerous' => false],
            
            // === CALENDRIER / AGENDA ===
            ['name' => 'import:reunions-an', 'label' => 'Réunions AN', 'description' => 'Agenda de l\'Assemblée nationale', 'icon' => '🔵', 'category' => 'calendrier', 'dangerous' => false],
            ['name' => 'import:agenda-senat', 'label' => 'Agenda Sénat', 'description' => 'Flux iCal du Sénat', 'icon' => '🔴', 'category' => 'calendrier', 'dangerous' => false],
            ['name' => 'import:agenda-elysee', 'label' => 'Agenda Élysée', 'description' => 'Agenda présidentiel (scraping)', 'icon' => '🟡', 'category' => 'calendrier', 'dangerous' => false],
            ['name' => 'sync:evenements-an', 'label' => 'Sync événements', 'description' => 'Réunions AN → table unifiée', 'icon' => '📅', 'category' => 'calendrier', 'dangerous' => false],
            
            // === ASSEMBLÉE NATIONALE ===
            ['name' => 'import:acteurs-an', 'label' => 'Acteurs AN', 'description' => 'Députés depuis JSON AN', 'icon' => '👤', 'category' => 'an', 'dangerous' => false],
            ['name' => 'import:organes-an', 'label' => 'Organes AN', 'description' => 'Groupes, commissions, délégations', 'icon' => '🏛️', 'category' => 'an', 'dangerous' => false],
            ['name' => 'import:mandats-an', 'label' => 'Mandats AN', 'description' => 'Mandats des acteurs', 'icon' => '📋', 'category' => 'an', 'dangerous' => false],
            ['name' => 'import:circonscriptions-an', 'label' => 'Circonscriptions', 'description' => 'Liaison député-circonscription', 'icon' => '🗺️', 'category' => 'an', 'dangerous' => false],
            ['name' => 'import:scrutins-an', 'label' => 'Scrutins AN', 'description' => 'Votes publics AN', 'icon' => '🗳️', 'category' => 'an', 'dangerous' => false],
            ['name' => 'import:amendements-an', 'label' => 'Amendements AN', 'description' => 'Tous les amendements AN', 'icon' => '📝', 'category' => 'an', 'dangerous' => false],
            ['name' => 'import:dossiers-textes-an', 'label' => 'Dossiers/Textes AN', 'description' => 'Dossiers législatifs AN', 'icon' => '📁', 'category' => 'an', 'dangerous' => false],
            ['name' => 'import:questions-an', 'label' => 'Questions AN', 'description' => 'Questions au Gouvernement', 'icon' => '❓', 'category' => 'an', 'dangerous' => false],
            
            // === SÉNAT ===
            ['name' => 'import:senateurs', 'label' => 'Sénateurs (CSV)', 'description' => 'Import depuis CSV local', 'icon' => '👤', 'category' => 'senat', 'dangerous' => false],
            ['name' => 'import:senateurs-complet', 'label' => 'Sénateurs (API)', 'description' => 'Import complet API data.senat.fr', 'icon' => '👥', 'category' => 'senat', 'dangerous' => false],
            ['name' => 'import:dossiers-senat', 'label' => 'Dossiers Sénat', 'description' => 'Dossiers législatifs Sénat', 'icon' => '📁', 'category' => 'senat', 'dangerous' => false],
            ['name' => 'import:amendements-senat', 'label' => 'Amendements Sénat', 'description' => 'Amendements depuis data.senat.fr', 'icon' => '📝', 'category' => 'senat', 'dangerous' => false],
            ['name' => 'import:questions-senat', 'label' => 'Questions Sénat', 'description' => 'Questions au Gouvernement', 'icon' => '❓', 'category' => 'senat', 'dangerous' => false],
            ['name' => 'import:senateurs-mandats-locaux', 'label' => 'Mandats locaux', 'description' => 'Mandats locaux sénateurs', 'icon' => '🗺️', 'category' => 'senat', 'dangerous' => false],
            ['name' => 'import:senateurs-etudes', 'label' => 'Formations', 'description' => 'Études/formations sénateurs', 'icon' => '🎓', 'category' => 'senat', 'dangerous' => false],
            ['name' => 'import:senat-sql', 'label' => 'Import SQL Sénat', 'description' => 'Bases PostgreSQL du Sénat', 'icon' => '🗄️', 'category' => 'senat', 'dangerous' => true],
            
            // === ENRICHISSEMENT ===
            ['name' => 'enrich:deputes', 'label' => 'Enrichir députés', 'description' => 'API NosDéputés.fr (groupes, photos)', 'icon' => '✨', 'category' => 'enrich', 'dangerous' => false],
            ['name' => 'enrich:deputes-votes', 'label' => 'Votes députés', 'description' => 'Votes, interventions, questions', 'icon' => '🗳️', 'category' => 'enrich', 'dangerous' => false],
            ['name' => 'enrich:senateurs', 'label' => 'Enrichir sénateurs', 'description' => 'API NosSénateurs.fr', 'icon' => '✨', 'category' => 'enrich', 'dangerous' => false],
            ['name' => 'enrich:senateurs-votes', 'label' => 'Votes sénateurs', 'description' => 'Votes, interventions, questions', 'icon' => '🗳️', 'category' => 'enrich', 'dangerous' => false],
            ['name' => 'enrich:amendements', 'label' => 'Enrichir amendements', 'description' => 'APIs NosDéputés/NosSénateurs', 'icon' => '📝', 'category' => 'enrich', 'dangerous' => false],
            ['name' => 'import:deputes-wikipedia', 'label' => 'Wikipedia députés', 'description' => 'Photos et extraits Wikipedia', 'icon' => '📚', 'category' => 'enrich', 'dangerous' => false],
            ['name' => 'enrich:senateurs-wikipedia', 'label' => 'Wikipedia sénateurs', 'description' => 'Photos et extraits Wikipedia', 'icon' => '📚', 'category' => 'enrich', 'dangerous' => false],
            
            // === AUTRES ===
            ['name' => 'import:deputes', 'label' => 'Députés (CSV)', 'description' => 'Import depuis CSV local', 'icon' => '👤', 'category' => 'autres', 'dangerous' => false],
            ['name' => 'import:maires', 'label' => 'Maires (CSV)', 'description' => 'Import depuis CSV local', 'icon' => '🏘️', 'category' => 'autres', 'dangerous' => false],
            ['name' => 'import:maires-datagouv', 'label' => 'Maires (data.gouv)', 'description' => 'Enrichir avec nuance, contact, GPS', 'icon' => '🏛️', 'category' => 'autres', 'dangerous' => false],
            ['name' => 'import:organes-parlementaires', 'label' => 'Organes parlementaires', 'description' => 'Groupes, commissions, membres', 'icon' => '🏛️', 'category' => 'autres', 'dangerous' => false],
            ['name' => 'import:akoma-ntoso', 'label' => 'Textes Akoma Ntoso', 'description' => 'Format législatif Sénat', 'icon' => '📜', 'category' => 'autres', 'dangerous' => false],
            
            // === SYSTÈME ===
            ['name' => 'dashboard:calculate-stats', 'label' => 'Stats dashboard', 'description' => 'Recalculer statistiques', 'icon' => '📊', 'category' => 'system', 'dangerous' => false],
            ['name' => 'calculate:parlementaires-stats', 'label' => 'Stats parlementaires', 'description' => 'Pré-calcul stats députés/sénateurs', 'icon' => '📈', 'category' => 'system', 'dangerous' => false],
            ['name' => 'calculate:lois-stats', 'label' => 'Stats lois', 'description' => 'Pré-calcul stats des lois', 'icon' => '📜', 'category' => 'system', 'dangerous' => false],
            ['name' => 'calculate:elus-global-stats', 'label' => 'Stats élus globales', 'description' => 'Statistiques comparatives (députés/sénateurs/maires)', 'icon' => '🏛️', 'category' => 'system', 'dangerous' => false],
            ['name' => 'cache:clear', 'label' => 'Vider cache', 'description' => 'Effacer les caches', 'icon' => '🗑️', 'category' => 'system', 'dangerous' => true],
            ['name' => 'optimize:clear', 'label' => 'Optimiser', 'description' => 'Vider tous les caches', 'icon' => '⚡', 'category' => 'system', 'dangerous' => true],
        ];

        return Inertia::render('Admin/Dashboard', [
            'userStats' => $userStats,
            'dataStats' => $dataStats,
            'recentImports' => $recentImports,
            'runningImports' => $runningImports,
            'failedImports' => $failedImports,
            'moderationStats' => $moderationStats,
            'availableCommands' => $availableCommands,
        ]);
    }

    /**
     * Lancer une commande d'import (en arrière-plan)
     */
    public function runCommand(Request $request)
    {
        $request->validate([
            'command' => 'required|string',
            'options' => 'array',
        ]);

        $command = $request->input('command');
        $options = $request->input('options', []);

        // Vérifier que la commande est autorisée
        $allowedCommands = [
            // Global
            'sync:all',
            // Calendrier
            'import:reunions-an', 'import:agenda-senat', 'import:agenda-elysee', 'sync:evenements-an',
            // AN
            'import:acteurs-an', 'import:organes-an', 'import:mandats-an', 'import:circonscriptions-an', 'import:scrutins-an',
            'import:amendements-an', 'import:dossiers-textes-an', 'import:questions-an',
            // Sénat
            'import:senateurs', 'import:senateurs-complet', 'import:dossiers-senat',
            'import:amendements-senat', 'import:questions-senat', 'import:senateurs-mandats-locaux',
            'import:senateurs-etudes', 'import:senat-sql',
            // Enrichissement
            'enrich:deputes', 'enrich:deputes-votes', 'enrich:senateurs', 'enrich:senateurs-votes',
            'enrich:amendements', 'import:deputes-wikipedia', 'enrich:senateurs-wikipedia',
            // Autres
            'import:deputes', 'import:maires', 'import:maires-datagouv', 'import:organes-parlementaires', 'import:akoma-ntoso',
            // Système
            'dashboard:calculate-stats', 'calculate:parlementaires-stats', 'calculate:lois-stats', 'calculate:elus-global-stats', 'cache:clear', 'optimize:clear',
        ];

        if (!in_array($command, $allowedCommands)) {
            return back()->with('error', 'Commande non autorisée');
        }

        // Déterminer la source
        $source = ImportLog::detectSource($command);

        // Créer le log avec statut "running"
        $log = ImportLog::start($command, $source, $options, auth()->id());

        // Dispatcher le job en arrière-plan
        \App\Jobs\RunImportCommand::dispatch(
            $command,
            $source,
            $options,
            auth()->id(),
            $log->id
        );

        return back()->with('success', "Import '{$command}' lancé en arrière-plan. Rafraîchissez pour voir le statut.");
    }

    /**
     * Historique des imports
     */
    public function imports(Request $request)
    {
        $query = ImportLog::with('user')
            ->orderByDesc('started_at');

        if ($request->filled('source')) {
            $query->where('source', $request->source);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $imports = $query->paginate(20);

        return Inertia::render('Admin/Imports', [
            'imports' => $imports,
            'filters' => $request->only(['source', 'status']),
            'sources' => ImportLog::SOURCES,
        ]);
    }

    /**
     * Détail d'un import
     */
    public function showImport(ImportLog $import)
    {
        $import->load('user');

        return Inertia::render('Admin/ImportDetail', [
            'importLog' => [
                'id' => $import->id,
                'command' => $import->command,
                'source' => $import->source,
                'sourceInfo' => $import->source_info,
                'status' => $import->status,
                'statusLabel' => $import->status_label,
                'statusColor' => $import->status_color,
                'recordsCreated' => $import->records_created,
                'recordsUpdated' => $import->records_updated,
                'recordsSkipped' => $import->records_skipped,
                'errorsCount' => $import->errors_count,
                'startedAt' => $import->started_at?->format('d/m/Y H:i:s'),
                'finishedAt' => $import->finished_at?->format('d/m/Y H:i:s'),
                'duration' => $import->duration_formatted,
                'durationSeconds' => $import->duration_seconds,
                'errorMessage' => $import->error_message,
                'errorDetails' => $import->error_details,
                'outputTail' => $import->output_tail,
                'exitCode' => $import->exit_code,
                'options' => $import->options,
                'triggeredBy' => $import->triggered_by,
                'scheduleExpression' => $import->schedule_expression,
                'user' => $import->user?->name,
            ],
        ]);
    }

    /**
     * Parser une statistique depuis l'output d'une commande
     */
    private function parseOutputStat(string $output, string $pattern): int
    {
        if (preg_match('/(\d+)\s*(' . $pattern . ')/ui', $output, $matches)) {
            return (int) $matches[1];
        }
        return 0;
    }
}

