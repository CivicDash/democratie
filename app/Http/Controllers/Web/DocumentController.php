<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Document;
use App\Services\DocumentService;
use App\Http\Requests\Document\UploadDocumentRequest;
use App\Http\Requests\Document\VerifyDocumentRequest;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Support\Facades\Storage;

class DocumentController extends Controller
{
    public function __construct(
        protected DocumentService $documentService
    ) {}

    /**
     * Liste des documents
     */
    public function index(Request $request): Response
    {
        $query = Document::with(['uploader'])
            ->withCount('verifications');

        // Filtres
        if ($request->filled('type')) {
            $query->where('document_type', $request->type);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $documents = $query->latest()->paginate(15);

        $stats = [
            'total' => Document::count(),
            'verified' => Document::where('status', 'verified')->count(),
            'pending' => Document::where('status', 'pending')->count(),
            'rejected' => Document::where('status', 'rejected')->count(),
        ];

        return Inertia::render('Documents/Index', [
            'documents' => $documents,
            'filters' => $request->only(['type', 'status']),
            'stats' => $stats,
        ]);
    }

    /**
     * Détails d'un document
     */
    public function show(Document $document): Response
    {
        $document->load(['uploader', 'verifications.verifier']);

        return Inertia::render('Documents/Show', [
            'document' => $document,
            'can' => [
                'update' => auth()->check() && auth()->user()->can('update', $document),
                'delete' => auth()->check() && auth()->user()->can('delete', $document),
                'verify' => auth()->check() && auth()->user()->can('verify', $document),
            ],
        ]);
    }

    /**
     * Téléverser un document
     */
    public function store(UploadDocumentRequest $request)
    {
        $document = $this->documentService->uploadDocument(
            $request->user(),
            $request->validated()
        );

        return back()->with('success', 'Document téléversé avec succès !');
    }

    /**
     * Mettre à jour un document
     */
    public function update(Request $request, Document $document)
    {
        $this->authorize('update', $document);

        $this->documentService->updateDocument(
            $document,
            $request->only(['title', 'source_url'])
        );

        return back()->with('success', 'Document mis à jour avec succès !');
    }

    /**
     * Supprimer un document
     */
    public function destroy(Document $document)
    {
        $this->authorize('delete', $document);

        $this->documentService->deleteDocument($document);

        return redirect()->route('documents.index')
            ->with('success', 'Document supprimé avec succès.');
    }

    /**
     * Télécharger un document
     */
    public function download(Document $document)
    {
        abort_unless(Storage::disk('public')->exists($document->file_path), 404);

        return Storage::disk('public')->download(
            $document->file_path,
            $document->title . '.pdf'
        );
    }

    /**
     * Documents en attente de vérification
     */
    public function pending(): Response
    {
        $this->authorize('viewPending', Document::class);

        $documents = Document::with(['uploader'])
            ->where('verification_status', 'pending')
            ->latest()
            ->paginate(20);

        return Inertia::render('Documents/Pending', [
            'documents' => $documents,
        ]);
    }

    /**
     * Vérifier un document
     */
    public function verify(VerifyDocumentRequest $request, Document $document)
    {
        $this->documentService->verifyDocument(
            $document,
            $request->user(),
            $request->validated()
        );

        return back()->with('success', 'Vérification enregistrée avec succès !');
    }

    /**
     * Statistiques des documents
     */
    public function stats(): Response
    {
        $stats = $this->documentService->getStats();
        $topVerifiers = $this->documentService->getTopVerifiers();

        return Inertia::render('Documents/Stats', [
            'stats' => $stats,
            'topVerifiers' => $topVerifiers,
        ]);
    }
}

