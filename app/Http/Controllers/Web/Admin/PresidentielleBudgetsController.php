<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Models\CadrageBudgetaire;
use App\Models\CadrageLigne;
use App\Models\CandidatPresidentielle;
use App\Models\IngestionDocument;
use App\Models\IngestionProposition;
use App\Models\ProgrammeDocument;
use App\Services\Presidentielle\ModerationService;
use App\Services\Presidentielle\ReglesCadrageBudgetaire;
use App\Support\Locuteur;
use App\Support\Porteur;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

/**
 * Back-office des cadrages budgétaires et de la ligne de référence officielle.
 *
 * Même groupe de routes que le reste du module : mêmes permissions, et surtout le même
 * middleware GardeIntegriteExport — une modification qui rendrait impubliable un cadrage
 * publié est annulée avant d'atteindre l'export. Valider, publier, dépublier et supprimer
 * passent par les actions génériques (type `cadrage`).
 *
 * Écritures sous forme lisible par AuditEcritures (`Modele::create([...])` explicite ou
 * `$modele->update($validated)`) : chaque clé validée doit arriver en base.
 */
class PresidentielleBudgetsController extends Controller
{
    public function index(ModerationService $service)
    {
        $resume = fn (CadrageBudgetaire $c) => [
            'id' => $c->id,
            'titre' => $c->titre,
            'nature' => $c->nature,
            'nature_libelle' => CadrageBudgetaire::NATURES[$c->nature] ?? $c->nature,
            'date_source' => $c->date_source?->toDateString(),
            'statut_validation' => $c->statut_validation,
            'affiche_publiquement' => $c->affiche_publiquement,
            'nb_lignes' => $c->lignes->count(),
            'nb_verifiees' => $c->lignes->filter->estAffichee()->count(),
            'raisons' => $service->raisonsNonPubliable($c),
        ];

        $cadrages = CadrageBudgetaire::where('election', '2027')
            ->with(['lignes.proposition.document', 'lignes.document', 'document', 'programmeDocument'])
            ->chronologieInverse()
            ->get();

        // Ordre alphabétique par nom : aucun candidat n'est mis en tête par l'écran.
        $candidats = CandidatPresidentielle::with('personnePolitique')->where('election', '2027')->get()
            ->sortBy(fn ($c) => mb_strtolower(($c->personnePolitique?->nom ?? '').' '.($c->personnePolitique?->prenom ?? '')))
            ->values()
            ->map(fn ($c) => [
                'id' => $c->id,
                'nom' => trim(($c->personnePolitique?->prenom ?? '').' '.($c->personnePolitique?->nom ?? '')),
                'publie' => $c->estPublie(),
                'cadrages' => $cadrages->where('candidat_id', $c->id)->map($resume)->values(),
            ]);

        return Inertia::render('Admin/Presidentielle/Budgets', [
            'references' => $cadrages->whereNull('candidat_id')->map($resume)->values(),
            'candidats' => $candidats,
            'natures' => CadrageBudgetaire::NATURES,
            'documents' => $this->documentsChoix(),
            'referentiels' => ProgrammeDocument::with('candidat.personnePolitique')->orderBy('id')->get()
                ->map(fn ($d) => ['id' => $d->id, 'titre' => $d->titre, 'candidat_id' => $d->candidat_id]),
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'candidat_id' => ['nullable', 'integer', 'exists:candidats_presidentielle,id'],
            'nature' => ['required', Rule::in(array_keys(CadrageBudgetaire::NATURES))],
            'titre' => ['required', 'string', 'max:300'],
            'date_source' => ['required', 'date'],
            'ingestion_document_id' => ['nullable', 'integer', 'exists:ingestion_documents,id'],
            'programme_document_id' => ['nullable', 'integer', 'exists:programme_documents,id'],
            'note_methodologique' => ['nullable', 'string', 'max:5000'],
        ], [
            'date_source.required' => 'La date de présentation est obligatoire : c\'est elle qui ordonne les cadrages.',
        ]);
        $this->verifierCoherence($request->input('candidat_id'), $request->input('nature'), $request->input('ingestion_document_id'), $request->input('programme_document_id'));

        $cadrage = CadrageBudgetaire::create([
            'election' => '2027',
            'candidat_id' => $request->input('candidat_id'),
            'nature' => $request->input('nature'),
            'titre' => $request->input('titre'),
            'date_source' => $request->input('date_source'),
            'ingestion_document_id' => $request->input('ingestion_document_id'),
            'programme_document_id' => $request->input('programme_document_id'),
            'note_methodologique' => $request->input('note_methodologique'),
            'statut_validation' => 'detecte',
            'affiche_publiquement' => false,
        ]);

        return redirect()->route('admin.presidentielle.budgets.show', $cadrage->id)
            ->with('success', 'Cadrage créé (detecte). Ajoutez ses lignes, vérifiez-les une à une, puis validez.');
    }

