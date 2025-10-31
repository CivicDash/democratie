<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Thématique {{ $thematique->nom }} - CivicDash</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 11pt;
            line-height: 1.6;
            color: #333;
            padding: 20px;
        }
        .header {
            border-bottom: 3px solid {{ $thematique->couleur_hex ?? '#3b82f6' }};
            padding-bottom: 15px;
            margin-bottom: 20px;
        }
        .header h1 {
            color: {{ $thematique->couleur_hex ?? '#3b82f6' }};
            font-size: 24pt;
            margin-bottom: 5px;
        }
        .header .subtitle {
            color: #666;
            font-size: 10pt;
        }
        .section {
            margin: 20px 0;
        }
        .section h2 {
            font-size: 16pt;
            color: #1f2937;
            border-bottom: 2px solid #e5e7eb;
            padding-bottom: 5px;
            margin-bottom: 10px;
        }
        .proposition-item {
            background: white;
            border-left: 3px solid {{ $thematique->couleur_hex ?? '#3b82f6' }};
            padding: 10px;
            margin: 8px 0;
            font-size: 9pt;
        }
        .proposition-title {
            font-weight: bold;
            color: #1f2937;
            margin-bottom: 3px;
        }
        .proposition-meta {
            color: #6b7280;
            font-size: 8pt;
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 10px;
            margin: 15px 0;
        }
        .stat-card {
            background: white;
            border: 1px solid #e5e7eb;
            border-radius: 5px;
            padding: 12px;
            text-align: center;
        }
        .stat-value {
            font-size: 20pt;
            font-weight: bold;
            color: {{ $thematique->couleur_hex ?? '#3b82f6' }};
        }
        .stat-label {
            font-size: 9pt;
            color: #6b7280;
            margin-top: 5px;
        }
        .footer {
            margin-top: 30px;
            padding-top: 15px;
            border-top: 1px solid #e5e7eb;
            text-align: center;
            font-size: 9pt;
            color: #9ca3af;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ $thematique->icone }} {{ $thematique->nom }}</h1>
        <div class="subtitle">{{ $thematique->code }}</div>
        <div class="subtitle">Rapport généré le {{ $generated_at }}</div>
    </div>

    <div class="section">
        <h2>📄 Description</h2>
        <p>{{ $thematique->description }}</p>
    </div>

    <div class="section">
        <h2>📊 Statistiques</h2>
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-value">{{ $stats['total_propositions'] }}</div>
                <div class="stat-label">Propositions</div>
            </div>
            <div class="stat-card">
                <div class="stat-value">{{ $stats['par_source']['assemblee'] ?? 0 }}</div>
                <div class="stat-label">Assemblée</div>
            </div>
            <div class="stat-card">
                <div class="stat-value">{{ $stats['par_source']['senat'] ?? 0 }}</div>
                <div class="stat-label">Sénat</div>
            </div>
        </div>
    </div>

    <div class="section">
        <h2>📜 Propositions de loi ({{ $propositions->count() }} dernières)</h2>
        @foreach($propositions as $proposition)
        <div class="proposition-item">
            <div class="proposition-title">{{ $proposition->titre }}</div>
            <div class="proposition-meta">
                {{ ucfirst($proposition->source) }} · 
                Déposée le {{ \Carbon\Carbon::parse($proposition->date_depot)->format('d/m/Y') }} ·
                Statut: {{ $proposition->statut }}
            </div>
        </div>
        @endforeach
    </div>

    <div class="footer">
        <p>CivicDash - Plateforme démocratique participative</p>
        <p>Document généré automatiquement · {{ $generated_at }}</p>
    </div>
</body>
</html>

