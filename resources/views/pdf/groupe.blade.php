<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Groupe {{ $groupe->sigle }} - CivicDash</title>
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
            border-bottom: 3px solid {{ $groupe->couleur_hex ?? '#3b82f6' }};
            padding-bottom: 15px;
            margin-bottom: 20px;
        }
        .header h1 {
            color: {{ $groupe->couleur_hex ?? '#3b82f6' }};
            font-size: 24pt;
            margin-bottom: 5px;
        }
        .header .subtitle {
            color: #666;
            font-size: 10pt;
        }
        .info-box {
            background: #f9fafb;
            border: 1px solid #e5e7eb;
            border-radius: 5px;
            padding: 15px;
            margin: 15px 0;
        }
        .info-row {
            display: flex;
            justify-content: space-between;
            padding: 5px 0;
        }
        .info-label {
            font-weight: bold;
            color: #6b7280;
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
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
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
            color: {{ $groupe->couleur_hex ?? '#3b82f6' }};
        }
        .stat-label {
            font-size: 9pt;
            color: #6b7280;
            margin-top: 5px;
        }
        .thematique-item {
            background: white;
            border-left: 3px solid {{ $groupe->couleur_hex ?? '#3b82f6' }};
            padding: 10px;
            margin: 10px 0;
        }
        .footer {
            margin-top: 30px;
            padding-top: 15px;
            border-top: 1px solid #e5e7eb;
            text-align: center;
            font-size: 9pt;
            color: #9ca3af;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
        }
        table th {
            background: #f3f4f6;
            padding: 8px;
            text-align: left;
            font-size: 10pt;
            border-bottom: 2px solid #e5e7eb;
        }
        table td {
            padding: 8px;
            border-bottom: 1px solid #f3f4f6;
            font-size: 10pt;
        }
    </style>
</head>
<body>
    <!-- En-tête -->
    <div class="header">
        <h1>{{ $groupe->nom }}</h1>
        <div class="subtitle">{{ $groupe->sigle }} · {{ $groupe->position_politique }}</div>
        <div class="subtitle">Rapport généré le {{ $generated_at }}</div>
    </div>

    <!-- Informations générales -->
    <div class="section">
        <h2>📋 Informations générales</h2>
        <div class="info-box">
            <div class="info-row">
                <span class="info-label">Source :</span>
                <span>{{ ucfirst($groupe->source) }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">Nombre de membres :</span>
                <span>{{ $groupe->nombre_membres }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">Président :</span>
                <span>{{ $groupe->president_nom ?? 'Non renseigné' }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">Législature :</span>
                <span>{{ $groupe->legislature ?? 'N/A' }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">Statut :</span>
                <span>{{ $groupe->actif ? 'Actif' : 'Inactif' }}</span>
            </div>
        </div>
    </div>

    <!-- Statistiques de vote -->
    <div class="section">
        <h2>📊 Statistiques de vote</h2>
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-value">{{ $stats['total_votes'] }}</div>
                <div class="stat-label">Votes totaux</div>
            </div>
            <div class="stat-card">
                <div class="stat-value">{{ $stats['votes_pour'] }}</div>
                <div class="stat-label">Votes pour</div>
            </div>
            <div class="stat-card">
                <div class="stat-value">{{ $stats['votes_contre'] }}</div>
                <div class="stat-label">Votes contre</div>
            </div>
            <div class="stat-card">
                <div class="stat-value">{{ $stats['votes_abstention'] }}</div>
                <div class="stat-label">Abstentions</div>
            </div>
        </div>
    </div>

    <!-- Thématiques favorites -->
    @if($thematiques && $thematiques->count() > 0)
    <div class="section">
        <h2>🏷️ Thématiques favorites</h2>
        @foreach($thematiques as $thematique)
        <div class="thematique-item">
            <strong>{{ $thematique->nom }}</strong>
            @if($thematique->description)
            <div style="font-size: 9pt; color: #6b7280; margin-top: 3px;">
                {{ $thematique->description }}
            </div>
            @endif
        </div>
        @endforeach
    </div>
    @endif

    <!-- Footer -->
    <div class="footer">
        <p>CivicDash - Plateforme démocratique participative</p>
        <p>Document généré automatiquement · {{ $generated_at }}</p>
    </div>
</body>
</html>