    public function show(CadrageBudgetaire $cadrage, ModerationService $service, ReglesCadrageBudgetaire $regles)
    {
        $cadrage->load(['candidat.personnePolitique', 'lignes.proposition.document', 'lignes.document', 'document', 'programmeDocument']);
        $reference = $cadrage->candidat_id === null;
        $doc = $cadrage->documentSource();

        return Inertia::render('Admin/Presidentielle/BudgetDetail', [
            'cadrage' => [
                'id' => $cadrage->id,
                'titre' => $cadrage->titre,
                'nature' => $cadrage->nature,
                'date_source' => $cadrage->date_source?->toDateString(),
                'candidat' => $cadrage->candidat?->personnePolitique?->nom_complet,
                'reference' => $reference,
                'ingestion_document_id' => $cadrage->ingestion_document_id,
                'programme_document_id' => $cadrage->programme_document_id,
                'note_methodologique' => $cadrage->note_methodologique,
                'non_precise' => $cadrage->rubriques_non_precisees ?? [],
                'statut_validation' => $cadrage->statut_validation,
                'affiche_publiquement' => $cadrage->affiche_publiquement,
                'document' => $doc ? [
                    'titre' => $doc->titre,
                    'url' => $doc->url,
                    'porteur' => Porteur::LIBELLES[$doc->porteurType()] ?? $doc->porteurType(),
                    'porteur_libelle' => $doc->porteur_libelle,
                ] : null,
            ],
            'raisons' => $service->raisonsNonPubliable($cadrage),
            'lignes' => $cadrage->lignes->map(fn (CadrageLigne $l) => [
                'id' => $l->id,
                'rubrique' => $l->rubrique,
                'libelle' => $l->libelle,
                'montant' => $l->montant === null ? null : (float) $l->montant,
                'montant_max' => $l->montant_max === null ? null : (float) $l->montant_max,
                'unite' => $l->unite,
                'qualificatif' => $l->qualificatif,
                'portee' => $l->portee,
                'annee' => $l->annee,
                'valeur_texte' => $l->valeur_texte,
                'citation' => $l->citation,
                'reperage' => $l->reperage,
                'locuteur' => $l->locuteur,
                'role_locuteur' => $l->role_locuteur,
                'qualite_locuteur' => $l->qualite_locuteur,
                'proposition_id' => $l->proposition_id,
                'proposition_statut' => $l->proposition?->statut,
                'ingestion_document_id' => $l->ingestion_document_id,
                'source_producteur' => $l->source_producteur,
                'source_titre' => $l->source_titre,
                'source_url' => $l->source_url,
                'source_date' => $l->source_date?->toDateString(),
                'note' => $l->note,
                'autres_citations' => $l->autres_citations ?? [],
                'verbatim_verifie' => $l->verbatim_verifie,
                'verification' => $l->verification,
                'verifie_at' => $l->verifie_at?->format('d/m/Y H:i'),
                'raisons' => $regles->raisonsLigne($l, $cadrage),
            ])->values(),
            'listes' => [
                'natures' => CadrageBudgetaire::NATURES,
                'rubriques' => $reference ? CadrageLigne::RUBRIQUES : CadrageLigne::GRILLE + [CadrageLigne::POSTE => CadrageLigne::RUBRIQUES[CadrageLigne::POSTE]],
                'unites' => CadrageLigne::UNITES,
                'unites_par_rubrique' => collect(CadrageLigne::RUBRIQUES)->keys()->mapWithKeys(fn ($r) => [$r => CadrageLigne::unitesAdmises($r)]),
                'qualificatifs' => CadrageLigne::QUALIFICATIFS,
                'portees' => CadrageLigne::PORTEES,
                'qualites' => [Locuteur::EQUIPE => Locuteur::LIBELLES[Locuteur::EQUIPE]],
            ],
            'documents' => $this->documentsChoix(),
            'referentiels' => ProgrammeDocument::where('candidat_id', $cadrage->candidat_id)->orderBy('id')->get(['id', 'titre']),
            'propositions' => $cadrage->ingestion_document_id
                ? IngestionProposition::where('document_id', $cadrage->ingestion_document_id)->orderBy('id')
                    ->get(['id', 'statut', 'locuteur', 'timestamp_ou_paragraphe', 'citation_verbatim'])
                    ->map(fn ($p) => ['id' => $p->id, 'libelle' => "#{$p->id} · {$p->statut} · ".($p->timestamp_ou_paragraphe ?? '—').' · '.mb_strimwidth((string) $p->citation_verbatim, 0, 80, '…')])
                : [],
        ]);
    }

