<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Document\UploadDocumentRequest;
use App\Http\Requests\Document\VerifyDocumentRequest;
use App\Models\Document;
use App\Services\DocumentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DocumentController extends Controller
{
    public function __construct(
        protected DocumentService $documentService
    ) {}

    /**
     * Get all documents.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Document::with(['uploader', 'documentable']);

        // Filtrer par vérification
        if ($request->has('verified')) {
            $query->where('is_verified', $request->boolean('verified'));
        }

        // Filtrer par type de contenu
        if ($request->has('documentable_type')) {
            $query->where('documentable_type', $request->documentable_type);
        }

        // Trier
        $sortBy = $request->input('sort_by', 'created_at');
        $sortOrder = $request->input('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        $documents = $query->paginate($request->input('per_page', 20));

        return response()->json($documents);
    }

    /**
     * Display a single document.
     */
    public function show(Document $document): JsonResponse
    {
        $this->authorize('view', $document);

        $document->load(['uploader', 'documentable', 'verifications.verifier']);

        // Vérifier l'intégrité
        $integrityValid = $this->documentService->verifyIntegrity($document);

        return response()->json([
            'document' => $document,
            'integrity_valid' => $integrityValid,
            'download_url' => $this->documentService->getDownloadUrl($document),
        ]);
    }

    /**
     * Upload a document.
     */
    public function store(UploadDocumentRequest $request): JsonResponse
    {
        try {
            $documentableType = $request->documentable_type;
            $documentable = $documentableType::findOrFail($request->documentable_id);

            $document = $this->documentService->uploadDocument(
                $request->user(),
                $request->file('file'),
                $documentable,
                $request->description
            );

            return response()->json([
                'message' => 'Document uploadé avec succès.',
                'document' => $document->load('uploader'),
            ], 201);
        } catch (\RuntimeException $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Update document description.
     */
    public function update(Request $request, Document $document): JsonResponse
    {
        $request->validate([
            'description' => ['required', 'string', 'max:500'],
        ]);

        try {
            $document = $this->documentService->updateDescription(
                $document,
                $request->user(),
                $request->description
            );

            return response()->json([
                'message' => 'Description mise à jour avec succès.',
                'document' => $document,
            ]);
        } catch (\RuntimeException $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 403);
        }
    }

    /**
     * Delete a document.
     */
    public function destroy(Document $document): JsonResponse
    {
        try {
            $this->documentService->deleteDocument($document, auth()->user());

            return response()->json([
                'message' => 'Document supprimé avec succès.',
            ]);
        } catch (\RuntimeException $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 403);
        }
    }

    /**
     * Verify a document (journalist/ong).
     */
    public function verify(VerifyDocumentRequest $request, Document $document): JsonResponse
    {
        try {
            if ($request->status === 'verified') {
                $result = $this->documentService->approveDocument(
                    $document,
                    $request->user(),
                    $request->notes
                );

                return response()->json([
                    'message' => 'Document vérifié avec succès.',
                    'document' => $result['document'],
                    'verification' => $result['verification'],
                ]);
            } else {
                $verification = $this->documentService->rejectDocument(
                    $document,
                    $request->user(),
                    $request->notes
                );

                return response()->json([
                    'message' => 'Document rejeté.',
                    'verification' => $verification,
                ]);
            }
        } catch (\RuntimeException $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 403);
        }
    }

    /**
     * Get verification history for a document.
     */
    public function verifications(Document $document): JsonResponse
    {
        $this->authorize('viewVerifications', $document);

        $history = $this->documentService->getVerificationHistory($document);

        return response()->json($history);
    }

    /**
     * Get pending documents (verifiers only).
     */
    public function pending(Request $request): JsonResponse
    {
        $limit = $request->input('limit', 20);
        $pending = $this->documentService->getPendingDocuments($limit);

        return response()->json($pending);
    }

    /**
     * Get verification statistics.
     */
    public function stats(Request $request): JsonResponse
    {
        $days = $request->input('days', 30);
        $stats = $this->documentService->getVerificationStats($days);

        return response()->json($stats);
    }

    /**
     * Get top verifiers.
     */
    public function topVerifiers(Request $request): JsonResponse
    {
        $days = $request->input('days', 30);
        $limit = $request->input('limit', 10);
        
        $verifiers = $this->documentService->getTopVerifiers($days, $limit);

        return response()->json($verifiers);
    }

    /**
     * Download a document.
     */
    public function download(Document $document)
    {
        $this->authorize('download', $document);

        $path = storage_path('app/public/' . $document->file_path);

        if (!file_exists($path)) {
            return response()->json([
                'message' => 'Fichier introuvable.',
            ], 404);
        }

        return response()->download($path, $document->file_name);
    }
}

