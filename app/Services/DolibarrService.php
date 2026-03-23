<?php

namespace App\Services;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class DolibarrService
{
    protected string $apiUrl = '';
    protected string $apiKey = '';
    protected int $timeout = 5;

    protected const MEMBER_TYPE_MAP = [
        '1' => 'adherent',
        '2' => 'bienfaiteur',
        '3' => 'donateur',
        '4' => 'fondateur',     // Conseil d'administration
        '5' => 'fondateur',
    ];

    protected const STATUS_VALIDATED = 1;
    protected const MIN_AGE = 18;

    public function __construct()
    {
        $this->apiUrl = config('services.dolibarr.api_url') ?? '';
        $this->apiKey = config('services.dolibarr.api_key') ?? '';
        $this->timeout = (int) (config('services.dolibarr.timeout') ?? 5);
    }

    public function isConfigured(): bool
    {
        return !empty($this->apiUrl) && !empty($this->apiKey);
    }

    /**
     * Search for a member by email in Dolibarr.
     * Returns the member data array or null if not found / error.
     */
    public function checkMemberByEmail(string $email): ?array
    {
        if (!$this->isConfigured()) {
            return null;
        }

        try {
            $response = Http::timeout($this->timeout)
                ->withHeaders(['DOLAPIKEY' => $this->apiKey])
                ->get("{$this->apiUrl}/members", [
                    'sqlfilters' => "(t.email:=:'{$email}')",
                ]);

            if (!$response->successful()) {
                Log::debug('DolibarrService: member lookup failed', [
                    'email' => $email,
                    'status' => $response->status(),
                ]);
                return null;
            }

            $members = $response->json();

            if (empty($members) || !is_array($members)) {
                return null;
            }

            return $members[0];
        } catch (\Exception $e) {
            Log::warning('DolibarrService: API request failed', [
                'email' => $email,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Validate a registration attempt against Dolibarr.
     *
     * Returns ['ok' => true, 'member' => [...]] on success,
     * or ['ok' => false, 'error' => 'field', 'message' => '...'] on failure.
     */
    public function validateRegistration(string $email, string $dateOfBirth): array
    {
        if (!$this->isConfigured()) {
            return [
                'ok' => false,
                'error' => 'email',
                'message' => 'La vérification des membres est temporairement indisponible. Veuillez réessayer plus tard.',
            ];
        }

        $member = $this->checkMemberByEmail($email);

        if (!$member) {
            return [
                'ok' => false,
                'error' => 'email',
                'message' => 'Cette adresse email ne correspond à aucun membre de l\'association Civis-Consilium. Veuillez d\'abord adhérer sur civis-consilium.eu.',
            ];
        }

        $statut = (int) ($member['statut'] ?? $member['status'] ?? -1);
        if ($statut !== self::STATUS_VALIDATED) {
            $statusLabels = [
                -1 => 'en brouillon',
                0 => 'résiliée',
                -2 => 'exclue',
            ];
            $label = $statusLabels[$statut] ?? 'inactive';
            return [
                'ok' => false,
                'error' => 'email',
                'message' => "Votre adhésion est actuellement {$label}. Veuillez contacter l'association pour régulariser votre situation.",
            ];
        }

        $dob = Carbon::parse($dateOfBirth);

        if ($dob->age < self::MIN_AGE) {
            return [
                'ok' => false,
                'error' => 'date_of_birth',
                'message' => 'Vous devez avoir au moins ' . self::MIN_AGE . ' ans pour vous inscrire.',
            ];
        }

        $dolibarrBirth = $member['birth'] ?? null;
        if (!empty($dolibarrBirth)) {
            try {
                $dolibarrDob = Carbon::parse($dolibarrBirth);
                if (!$dob->isSameDay($dolibarrDob)) {
                    return [
                        'ok' => false,
                        'error' => 'date_of_birth',
                        'message' => 'La date de naissance ne correspond pas à celle enregistrée dans votre dossier d\'adhésion.',
                    ];
                }
            } catch (\Exception $e) {
                // Dolibarr birth unparseable, skip cross-check
            }
        }

        return ['ok' => true, 'member' => $member];
    }

    /**
     * Sync user fields from a Dolibarr member record.
     * Returns true if the user was updated as an active member.
     */
    public function syncMemberToUser(User $user, ?array $member = null): bool
    {
        $member ??= $this->checkMemberByEmail($user->email);

        if (!$member) {
            return false;
        }

        $statut = (int) ($member['statut'] ?? $member['status'] ?? -1);
        $isActive = $statut === self::STATUS_VALIDATED;

        $updates = [
            'association_member_id' => $member['id'] ?? null,
            'member_number' => $member['ref'] ?? null,
            'member_type' => self::MEMBER_TYPE_MAP[$member['typeid'] ?? ''] ?? 'adherent',
            'is_association_member' => $isActive,
        ];

        if (!empty($member['datec'])) {
            $updates['member_since'] = Carbon::createFromTimestamp((int) $member['datec'])->toDateString();
        }

        if (!empty($member['datefin'])) {
            $updates['member_until'] = Carbon::createFromTimestamp((int) $member['datefin'])->toDateString();
        } else {
            $updates['member_until'] = null;
        }

        if ($isActive && !$user->association_member_since) {
            $updates['association_member_since'] = now();
        }

        $user->update($updates);

        if ($isActive && !$user->hasRole('association_member')) {
            try {
                $user->assignRole('association_member');
            } catch (\Exception $e) {
                // Role may not be seeded yet
            }
        }

        if (!$isActive && $user->hasRole('association_member')) {
            try {
                $user->removeRole('association_member');
            } catch (\Exception $e) {
                // ignore
            }
        }

        return $isActive;
    }
}
