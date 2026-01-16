<?php

namespace App\Services;

use App\Models\IpBan;
use App\Models\User;
use Illuminate\Cache\RateLimiter;

class IpBanService
{
    public function __construct(
        protected RateLimiter $limiter
    ) {}

    public function logBan(
        string $ip,
        string $scope,
        string $banKey,
        ?string $abuseKey,
        int $abuseCount,
        int $banSeconds,
        string $reason,
        array $metadata = []
    ): IpBan {
        $existing = IpBan::active()
            ->where('ban_key', $banKey)
            ->latest()
            ->first();

        if ($existing) {
            return $existing;
        }

        return IpBan::create([
            'ip' => $ip,
            'scope' => $scope,
            'ban_key' => $banKey,
            'abuse_key' => $abuseKey,
            'abuse_count' => $abuseCount,
            'ban_seconds' => $banSeconds,
            'reason' => $reason,
            'expires_at' => now()->addSeconds($banSeconds),
            'metadata' => $metadata ?: null,
        ]);
    }

    public function unban(IpBan $ban, ?User $user = null, ?string $reason = null): void
    {
        $this->limiter->clear($ban->ban_key);

        if ($ban->abuse_key) {
            $this->limiter->clear($ban->abuse_key);
        }

        $ban->update([
            'unbanned_at' => now(),
            'unbanned_by' => $user?->id,
            'unban_reason' => $reason,
        ]);
    }
}
