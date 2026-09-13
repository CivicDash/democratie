<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Agrège le journal d'accès d'objectif2027.fr en compteurs quotidiens.
 *
 * Principe : le serveur web journalise déjà les requêtes pour fonctionner. On lit ce
 * journal, on compte, on écrit des AGRÉGATS. Aucune adresse IP, aucune ligne de log,
 * aucun identifiant de visiteur n'est conservé — donc ni cookie, ni script chez le
 * visiteur, ni service tiers.
 *
 * L'empreinte de visiteur (IP + User-Agent) est hachée avec un sel quotidien tiré de
 * APP_KEY : elle sert uniquement à compter des visiteurs distincts DANS LA JOURNÉE, puis
 * disparaît avec le processus. Le sel changeant chaque jour, deux jours ne sont pas
 * rapprochables.
 */
class AudienceAgreger extends Command
{
    protected $signature = 'audience:agreger
        {--fichier= : journal d\'accès (JSON par ligne) ; défaut storage/app/audience/objectif2027.log}
        {--site=objectif2027.fr : site mesuré}
        {--jour= : ne traiter qu\'un jour (AAAA-MM-JJ)}
        {--dry-run : afficher sans écrire}';

    protected $description = 'Agrège le journal d\'accès en compteurs quotidiens (sans cookie ni traceur).';

    /**
     * Signatures de robots déclarés. Volontairement large : un agent qui s'annonce comme
     * robot est cru sur parole, c'est le cas le plus fréquent et le moins coûteux à traiter.
     */
    private const BOTS = [
        'bot', 'crawler', 'spider', 'crawl', 'slurp', 'facebookexternalhit', 'ia_archiver',
        'curl/', 'wget/', 'python-requests', 'python-urllib', 'go-http-client', 'java/',
        'headlesschrome', 'phantomjs', 'scrapy', 'axios/', 'okhttp', 'libwww-perl',
        'lighthouse', 'pingdom', 'uptimerobot', 'semrush', 'ahrefs', 'mj12', 'dotbot',
        'gptbot', 'claudebot', 'ccbot', 'perplexitybot', 'bytespider', 'petalbot',
    ];

    /** Ce qui n'est pas une page lue par un humain. */
    private const EXCLUS = ['/pagefind/', '/_astro/', '/data/', '/og/', '/favicon', '/robots.txt', '/sitemap.xml'];

    public function handle(): int
    {
        // Le conteneur applicatif ne monte que .env et storage : il ne voit pas
        // /var/log/caddy. Le journal est donc déposé dans le storage par le script
        // d'exploitation `audience-sync.sh`, qui tourne sur l'hôte.
        $fichier = (string) ($this->option('fichier') ?: storage_path('app/audience/objectif2027.log'));
        if (! is_file($fichier)) {
            $this->error("Journal introuvable : {$fichier}");
            $this->line('Le journal doit être déposé dans le storage par audience-sync.sh (hôte).');
            $this->line('Voir docs/audience.md.');

            return self::FAILURE;
        }

        $site = (string) $this->option('site');
        $filtreJour = $this->option('jour');
        $sel = substr(hash('sha256', (string) config('app.key')), 0, 16);

        // [jour][chemin] => ['h' => n, 'b' => n, 'emp' => [empreinte => true]]
        $agg = [];
        $lignes = 0;
        $ignorees = 0;

        $fh = fopen($fichier, 'r');
        while (($ligne = fgets($fh)) !== false) {
            $lignes++;
            $e = json_decode($ligne, true);
            if (! is_array($e)) {
                $ignorees++;

                continue;
            }

            $uri = (string) ($e['request']['uri'] ?? '');
            $chemin = parse_url($uri, PHP_URL_PATH) ?: '/';
            $statut = (int) ($e['status'] ?? 0);

            // Seules les pages réellement servies comptent : ni assets, ni 404, ni redirections.
            if ($statut !== 200 || $this->estExclu($chemin)) {
                $ignorees++;

                continue;
            }

            $jour = substr(date('c', (int) ($e['ts'] ?? time())), 0, 10);
            if ($filtreJour && $jour !== $filtreJour) {
                continue;
            }

            $ua = (string) (($e['request']['headers']['User-Agent'][0] ?? ''));
            $estBot = $this->estBot($ua);

            $agg[$jour][$chemin] ??= ['h' => 0, 'b' => 0, 'emp' => []];
            $agg[$jour][$chemin][$estBot ? 'b' : 'h']++;

            if (! $estBot) {
                // Empreinte éphémère, jamais stockée : elle ne sert qu'à compter.
                $ip = (string) ($e['request']['remote_ip'] ?? $e['request']['client_ip'] ?? '');
                $agg[$jour][$chemin]['emp'][hash('sha256', $sel.$jour.$ip.$ua)] = true;
            }
        }
        fclose($fh);

        $ecrits = 0;
        foreach ($agg as $jour => $chemins) {
            foreach ($chemins as $chemin => $c) {
                $ligne = [
                    'vues_humaines' => $c['h'],
                    'vues_bots' => $c['b'],
                    'visiteurs_estimes' => count($c['emp']),
                ];
                if ($this->option('dry-run')) {
                    $this->line(sprintf('  %s  %-40s humains=%-5d bots=%-5d visiteurs≈%d',
                        $jour, mb_strimwidth($chemin, 0, 40, '…'), $c['h'], $c['b'], count($c['emp'])));
                } else {
                    DB::table('audience_jour')->updateOrInsert(
                        ['site' => $site, 'jour' => $jour, 'chemin' => mb_substr($chemin, 0, 300)],
                        $ligne + ['updated_at' => now(), 'created_at' => now()],
                    );
                }
                $ecrits++;
            }
        }

        $this->info(($this->option('dry-run') ? 'DRY-RUN : ' : '')
            ."{$lignes} ligne(s) lue(s), {$ignorees} ignorée(s), {$ecrits} agrégat(s).");

        return self::SUCCESS;
    }

    private function estExclu(string $chemin): bool
    {
        foreach (self::EXCLUS as $p) {
            if (str_starts_with($chemin, $p)) {
                return true;
            }
        }

        return false;
    }

    private function estBot(string $ua): bool
    {
        if ($ua === '') {
            return true;   // un navigateur envoie toujours un User-Agent
        }
        $ua = strtolower($ua);
        foreach (self::BOTS as $motif) {
            if (str_contains($ua, $motif)) {
                return true;
            }
        }

        return false;
    }
}