    public function update(Request $request, CadrageBudgetaire $cadrage)
    {
        $validated = $request->validate([
            'nature' => ['required', Rule::in(array_keys(CadrageBudgetaire::NATURES))],
            'titre' => ['required', 'string', 'max:300'],
            'date_source' => ['required', 'date'],
            'ingestion_document_id' => ['nullable', 'integer', 'exists:ingestion_documents,id'],
            'programme_document_id' => ['nullable', 'integer', 'exists:programme_documents,id'],
            'note_methodologique' => ['nullable', 'string', 'max:5000'],
        ]);
        $this->verifierCoherence($cadrage->candidat_id, $validated['nature'], $validated['ingestion_document_id'] ?? null, $validated['programme_document_id'] ?? null);

        $cadrage->update($validated);

        return back()->with('success', 'En-tête du cadrage enregistré.');
    }

    public function ligneStore(Request $request, CadrageBudgetaire $cadrage)
    {
        [$reference, $rubriques] = $this->rubriquesAdmises($cadrage);
        $request->validate([
            'rubrique' => ['required', Rule::in($rubriques)],
            'libelle' => ['nullable', 'string', 'max:300'],
            'montant' => ['nullable', 'numeric', 'between:-999999999999,999999999999'],
            'montant_max' => ['nullable', 'numeric', 'between:-999999999999,999999999999'],
            'unite' => ['required', Rule::in(array_keys(CadrageLigne::UNITES))],
            'qualificatif' => ['nullable', Rule::in(array_keys(CadrageLigne::QUALIFICATIFS))],
            'portee' => ['nullable', Rule::in(array_keys(CadrageLigne::PORTEES))],
            'annee' => ['nullable', 'integer', 'between:1990,2100'],
            'valeur_texte' => ['nullable', 'string', 'max:2000'],
            'citation' => ['nullable', 'string', 'max:5000'],
            'reperage' => ['nullable', 'string', 'max:100'],
            'locuteur' => ['nullable', 'string', 'max:150', 'required_with:qualite_locuteur', Rule::prohibitedIf($reference)],
            'role_locuteur' => ['nullable', 'string', 'max:200', 'required_with:locuteur'],
            // Le cadrage d'un candidat n'admet que lui et son équipe de campagne.
            'qualite_locuteur' => ['nullable', Rule::in([Locuteur::EQUIPE]), 'required_with:locuteur'],
            'proposition_id' => ['nullable', 'integer', 'exists:ingestion_propositions,id'],
            'ingestion_document_id' => ['nullable', 'integer', 'exists:ingestion_documents,id'],
            'source_producteur' => ['nullable', 'string', 'max:120'],
            'source_titre' => ['nullable', 'string', 'max:300'],
            'source_url' => ['nullable', 'url:http,https', 'max:1000'],
            'source_date' => ['nullable', 'date'],
            'note' => ['nullable', 'string', 'max:5000'],
        ], [
            'role_locuteur.required_with' => 'Le rôle du locuteur au moment des propos est obligatoire.',
            'qualite_locuteur.required_with' => 'Un locuteur autre que le candidat parle pour son équipe de campagne.',
            'qualite_locuteur.in' => 'Seuls le candidat et son équipe de campagne entrent dans son cadrage : la parole d\'un parti va dans « Autour du candidat ».',
            'locuteur.prohibited' => 'Une ligne de référence n\'a pas de locuteur : elle cite un producteur public.',
        ]);

        CadrageLigne::create([
            'cadrage_id' => $cadrage->id,
            'rubrique' => $request->input('rubrique'),
            'libelle' => $request->input('libelle'),
            'montant' => $request->input('montant'),
            'montant_max' => $request->input('montant_max'),
            'unite' => $request->input('unite'),
            'qualificatif' => $request->input('qualificatif'),
            'portee' => $request->input('portee'),
            'annee' => $request->input('annee'),
            'valeur_texte' => $request->input('valeur_texte'),
            'citation' => $request->input('citation'),
            'reperage' => $request->input('reperage'),
            'locuteur' => $request->input('locuteur'),
            'role_locuteur' => $request->input('role_locuteur'),
            'qualite_locuteur' => $request->input('qualite_locuteur'),
            'proposition_id' => $request->input('proposition_id'),
            'ingestion_document_id' => $request->input('ingestion_document_id'),
            'source_producteur' => $request->input('source_producteur'),
            'source_titre' => $request->input('source_titre'),
            'source_url' => $request->input('source_url'),
            'source_date' => $request->input('source_date'),
            'note' => $request->input('note'),
            // Une ligne saisie à la main n'est pas vérifiée du seul fait d'avoir été écrite.
            'verification' => 'a_verifier',
            'ordre' => (int) $cadrage->lignes()->max('ordre') + 1,
        ]);

        return back()->with('success', 'Ligne ajoutée (à vérifier).');
    }

