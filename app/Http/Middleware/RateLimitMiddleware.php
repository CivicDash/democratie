<?php

namespace App\Http\Middleware;

use App\Services\RateLimitService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RateLimitMiddleware
{
    public function __construct(
        protected RateLimitService $rateLimitService
    ) {}

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, string $action = 'api'): Response
    {
        try {
            match ($action) {
                'login' => $this->rateLimitService->checkLoginLimit($request),
                'register' => $this->rateLimitService->checkRegisterLimit($request),
                'post' => $this->rateLimitService->checkPostLimit($request),
                'report' => $this->rateLimitService->checkReportLimit($request),
                'document' => $this->rateLimitService->checkDocumentUploadLimit($request),
                'budget' => $this->rateLimitService->checkBudgetAllocationLimit($request),
                default => null, // Rate limit général géré par throttle:api
            };

            return $next($request);
        } catch (\Illuminate\Http\Exceptions\ThrottleRequestsException $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'retry_after' => $this->rateLimitService->availableIn(
                    $this->getKeyForAction($request, $action)
                ),
            ], 429);
        }
    }

    /**
     * Obtenir la clé pour l'action
     */
    protected function getKeyForAction(Request $request, string $action): string
    {
        return match ($action) {
            'login' => $this->rateLimitService->loginKey($request),
            'register' => $this->rateLimitService->registerKey($request),
            'post' => $this->rateLimitService->postKey($request),
            'report' => $this->rateLimitService->reportKey($request),
            'document' => $this->rateLimitService->documentUploadKey($request),
            'budget' => $this->rateLimitService->budgetAllocationKey($request),
            default => $this->rateLimitService->apiKey($request),
        };
    }
}
