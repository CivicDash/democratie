<?php

namespace App\Services;

use App\Models\Document;
use App\Models\User;
use App\Models\Verification;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

/**
 * Service pour gérer les documents et leur vérification
 */
class DocumentService
{
    /**
     * Upload un document et l'attache à un contenu.
     * 
     * @param User $uploader
     * @param UploadedFile $file
     * @param Model $documentable Le contenu auquel attacher le document (Topic, Post, etc.)
     * @param string|null $description
     * 
     * @return Document
     */
    public function uploadDocument(
        User $uploader,
        UploadedFile $file,
        Model $documentable,
        ?string $description = null
    ): Document {
        if (!$uploader->can('upload', Document::class)) {
            throw new RuntimeException('User cannot upload documents.');
        }

        return DB::transaction(function () use ($uploader, $file, $documentable, $description) {
            // Calculer le hash SHA256 du fichier
            $fileHash = hash_file('sha256', $file->getRealPath());

            // Vérifier qu'un document avec le même hash n'existe pas déjà
            $existingDocument = Document::where('sha256_hash', $fileHash)->first();
            if ($existingDocument) {
                throw new RuntimeException('This document already exists in the system.');
            }

            // Stocker le fichier
            $path = $file->store('documents', 'public');

            // Créer le document
            $document = Document::create([
                'documentable_type' => get_class($documentable),
                'documentable_id' => $documentable->id,
                'uploader_id' => $uploader->id,
                'file_path' => $path,
                'file_name' => $file->getClientOriginalName(),
                'mime_type' => $file->getMimeType(),
                'file_size' => $file->getSize(),
                'sha256_hash' => $fileHash,
                'description' => $description,
                'is_verified' => false,
            ]);

            return $document;
        });
    }

    /**
     * Met à jour la description d'un document.
     */
    public function updateDescription(Document $document, User $user, string $description): Document
    {
        if (!$user->can('update', $document)) {
            throw new RuntimeException('User cannot update this document.');
        }

        $document->update(['description' => $description]);

        return $document->fresh();
    }

    /**
     * Supprime un document.
     */
    public function deleteDocument(Document $document, User $user): bool
    {
        if (!$user->can('delete', $document)) {
            throw new RuntimeException('User cannot delete this document.');
        }

        return DB::transaction(function () use ($document) {
            // Supprimer le fichier du stockage
            if (Storage::disk('public')->exists($document->file_path)) {
                Storage::disk('public')->delete($document->file_path);
            }

            // Supprimer les vérifications associées
            Verification::where('document_id', $document->id)->delete();

            // Supprimer le document
            return $document->delete();
        });
    }

    /**
     * Démarre une vérification de document.
     */
    public function startVerification(Document $document, User $verifier): Verification
    {
        if (!$verifier->can('verify', $document)) {
            throw new RuntimeException('User cannot verify this document.');
        }

        return Verification::create([
            'document_id' => $document->id,
            'verifier_id' => $verifier->id,
            'status' => 'pending',
        ]);
    }

    /**
     * Approuve un document (vérification réussie).
     */
    public function approveDocument(Document $document, User $verifier, ?string $notes = null): array
    {
        if (!$verifier->can('verify', $document)) {
            throw new RuntimeException('User cannot verify this document.');
        }

        return DB::transaction(function () use ($document, $verifier, $notes) {
            // Créer ou mettre à jour la vérification
            $verification = Verification::updateOrCreate(
                [
                    'document_id' => $document->id,
                    'verifier_id' => $verifier->id,
                ],
                [
                    'status' => 'verified',
                    'notes' => $notes,
                ]
            );

            // Marquer le document comme vérifié
            $document->update(['is_verified' => true]);

            return [
                'document' => $document->fresh(),
                'verification' => $verification,
            ];
        });
    }

    /**
     * Rejette un document (vérification échouée).
     */
    public function rejectDocument(Document $document, User $verifier, string $reason): Verification
    {
        if (!$verifier->can('verify', $document)) {
            throw new RuntimeException('User cannot verify this document.');
        }

        return Verification::updateOrCreate(
            [
                'document_id' => $document->id,
                'verifier_id' => $verifier->id,
            ],
            [
                'status' => 'rejected',
                'notes' => $reason,
            ]
        );
    }

    /**
     * Obtient l'historique de vérification d'un document.
     */
    public function getVerificationHistory(Document $document): \Illuminate\Support\Collection
    {
        return Verification::with('verifier')
            ->where('document_id', $document->id)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Obtient les documents en attente de vérification.
     */
    public function getPendingDocuments(?int $limit = 20): \Illuminate\Support\Collection
    {
        return Document::where('is_verified', false)
            ->whereDoesntHave('verifications', function ($query) {
                $query->where('status', 'verified');
            })
            ->with('uploader')
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Obtient les statistiques de vérification de documents.
     */
    public function getVerificationStats(?int $days = 30): array
    {
        $since = now()->subDays($days);

        $totalDocuments = Document::where('created_at', '>=', $since)->count();
        $verifiedDocuments = Document::where('is_verified', true)
            ->where('created_at', '>=', $since)
            ->count();
        $pendingDocuments = Document::where('is_verified', false)->count();

        $verifications = Verification::where('created_at', '>=', $since);

        return [
            'documents' => [
                'total' => $totalDocuments,
                'verified' => $verifiedDocuments,
                'pending' => $pendingDocuments,
                'verification_rate' => $totalDocuments > 0 ? round(($verifiedDocuments / $totalDocuments) * 100, 2) : 0,
            ],
            'verifications' => [
                'total' => $verifications->count(),
                'approved' => $verifications->where('status', 'verified')->count(),
                'rejected' => $verifications->where('status', 'rejected')->count(),
            ],
            'period' => "{$days} days",
        ];
    }

    /**
     * Obtient les vérificateurs les plus actifs.
     */
    public function getTopVerifiers(?int $days = 30, int $limit = 10): \Illuminate\Support\Collection
    {
        $since = now()->subDays($days);

        return Verification::where('created_at', '>=', $since)
            ->groupBy('verifier_id')
            ->select('verifier_id', DB::raw('COUNT(*) as verification_count'))
            ->orderBy('verification_count', 'desc')
            ->take($limit)
            ->get()
            ->map(function ($item) {
                $verifier = User::find($item->verifier_id);
                return [
                    'verifier' => $verifier ? $verifier->name : 'Unknown',
                    'verification_count' => $item->verification_count,
                    'profile_verified' => $verifier && $verifier->profile ? $verifier->profile->is_verified : false,
                ];
            });
    }

    /**
     * Vérifie l'intégrité d'un document (hash).
     */
    public function verifyIntegrity(Document $document): bool
    {
        $filePath = storage_path('app/public/' . $document->file_path);

        if (!file_exists($filePath)) {
            return false;
        }

        $currentHash = hash_file('sha256', $filePath);

        return $currentHash === $document->sha256_hash;
    }

    /**
     * Obtient l'URL de téléchargement d'un document.
     */
    public function getDownloadUrl(Document $document): string
    {
        return Storage::disk('public')->url($document->file_path);
    }
}

