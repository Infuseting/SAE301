<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Liste de Départ - {{ $race->race_name }}</title>
    <style>
        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            font-size: 11px;
            margin: 20px;
            color: #333;
        }
        h1 {
            color: #047857;
            font-size: 24px;
            margin-bottom: 5px;
            text-align: center;
        }
        .subtitle {
            text-align: center;
            color: #666;
            margin-bottom: 20px;
            font-size: 13px;
        }
        .info-box {
            background: #f3f4f6;
            padding: 10px;
            margin-bottom: 20px;
            border-radius: 5px;
        }
        .info-box p {
            margin: 5px 0;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th {
            background-color: #047857;
            color: white;
            padding: 10px;
            text-align: left;
            font-size: 12px;
            font-weight: bold;
        }
        td {
            padding: 8px;
            border-bottom: 1px solid #e5e7eb;
        }
        tr:nth-child(even) {
            background-color: #f9fafb;
        }
        .dossard {
            font-weight: bold;
            font-size: 14px;
            color: #047857;
            text-align: center;
        }
        .present {
            color: #059669;
            font-weight: bold;
        }
        .absent {
            color: #dc2626;
        }
        .footer {
            margin-top: 30px;
            text-align: center;
            color: #9ca3af;
            font-size: 10px;
            border-top: 1px solid #e5e7eb;
            padding-top: 10px;
        }
        .stats {
            text-align: right;
            margin-top: 10px;
            font-size: 10px;
            color: #6b7280;
        }
    </style>
</head>
<body>
    <h1>🏃 Liste de Départ / Start-List</h1>
    <div class="subtitle">{{ $race->race_name }}</div>
    
    <div class="info-box">
        <p><strong>Raid:</strong> {{ $race->raid->raid_name }}</p>
        <p><strong>Date:</strong> {{ \Carbon\Carbon::parse($race->raid->raid_date_start)->format('d/m/Y') }}</p>
        <p><strong>Lieu:</strong> {{ $race->raid->raid_city }} ({{ $race->raid->raid_postal_code }})</p>
        @if($race->race_distance)
            <p><strong>Distance:</strong> {{ $race->race_distance }} km</p>
        @endif
        <p><strong>Total équipes inscrites:</strong> {{ count($registrations) }}</p>
        <p><strong>Document généré le:</strong> {{ now()->format('d/m/Y à H:i') }}</p>
    </div>
    
    <table>
        <thead>
            <tr>
                <th style="width: 10%; text-align: center;">Dossard</th>
                <th style="width: 25%;">Équipe</th>
                <th style="width: 25%;">Capitaine</th>
                <th style="width: 20%;">Email</th>
                <th style="width: 10%; text-align: center;">Présent</th>
                <th style="width: 10%; text-align: center;">Points</th>
            </tr>
        </thead>
        <tbody>
            @forelse($registrations as $registration)
                <tr>
                    <td class="dossard">
                        {{ $registration->reg_dossard ?? '-' }}
                    </td>
                    <td>
                        <strong>{{ $registration->team->equ_name ?? 'N/A' }}</strong>
                    </td>
                    <td>
                        @if($registration->team && $registration->team->leader)
                            {{ $registration->team->leader->first_name }} 
                            {{ $registration->team->leader->last_name }}
                        @else
                            N/A
                        @endif
                    </td>
                    <td style="font-size: 9px;">
                        @if($registration->team && $registration->team->leader)
                            {{ $registration->team->leader->email }}
                        @else
                            -
                        @endif
                    </td>
                    <td style="text-align: center;">
                        @if($registration->is_present)
                            <span class="present">✓ OUI</span>
                        @else
                            <span class="absent">✗ NON</span>
                        @endif
                    </td>
                    <td style="text-align: center;">
                        {{ number_format($registration->reg_points, 0) }}
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" style="text-align: center; color: #9ca3af; padding: 20px;">
                        Aucune équipe inscrite pour cette course
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
    
    <div class="stats">
        <strong>{{ count($registrations) }}</strong> équipe(s) inscrite(s) pour cette course
    </div>

    <div class="footer">
        Document officiel généré par le système SAE301 - {{ config('app.name') }}
    </div>
</body>
</html>
