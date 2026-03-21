<?php

namespace App\Console\Commands;

use App\Models\ActeurAN;
use App\Models\DashboardStat;
use App\Models\Senateur;
use App\Models\ScrutinAN;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CalculateDashboardStats extends Command
{
    protected $signature = 'dashboard:calculate-stats {--force : Force recalcul même si frais}';
    protected $description = 'Calcule et stocke les statistiques du dashboard (à lancer quotidiennement)';

    public function handle(): int
    {
        $this->info('🚀 Calcul des statistiques du dashboard...');
        $start = microtime(true);

        // Vérifier si les stats sont fraîches (sauf si --force)
        if (!$this->option('force') && DashboardStat::isFresh('top_deputes', 12)) {
            $this->info('✅ Les stats sont encore fraîches (< 12h). Utilisez --force pour forcer le recalcul.');
            return self::SUCCESS;
        }

        // 🏆 TOP DÉPUTÉS (par votes)
        $this->info('📊 Calcul top députés...');
        $topDeputes = DB::table('votes_individuels_an')
            ->select('acteur_ref', DB::raw('count(*) as nb_votes'))
            ->groupBy('acteur_ref')
            ->orderByDesc('nb_votes')
            ->limit(10)
            ->get()
            ->map(function ($item) {
                $acteur = ActeurAN::find($item->acteur_ref);
                return [
                    'uid' => $item->acteur_ref,
                    'nom' => $acteur ? ($acteur->prenom . ' ' . $acteur->nom) : 'Inconnu',
                    'photo' => $acteur?->photo_url,
                    'groupe' => null,
                    'groupe_couleur' => '#6B7280',
                    'nb_votes' => $item->nb_votes,
                ];
            })->toArray();
        DashboardStat::set('top_deputes', $topDeputes);
        $this->info('  ✓ ' . count($topDeputes) . ' députés');

        // 🏆 TOP SÉNATEURS (par amendements)
        $this->info('📊 Calcul top sénateurs...');
        $topSenateurs = DB::table('amendements_senat')
            ->select('senateur_matricule', DB::raw('count(*) as nb_amendements'))
            ->whereNotNull('senateur_matricule')
            ->groupBy('senateur_matricule')
            ->orderByDesc('nb_amendements')
            ->limit(10)
            ->get()
            ->map(function ($item) {
                $senateur = Senateur::where('matricule', $item->senateur_matricule)
                    ->first(['matricule', 'prenom', 'nom', 'nom_usuel', 'groupe_politique_code', 'photo_wikipedia_url']);
                return [
                    'matricule' => $item->senateur_matricule,
                    'nom' => $senateur ? ($senateur->prenom . ' ' . ($senateur->nom_usuel ?? $senateur->nom)) : 'Inconnu',
                    'photo' => $senateur?->photo_url,
                    'groupe' => $senateur?->groupe_politique_code,
                    'groupe_couleur' => '#DC2626',
                    'nb_amendements' => $item->nb_amendements,
                ];
            })->toArray();
        DashboardStat::set('top_senateurs', $topSenateurs);
        $this->info('  ✓ ' . count($topSenateurs) . ' sénateurs');

        // 🏛️ GROUPES ACTIFS
        $this->info('📊 Calcul groupes actifs...');
        $couleurs = [
            'RN' => '#0D1B4C', 'LFI-NFP' => '#CC2443', 'EPR' => '#7B4FBB',
            'SOC' => '#FF6B6B', 'DR' => '#0066CC', 'MODEM' => '#FF9800',
            'ECOLO' => '#00C853', 'GDR' => '#E53935', 'LIOT' => '#9C27B0',
            'HOR' => '#42A5F5', 'NI' => '#757575', 'UDR' => '#1976D2',
            'LFI' => '#CC2443', 'RE' => '#FFD600', 'LR' => '#0066CC',
        ];
        
        $groupesActifs = DB::table('mandats_an')
            ->join('organes_an', 'mandats_an.organe_ref', '=', 'organes_an.uid')
            ->where('organes_an.code_type', 'GP')
            ->whereNull('mandats_an.date_fin')
            ->select('organes_an.uid', 'organes_an.libelle', 'organes_an.libelle_abrege', DB::raw('count(*) as nb_membres'))
            ->groupBy('organes_an.uid', 'organes_an.libelle', 'organes_an.libelle_abrege')
            ->orderByDesc('nb_membres')
            ->limit(10)
            ->get()
            ->map(fn($g) => [
                'uid' => $g->uid,
                'nom' => $g->libelle,
                'sigle' => $g->libelle_abrege ?? substr($g->libelle, 0, 10),
                'couleur' => $couleurs[$g->libelle_abrege] ?? '#6B7280',
                'nb_membres' => $g->nb_membres,
            ])->toArray();
        DashboardStat::set('groupes_actifs', $groupesActifs);
        $this->info('  ✓ ' . count($groupesActifs) . ' groupes');

        // 📊 DERNIERS SCRUTINS (avec calcul correct des votes)
        $this->info('📊 Calcul derniers scrutins...');
        $derniersScrutins = ScrutinAN::orderByDesc('date_scrutin')
            ->limit(10)
            ->get()
            ->map(function ($s) {
                // Utiliser les accesseurs qui calculent depuis ventilation_votes si les colonnes sont vides
                $pour = $s->pour_calcule;
                $contre = $s->contre_calcule;
                $abstention = $s->abstentions_calcule;
                
                // Déterminer si adopté basé sur le résultat_code ou le calcul
                $adopte = $s->resultat_code === 'adopté' || ($pour > $contre);
                
                return [
                    'uid' => $s->uid,
                    'numero' => $s->numero,
                    'titre' => \Illuminate\Support\Str::limit($s->titre ?? 'Scrutin n°' . $s->numero, 80),
                    'date' => $s->date_scrutin?->format('d/m/Y'),
                    'pour' => $pour,
                    'contre' => $contre,
                    'abstention' => $abstention,
                    'adopte' => $adopte,
                ];
            })->toArray();
        DashboardStat::set('derniers_scrutins', $derniersScrutins);
        $this->info('  ✓ ' . count($derniersScrutins) . ' scrutins');

        // 📈 STATS GLOBALES
        $this->info('📊 Calcul stats globales...');
        $allScrutins = ScrutinAN::get();
        $nbAdoptes = $allScrutins->filter(fn($s) => $s->pour_calcule > $s->contre_calcule)->count();
        $globalStats = [
            'nb_deputes' => ActeurAN::count(),
            'nb_senateurs' => Senateur::where('etat', 'ACTIF')->count(),
            'nb_scrutins' => $allScrutins->count(),
            'nb_amendements_an' => DB::table('amendements_an')->count(),
            'nb_maires' => DB::table('maires')->count(),
            'nb_gouvernements' => DB::table('gouvernements')->count(),
            'nb_scrutins_adoptes' => $nbAdoptes,
            'nb_scrutins_rejetes' => $allScrutins->count() - $nbAdoptes,
        ];
        DashboardStat::set('global_stats', $globalStats);
        $this->info('  ✓ Stats globales calculées');

        $elapsed = round(microtime(true) - $start, 2);
        $this->newLine();
        $this->info("✅ Statistiques calculées en {$elapsed}s");

        return self::SUCCESS;
    }
}

