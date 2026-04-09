<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\IpBan;
use App\Services\IpBanService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class IpBanController extends Controller
{
    public function __construct(
        protected IpBanService $ipBanService
    ) {}

    public function index(Request $request): Response
    {
        $status = $request->input('status', 'active');
        $scope = $request->input('scope');
        $ip = $request->input('ip');

        $query = IpBan::query()
            ->when($scope, fn ($q) => $q->where('scope', $scope))
            ->when($ip, fn ($q) => $q->where('ip', 'like', '%'.$ip.'%'))
            ->when($status === 'active', fn ($q) => $q->active())
            ->when($status === 'expired', fn ($q) => $q->whereNotNull('unbanned_at')
                ->orWhere('expires_at', '<=', now()))
            ->orderByDesc('created_at');

        $bans = $query->paginate(20)->withQueryString();

        return Inertia::render('Admin/IpBans/Index', [
            'bans' => $bans,
            'filters' => [
                'status' => $status,
                'scope' => $scope,
                'ip' => $ip,
            ],
        ]);
    }

    public function unban(Request $request, IpBan $ipBan)
    {
        $request->validate([
            'reason' => ['nullable', 'string', 'max:255'],
        ]);

        $this->ipBanService->unban($ipBan, $request->user(), $request->input('reason'));

        return back()->with('success', "L'IP {$ipBan->ip} a été débloquée.");
    }
}
