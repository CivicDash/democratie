<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Report;
use App\Models\Sanction;
use App\Models\User;
use App\Services\ModerationService;
use App\Http\Requests\Moderation\StoreReportRequest;
use App\Http\Requests\Moderation\ResolveReportRequest;
use App\Http\Requests\Moderation\StoreSanctionRequest;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ModerationController extends Controller
{
    public function __construct(
        protected ModerationService $moderationService
    ) {}

    /**
     * Dashboard modération
     */
    public function dashboard(): Response
    {
        $stats = $this->moderationService->getModeratorStats();
        
        $recentReports = Report::with(['reporter', 'reportable', 'assignee'])
            ->latest()
            ->take(10)
            ->get();
        
        $topModerators = User::role('moderator')
            ->withCount(['resolvedReports'])
            ->orderByDesc('resolved_reports_count')
            ->take(5)
            ->get();

        return Inertia::render('Moderation/Dashboard', [
            'stats' => $stats,
            'recentReports' => $recentReports,
            'topModerators' => $topModerators,
        ]);
    }

    /**
     * Liste des signalements
     */
    public function reports(Request $request): Response
    {
        $query = Report::with(['reporter', 'reportable', 'assignee']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('reason')) {
            $query->where('reason', $request->reason);
        }

        $reports = $query->latest()->paginate(20);

        return Inertia::render('Moderation/Reports', [
            'reports' => $reports,
            'filters' => $request->only(['status', 'reason']),
        ]);
    }

    /**
     * Signalements prioritaires
     */
    public function priorityReports(): Response
    {
        $reports = $this->moderationService->getPriorityReports();

        return Inertia::render('Moderation/PriorityReports', [
            'reports' => $reports,
        ]);
    }

    /**
     * Détails d'un signalement
     */
    public function showReport(Report $report): Response
    {
        $report->load(['reporter', 'reportable', 'assignee']);

        return Inertia::render('Moderation/ReportDetail', [
            'report' => $report,
            'can' => [
                'assign' => auth()->user()->can('assign', $report),
                'resolve' => auth()->user()->can('resolve', $report),
            ],
        ]);
    }

    /**
     * Créer un signalement
     */
    public function store(StoreReportRequest $request)
    {
        $this->moderationService->createReport(
            $request->user(),
            $request->validated()
        );

        return back()->with('success', 'Signalement envoyé avec succès !');
    }

    /**
     * Assigner un signalement
     */
    public function assignReport(Request $request, Report $report)
    {
        $this->authorize('assign', $report);

        $this->moderationService->assignReport(
            $report,
            $request->user()
        );

        return back()->with('success', 'Signalement assigné avec succès !');
    }

    /**
     * Résoudre un signalement
     */
    public function resolveReport(ResolveReportRequest $request, Report $report)
    {
        $this->moderationService->resolveReport(
            $report,
            $request->user(),
            $request->validated()
        );

        return back()->with('success', 'Signalement résolu avec succès !');
    }

    /**
     * Rejeter un signalement
     */
    public function rejectReport(Request $request, Report $report)
    {
        $this->authorize('resolve', $report);

        $this->moderationService->rejectReport(
            $report,
            $request->user(),
            $request->input('reason')
        );

        return back()->with('success', 'Signalement rejeté avec succès.');
    }

    /**
     * Liste des sanctions
     */
    public function sanctions(Request $request): Response
    {
        $query = Sanction::with(['user', 'moderator']);

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('active')) {
            if ($request->active === 'true') {
                $query->active();
            } else {
                $query->expired();
            }
        }

        $sanctions = $query->latest()->paginate(20);

        return Inertia::render('Moderation/Sanctions', [
            'sanctions' => $sanctions,
            'filters' => $request->only(['type', 'active']),
        ]);
    }

    /**
     * Détails d'une sanction
     */
    public function showSanction(Sanction $sanction): Response
    {
        $sanction->load(['user', 'moderator']);

        return Inertia::render('Moderation/SanctionDetail', [
            'sanction' => $sanction,
            'can' => [
                'revoke' => auth()->user()->can('revoke', $sanction),
            ],
        ]);
    }

    /**
     * Révoquer une sanction
     */
    public function revokeSanction(Sanction $sanction)
    {
        $this->moderationService->revokeSanction($sanction);

        return back()->with('success', 'Sanction révoquée avec succès.');
    }

    /**
     * Statistiques de modération
     */
    public function stats(): Response
    {
        $stats = $this->moderationService->getModeratorStats();
        $topModerators = $this->moderationService->getTopModerators();

        return Inertia::render('Moderation/Stats', [
            'stats' => $stats,
            'topModerators' => $topModerators,
        ]);
    }
}