    /**
     * Modifie une ligne. Toute modification de ce qu'un humain a confirmé — le chiffre,
     * son unité, son année, sa citation, son repérage, son locuteur — remet la ligne
     * « à vérifier » : elle cesse de paraître jusqu'à la prochaine vérification.
     */
    public function ligneUpdate(Request $request, CadrageLigne $ligne)
    {
        [$reference, $rubriques] = $this->rubriquesAdmises($ligne->cadrage);
        // Règles écrites en toutes lettres (et non partagées) : l'audit des écritures ne
        // sait lire qu'un tableau littéral, et c'est lui qui garantit que chaque clé arrive en base.
        $validated = $request->validate([
            'rubrique' => ['required', Rule::in($rubriques)],
            'libelle' => ['nullable', 'string', 'max:300'],
            'montant' => ['nullable', 'numeric', 'between:-999999999999,999999999999'],
            'montant_max' => ['nullable', 'numeric', 'between:-999999999999,999999999999'],
            'unite' => ['required', Rule::in(array_keys(CadrageLigne::UNITES))],
            'qualificatif' => ['nullable', Rule::in(array_keys(CadrageLigne::QUALIFICATIFS))],
            'portee' => ['nullable', Rule::in(array_keys(CadrageLigne::PORTEES))],
            'annee' => ['nullable', 'integer', 'between:1990,2100'],
            'valeur_texte' => ['nullable', 'string', 'max:2000'],
            'citation' => ['nullable', 'string', 'max:5000'],
            'reperage' => ['nullable', 'string', 'max:100'],
            'locuteur' => ['nullable', 'string', 'max:150', 'required_with:qualite_locuteur', Rule::prohibitedIf($reference)],
            'role_locuteur' => ['nullable', 'string', 'max:200', 'required_with:locuteur'],
            // Le cadrage d'un candidat n'admet que lui et son équipe de campagne.
            'qualite_locuteur' => ['nullable', Rule::in([Locuteur::EQUIPE]), 'required_with:locuteur'],
            'proposition_id' => ['nullable', 'integer', 'exists:ingestion_propositions,id'],
            'ingestion_document_id' => ['nullable', 'integer', 'exists:ingestion_documents,id'],
            'source_producteur' => ['nullable', 'string', 'max:120'],
            'source_titre' => ['nullable', 'string', 'max:300'],
            'source_url' => ['nullable', 'url:http,https', 'max:1000'],
            'source_date' => ['nullable', 'date'],
            'note' => ['nullable', 'string', 'max:5000'],
        ], [
            'role_locuteur.required_with' => 'Le rôle du locuteur au moment des propos est obligatoire.',
            'qualite_locuteur.required_with' => 'Un locuteur autre que le candidat parle pour son équipe de campagne.',
            'qualite_locuteur.in' => 'Seuls le candidat et son équipe de campagne entrent dans son cadrage : la parole d\'un parti va dans « Autour du candidat ».',
            'locuteur.prohibited' => 'Une ligne de référence n\'a pas de locuteur : elle cite un producteur public.',
        ]);
        $validated += array_fill_keys(['libelle', 'montant', 'montant_max', 'qualificatif', 'portee', 'annee', 'valeur_texte', 'citation', 'reperage',
            'locuteur', 'role_locuteur', 'qualite_locuteur', 'proposition_id', 'ingestion_document_id',
            'source_producteur', 'source_titre', 'source_url', 'source_date', 'note'], null);

        $ligne->fill($validated);
        $aRevoir = $ligne->isDirty(CadrageLigne::CHAMPS_VERIFIES) && $ligne->verification === 'verifie';
        if ($aRevoir) {
            $ligne->verification = 'a_verifier';
            $ligne->verifie_par = null;
            $ligne->verifie_at = null;
        }
        $ligne->save();

        return back()->with('success', $aRevoir ? 'Ligne enregistrée ; elle est remise « à vérifier ».' : 'Ligne enregistrée.');
    }

