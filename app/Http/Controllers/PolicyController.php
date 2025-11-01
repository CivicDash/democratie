<?php

namespace App\Http\Controllers;

use App\Models\PolicyVersion;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Contrôleur pour les pages légales (RGPD Art. 13)
 * 
 * Pages :
 * - Privacy Policy (Politique de confidentialité)
 * - Terms of Service (Conditions d'utilisation)
 * - Cookies Policy (Politique des cookies)
 */
class PolicyController extends Controller
{
    /**
     * Affiche la politique de confidentialité
     */
    public function privacy(): Response
    {
        $policy = PolicyVersion::getCurrentVersion(PolicyVersion::TYPE_PRIVACY);

        return Inertia::render('Legal/Privacy', [
            'policy' => $policy ? [
                'version' => $policy->version,
                'effective_at' => $policy->effective_at->format('d/m/Y'),
                'content_summary' => $policy->content_summary,
                // Note : Le contenu Markdown complet sera chargé côté Vue
            ] : null,
        ]);
    }

    /**
     * Affiche les conditions d'utilisation
     */
    public function terms(): Response
    {
        $policy = PolicyVersion::getCurrentVersion(PolicyVersion::TYPE_TERMS);

        return Inertia::render('Legal/Terms', [
            'policy' => $policy ? [
                'version' => $policy->version,
                'effective_at' => $policy->effective_at->format('d/m/Y'),
                'content_summary' => $policy->content_summary,
            ] : null,
        ]);
    }

    /**
     * Affiche la politique des cookies
     */
    public function cookies(): Response
    {
        $policy = PolicyVersion::getCurrentVersion(PolicyVersion::TYPE_COOKIES);

        return Inertia::render('Legal/Cookies', [
            'policy' => $policy ? [
                'version' => $policy->version,
                'effective_at' => $policy->effective_at->format('d/m/Y'),
                'content_summary' => $policy->content_summary,
            ] : null,
        ]);
    }
}
