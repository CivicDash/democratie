<?php

namespace App\Policies;

use App\Models\Document;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class DocumentPolicy
{
    use HandlesAuthorization;

    /**
     * Determine if the user can view any documents.
     */
    public function viewAny(?User $user): bool
    {
        // Tous peuvent voir les documents
        return true;
    }

    /**
     * Determine if the user can view the document.
     */
    public function view(?User $user, Document $document): bool
    {
        // Tous peuvent voir les documents publics
        return true;
    }

    /**
     * Determine if the user can upload documents.
     */
    public function upload(User $user): bool
    {
        // Users avec permission peuvent uploader
        return $user->hasPermissionTo('documents.upload') &&
               !$user->isMuted() &&
               !$user->isBanned();
    }

    /**
     * Determine if the user can update the document.
     */
    public function update(User $user, Document $document): bool
    {
        // L'uploader peut mettre à jour (description, etc.)
        if ($document->uploader_id === $user->id) {
            return !$user->isMuted() && !$user->isBanned();
        }

        // Admins peuvent mettre à jour
        return $user->hasRole('admin');
    }

    /**
     * Determine if the user can delete the document.
     */
    public function delete(User $user, Document $document): bool
    {
        // L'uploader peut supprimer si pas encore vérifié
        if ($document->uploader_id === $user->id && !$document->is_verified) {
            return true;
        }

        // Admins peuvent supprimer
        return $user->hasRole('admin');
    }

    /**
     * Determine if the user can verify the document.
     */
    public function verify(User $user, Document $document): bool
    {
        // User doit avoir la permission de vérifier
        if (!$user->hasPermissionTo('documents.verify')) {
            return false;
        }

        // User doit être vérifié lui-même (profil verified)
        if (!$user->profile || !$user->profile->is_verified) {
            return false;
        }

        // Ne peut pas vérifier son propre document
        if ($document->uploader_id === $user->id) {
            return false;
        }

        // Document ne doit pas déjà être vérifié
        return !$document->is_verified;
    }

    /**
     * Determine if the user can download the document.
     */
    public function download(?User $user, Document $document): bool
    {
        // Tous peuvent télécharger
        return true;
    }

    /**
     * Determine if the user can view verification history.
     */
    public function viewVerifications(User $user, Document $document): bool
    {
        // Tous peuvent voir les vérifications
        return true;
    }

    /**
     * Determine if the user can attach document to content.
     */
    public function attach(User $user): bool
    {
        // Mêmes règles que upload
        return $this->upload($user);
    }
}

