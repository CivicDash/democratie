<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Post;
use App\Models\Report;
use App\Models\Topic;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ReportController extends Controller
{
    /**
     * Signaler un contenu
     */
    public function store(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!$user->can('create', Report::class)) {
            return response()->json([
                'success' => false,
                'message' => 'Vous n\'avez pas la permission de signaler du contenu.',
            ], 403);
        }

        // Vérifier que l'utilisateur peut signaler (pas démo, pas banni)
        if ($user->isReadOnly()) {
            return response()->json([
                'success' => false,
                'message' => 'Les comptes de démonstration ne peuvent pas signaler de contenu.',
            ], 403);
        }

        $validated = $request->validate([
            'type' => 'required|string|in:topic,post,comment',
            'id' => 'required|integer',
            'reason' => 'required|string|in:' . implode(',', array_keys(Report::REASONS)),
            'description' => 'nullable|string|max:1000',
        ]);

        // Résoudre le contenu signalé
        $reportable = $this->resolveReportable($validated['type'], $validated['id']);

        if (!$reportable) {
            return response()->json([
                'success' => false,
                'message' => 'Contenu introuvable.',
            ], 404);
        }

        // Vérifier si déjà signalé par cet utilisateur
        $existing = Report::where([
            'reporter_id' => $user->id,
            'reportable_type' => get_class($reportable),
            'reportable_id' => $reportable->id,
        ])->whereIn('status', ['pending', 'reviewing'])->first();

        if ($existing) {
            return response()->json([
                'success' => false,
                'message' => 'Vous avez déjà signalé ce contenu.',
                'report_id' => $existing->id,
            ], 409);
        }

        // Créer le signalement
        $report = Report::create([
            'reporter_id' => $user->id,
            'reportable_type' => get_class($reportable),
            'reportable_id' => $reportable->id,
            'reason' => $validated['reason'],
            'description' => $validated['description'],
            'status' => 'pending',
        ]);

        Log::info('Nouveau signalement', [
            'report_id' => $report->id,
            'reporter' => $user->id,
            'type' => $validated['type'],
            'reason' => $validated['reason'],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Signalement enregistré. Merci pour votre contribution à la modération.',
            'report_id' => $report->id,
        ]);
    }

    /**
     * Liste des raisons de signalement
     */
    public function reasons(): JsonResponse
    {
        $reasons = collect(Report::REASONS)->map(fn($info, $key) => [
            'key' => $key,
            'label' => $info['label'],
            'icon' => $info['icon'],
            'severity' => $info['severity'],
        ])->values();

        return response()->json([
            'success' => true,
            'reasons' => $reasons,
        ]);
    }

    /**
     * Mes signalements
     */
    public function myReports(Request $request): JsonResponse
    {
        $user = $request->user();

        $reports = Report::where('reporter_id', $user->id)
            ->with('reportable')
            ->orderByDesc('created_at')
            ->limit(50)
            ->get()
            ->map(fn($report) => [
                'id' => $report->id,
                'reason' => $report->reason,
                'reason_info' => $report->reason_info,
                'status' => $report->status,
                'status_info' => $report->status_info,
                'content_type' => class_basename($report->reportable_type),
                'created_at' => $report->created_at->diffForHumans(),
                'resolved_at' => $report->resolved_at?->diffForHumans(),
            ]);

        return response()->json([
            'success' => true,
            'reports' => $reports,
        ]);
    }

    /**
     * Résout le modèle à partir du type et de l'ID
     */
    protected function resolveReportable(string $type, int $id)
    {
        return match ($type) {
            'topic' => Topic::find($id),
            'post', 'comment' => Post::find($id),
            default => null,
        };
    }
}
