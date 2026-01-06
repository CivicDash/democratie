<?php

namespace App\Http\Controllers;

use App\Models\BannedWord;
use App\Models\ContentReport;
use App\Models\ProfilePhotoModeration;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class ModerationController extends Controller
{
    /**
     * Dashboard de modération centralisé
     */
    public function dashboard(): Response
    {
        // Statistiques des photos en attente
        $photoStats = [
            'pending' => User::where('profile_photo_status', 'pending')->count(),
            'approved' => User::where('profile_photo_status', 'approved')->count(),
            'rejected' => User::where('profile_photo_status', 'rejected')->count(),
        ];

        // Statistiques des signalements (si table existe)
        $reportStats = [
            'pending' => 0,
            'resolved' => 0,
            'rejected' => 0,
        ];
        
        if (DB::getSchemaBuilder()->hasTable('content_reports')) {
            $reportStats = [
                'pending' => ContentReport::where('status', 'pending')->count(),
                'resolved' => ContentReport::where('status', 'resolved')->count(),
                'rejected' => ContentReport::where('status', 'rejected')->count(),
            ];
        }

        // Mots bannis
        $bannedWordsCount = BannedWord::count();
        
        // Utilisateurs avec vérification email en attente
        $unverifiedUsers = User::whereNull('email_verified_at')
            ->where('created_at', '>=', now()->subDays(7))
            ->count();

        // Dernières photos en attente
        $pendingPhotos = User::where('profile_photo_status', 'pending')
            ->orderBy('profile_photo_submitted_at', 'desc')
            ->take(5)
            ->get(['id', 'name', 'email', 'profile_photo_path', 'profile_photo_submitted_at']);

        // Actions récentes de modération
        $recentModerations = ProfilePhotoModeration::with(['user:id,name,email', 'moderator:id,name'])
            ->orderBy('created_at', 'desc')
            ->take(10)
            ->get();

        return Inertia::render('Moderation/Dashboard', [
            'photoStats' => $photoStats,
            'reportStats' => $reportStats,
            'bannedWordsCount' => $bannedWordsCount,
            'unverifiedUsers' => $unverifiedUsers,
            'pendingPhotos' => $pendingPhotos->map(fn($u) => [
                'id' => $u->id,
                'name' => $u->name,
                'email' => $u->email,
                'photo_url' => $u->profile_photo_url,
                'submitted_at' => $u->profile_photo_submitted_at?->diffForHumans(),
            ]),
            'recentModerations' => $recentModerations->map(fn($m) => [
                'id' => $m->id,
                'user_name' => $m->user?->name ?? 'Inconnu',
                'moderator_name' => $m->moderator?->name ?? 'Système',
                'action' => $m->action,
                'reason' => $m->reason,
                'created_at' => $m->created_at->diffForHumans(),
            ]),
        ]);
    }

    /**
     * Liste des signalements
     */
    public function reports(Request $request): Response
    {
        $status = $request->get('status', 'pending');

        $reports = [];
        if (DB::getSchemaBuilder()->hasTable('content_reports')) {
            $query = ContentReport::with(['reporter:id,name', 'moderator:id,name'])
                ->when($status !== 'all', fn($q) => $q->where('status', $status))
                ->orderBy('created_at', 'desc');

            $reports = $query->paginate(20);
        }

        return Inertia::render('Moderation/Reports', [
            'reports' => $reports,
            'currentStatus' => $status,
        ]);
    }

    /**
     * Signalements prioritaires
     */
    public function priorityReports(): Response
    {
        $reports = [];
        if (DB::getSchemaBuilder()->hasTable('content_reports')) {
            $reports = ContentReport::with(['reporter:id,name'])
                ->where('status', 'pending')
                ->where('priority', 'high')
                ->orderBy('created_at', 'asc')
                ->take(20)
                ->get();
        }

        return Inertia::render('Moderation/PriorityReports', [
            'reports' => $reports,
        ]);
    }

    /**
     * Détail d'un signalement
     */
    public function showReport($id): Response
    {
        if (!DB::getSchemaBuilder()->hasTable('content_reports')) {
            abort(404);
        }

        $report = ContentReport::with(['reporter:id,name,email', 'moderator:id,name'])
            ->findOrFail($id);

        return Inertia::render('Moderation/ReportDetail', [
            'report' => $report,
        ]);
    }

    /**
     * Assigner un signalement
     */
    public function assignReport(Request $request, $id)
    {
        if (!DB::getSchemaBuilder()->hasTable('content_reports')) {
            return back()->with('error', 'Fonctionnalité non disponible');
        }

        $report = ContentReport::findOrFail($id);
        $report->update([
            'moderator_id' => auth()->id(),
            'status' => 'in_progress',
        ]);

        return back()->with('success', 'Signalement assigné');
    }

    /**
     * Résoudre un signalement
     */
    public function resolveReport(Request $request, $id)
    {
        if (!DB::getSchemaBuilder()->hasTable('content_reports')) {
            return back()->with('error', 'Fonctionnalité non disponible');
        }

        $request->validate([
            'action' => 'required|string|in:remove,warn,ban,no_action',
            'notes' => 'nullable|string|max:1000',
        ]);

        $report = ContentReport::findOrFail($id);
        $report->update([
            'status' => 'resolved',
            'moderator_id' => auth()->id(),
            'resolution_action' => $request->action,
            'resolution_notes' => $request->notes,
            'resolved_at' => now(),
        ]);

        return back()->with('success', 'Signalement résolu');
    }

    /**
     * Rejeter un signalement
     */
    public function rejectReport(Request $request, $id)
    {
        if (!DB::getSchemaBuilder()->hasTable('content_reports')) {
            return back()->with('error', 'Fonctionnalité non disponible');
        }

        $report = ContentReport::findOrFail($id);
        $report->update([
            'status' => 'rejected',
            'moderator_id' => auth()->id(),
            'resolution_notes' => $request->notes ?? 'Signalement rejeté',
            'resolved_at' => now(),
        ]);

        return back()->with('success', 'Signalement rejeté');
    }

    /**
     * Liste des sanctions
     */
    public function sanctions(): Response
    {
        return Inertia::render('Moderation/Sanctions', [
            'sanctions' => [],
        ]);
    }

    /**
     * Détail d'une sanction
     */
    public function showSanction($id): Response
    {
        return Inertia::render('Moderation/SanctionDetail', [
            'sanction' => null,
        ]);
    }

    /**
     * Révoquer une sanction
     */
    public function revokeSanction($id)
    {
        return back()->with('error', 'Fonctionnalité non implémentée');
    }

    /**
     * Statistiques de modération
     */
    public function stats(): Response
    {
        $stats = [
            'photos_moderated_today' => ProfilePhotoModeration::whereDate('created_at', today())->count(),
            'photos_moderated_week' => ProfilePhotoModeration::whereBetween('created_at', [now()->startOfWeek(), now()])->count(),
            'photos_moderated_month' => ProfilePhotoModeration::whereMonth('created_at', now()->month)->count(),
        ];

        return Inertia::render('Moderation/Stats', [
            'stats' => $stats,
        ]);
    }
}