    /** Marque une ligne vérifiée (ou la remet à vérifier), et trace qui l'a fait. */
    public function ligneVerification(Request $request, CadrageLigne $ligne)
    {
        $validated = $request->validate([
            'verification' => ['required', Rule::in(array_keys(CadrageLigne::VERIFICATIONS))],
        ]);
        $verifie = $validated['verification'] === 'verifie';
        $validated['verifie_par'] = $verifie ? $request->user()->id : null;
        $validated['verifie_at'] = $verifie ? now() : null;
        $ligne->update($validated);

        return back()->with('success', $verifie ? 'Ligne vérifiée.' : 'Ligne remise à vérifier.');
    }

    public function ligneDestroy(CadrageLigne $ligne)
    {
        $ligne->delete();

        return back()->with('success', 'Ligne supprimée.');
    }

    /** @return array{0: bool, 1: list<string>} référence ?, rubriques admises */
    private function rubriquesAdmises(CadrageBudgetaire $cadrage): array
    {
        $reference = $cadrage->candidat_id === null;

        return [$reference, $reference
            ? array_keys(CadrageLigne::RUBRIQUES)
            : [...array_keys(CadrageLigne::GRILLE), CadrageLigne::POSTE]];
    }

    /** Une référence n'a pas de candidat, un cadrage de candidat n'est pas la référence, une seule source. */
    private function verifierCoherence(mixed $candidatId, ?string $nature, mixed $documentId, mixed $referentielId): void
    {
        $reference = $nature === CadrageBudgetaire::NATURE_REFERENCE;
        if ($reference && $candidatId) {
            throw ValidationException::withMessages(['nature' => 'La référence officielle n\'a pas de candidat.']);
        }
        if (! $reference && ! $candidatId) {
            throw ValidationException::withMessages(['candidat_id' => 'Choisissez un candidat, ou la nature « référence officielle ».']);
        }
        if ($documentId && $referentielId) {
            throw ValidationException::withMessages(['ingestion_document_id' => 'Une seule source : une prise de parole OU un référentiel de programme.']);
        }
        if ($documentId && Porteur::estEntourage(IngestionDocument::whereKey($documentId)->value('porteur_type'))) {
            throw ValidationException::withMessages(['ingestion_document_id' => 'Ce document est porté par un parti ou un groupe parlementaire : sa vision n\'est pas le cadrage du candidat.']);
        }
    }

    private function documentsChoix()
    {
        return IngestionDocument::orderByDesc('id')->limit(60)->get(['id', 'titre', 'date_publication', 'porteur_type'])
            ->map(fn ($d) => [
                'id' => $d->id,
                'titre' => $d->titre,
                'date' => $d->date_publication?->toDateString(),
                'porteur_type' => $d->porteurType(),
            ]);
    }
}
