<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Moderation\ResolveReportRequest;
use App\Http\Requests\Moderation\StoreReportRequest;
use App\Http\Requests\Moderation\StoreSanctionRequest;
use App\Models\Report;
use App\Models\Sanction;
use App\Models\User;
use App\Services\ModerationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ModerationController extends Controller
{
    public function __construct(
        protected ModerationService $moderationService
    ) {}

    /**
     * Get all reports (moderators only).
     */
    public function reports(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Report::class);

        $query = Report::with(['reporter', 'moderator', 'reportable']);

        // Filtrer par statut
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Trier
        $sortBy = $request->input('sort_by', 'created_at');
        $sortOrder = $request->input('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        $reports = $query->paginate($request->input('per_page', 20));

        return response()->json($reports);
    }

    /**
     * Get priority reports (multiple reports on same content).
     */
    public function priorityReports(): JsonResponse
    {
        $this->authorize('viewAny', Report::class);

        $priorityReports = $this->moderationService->getPriorityReports();

        return response()->json($priorityReports);
    }

    /**
     * Create a report.
     */
    public function storeReport(StoreReportRequest $request): JsonResponse
    {
        try {
            $reportableType = $request->reportable_type;
            $reportable = $reportableType::findOrFail($request->reportable_id);

            $report = $this->moderationService->createReport(
                $request->user(),
                $reportable,
                $request->reason
            );

            return response()->json([
                'message' => 'Signalement créé avec succès.',
                'report' => $report->load('reporter'),
            ], 201);
        } catch (\RuntimeException $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Assign a report to current moderator.
     */
    public function assignReport(Report $report): JsonResponse
    {
        try {
            $report = $this->moderationService->assignReport(
                $report,
                auth()->user()
            );

            return response()->json([
                'message' => 'Signalement assigné avec succès.',
                'report' => $report,
            ]);
        } catch (\RuntimeException $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 403);
        }
    }

    /**
     * Resolve a report.
     */
    public function resolveReport(ResolveReportRequest $request, Report $report): JsonResponse
    {
        try {
            $report = $this->moderationService->resolveReport(
                $report,
                $request->user(),
                $request->notes,
                $request->apply_action ?? false
            );

            return response()->json([
                'message' => 'Signalement résolu avec succès.',
                'report' => $report,
            ]);
        } catch (\RuntimeException $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 403);
        }
    }

    /**
     * Reject a report.
     */
    public function rejectReport(Request $request, Report $report): JsonResponse
    {
        try {
            $report = $this->moderationService->rejectReport(
                $report,
                $request->user(),
                $request->input('notes')
            );

            return response()->json([
                'message' => 'Signalement rejeté avec succès.',
                'report' => $report,
            ]);
        } catch (\RuntimeException $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 403);
        }
    }

    /**
     * Get all sanctions for a user.
     */
    public function userSanctions(User $user): JsonResponse
    {
        $this->authorize('viewHistory', [Sanction::class, $user]);

        $sanctions = $this->moderationService->getSanctionHistory($user);

        return response()->json($sanctions);
    }

    /**
     * Create a sanction.
     */
    public function storeSanction(StoreSanctionRequest $request, User $user): JsonResponse
    {
        try {
            $expiresAt = $request->duration_days 
                ? now()->addDays($request->duration_days) 
                : null;

            $sanction = $this->moderationService->createSanction(
                $user,
                $request->user(),
                $request->type,
                $request->reason,
                $expiresAt
            );

            return response()->json([
                'message' => 'Sanction créée avec succès.',
                'sanction' => $sanction->load('moderator'),
            ], 201);
        } catch (\RuntimeException $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 403);
        }
    }

    /**
     * Revoke a sanction.
     */
    public function revokeSanction(Sanction $sanction): JsonResponse
    {
        try {
            $sanction = $this->moderationService->revokeSanction(
                $sanction,
                auth()->user()
            );

            return response()->json([
                'message' => 'Sanction révoquée avec succès.',
                'sanction' => $sanction,
            ]);
        } catch (\RuntimeException $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 403);
        }
    }

    /**
     * Get moderation statistics.
     */
    public function stats(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Report::class);

        $days = $request->input('days', 30);
        $stats = $this->moderationService->getModerationStats($days);

        return response()->json($stats);
    }

    /**
     * Get top moderators.
     */
    public function topModerators(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Report::class);

        $days = $request->input('days', 30);
        $limit = $request->input('limit', 10);
        
        $moderators = $this->moderationService->getTopModerators($days, $limit);

        return response()->json($moderators);
    }
}

