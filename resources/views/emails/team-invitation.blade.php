<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invitation à rejoindre une équipe</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
        }

        .container {
            max-width: 600px;
            margin: 20px auto;
            background: #ffffff;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: #ffffff;
            padding: 30px;
            text-align: center;
        }

        .header h1 {
            margin: 0;
            font-size: 24px;
        }

        .content {
            padding: 30px;
        }

        .race-info {
            background: #f8f9fa;
            border-left: 4px solid #667eea;
            padding: 15px;
            margin: 20px 0;
        }

        .race-info h2 {
            margin: 0 0 10px 0;
            color: #667eea;
            font-size: 18px;
        }

        .race-info p {
            margin: 5px 0;
            color: #666;
        }

        .cta-button {
            display: inline-block;
            padding: 15px 30px;
            background: #667eea;
            color: #ffffff !important;
            text-decoration: none;
            border-radius: 5px;
            font-weight: bold;
            margin: 20px 0;
            text-align: center;
        }

        .cta-button:hover {
            background: #5568d3;
        }

        .warning {
            background: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 15px;
            margin: 20px 0;
        }

        .footer {
            background: #f8f9fa;
            padding: 20px;
            text-align: center;
            font-size: 12px;
            color: #666;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="header">
            <h1>🏃 Invitation à rejoindre une équipe</h1>
        </div>

        <div class="content">
            <p>Bonjour,</p>

            <p><strong>{{ $inviter->name }}</strong> vous invite à rejoindre son équipe pour participer à la course
                suivante :</p>

            <div class="race-info">
                <h2>{{ $race->race_name }}</h2>
                <p><strong>📅 Date :</strong>
                    {{ $race->race_date_start ? \Carbon\Carbon::parse($race->race_date_start)->format('d/m/Y à H:i') : 'À définir' }}
                </p>
                @if($race->raid)
                    <p><strong>📍 Lieu :</strong> {{ $race->raid->raid_location ?? $race->raid->raid_city }}</p>
                @endif
                @if($race->race_description)
                    <p><strong>📝 Description :</strong> {{ Str::limit($race->race_description, 150) }}</p>
                @endif
            </div>

            <p>Pour accepter cette invitation et rejoindre l'équipe, cliquez sur le bouton ci-dessous :</p>

            <div style="text-align: center;">
                <a href="{{ $acceptUrl }}" class="cta-button">
                    ✅ Accepter l'invitation
                </a>
            </div>

            <div class="warning">
                <p><strong>⏰ Attention :</strong> Cette invitation expire le <strong>{{ $expiresAt }}</strong>.</p>
                <p>Si vous ne répondez pas avant cette date, vous serez automatiquement retiré de l'équipe.</p>
            </div>

            <p>Si vous n'avez pas de compte, vous pourrez en créer un directement en cliquant sur le lien ci-dessus.</p>

            <p>À bientôt sur la ligne de départ ! 🏁</p>
        </div>

        <div class="footer">
            <p>Cet email a été envoyé automatiquement. Merci de ne pas y répondre.</p>
            <p>Si vous pensez avoir reçu cet email par erreur, vous pouvez l'ignorer.</p>
        </div>
    </div>
</body>

</html>