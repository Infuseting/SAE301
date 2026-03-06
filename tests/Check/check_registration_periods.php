<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

echo "\n" . str_repeat("=", 80) . "\n";
echo "VERIFICATION DES PERIODES D'INSCRIPTION\n";
echo str_repeat("=", 80) . "\n\n";

$raids = DB::table('raids')
    ->whereBetween('raid_id', [600, 700])
    ->get();

foreach ($raids as $raid) {
    $period = DB::table('registration_period')
        ->where('ins_id', $raid->ins_id)
        ->first();
    
    echo "Raid: {$raid->raid_name}\n";
    echo "  📅 Dates du raid: {$raid->raid_date_start} → {$raid->raid_date_end}\n";
    
    if ($period) {
        echo "  ✍️  Inscriptions: {$period->ins_start_date} → {$period->ins_end_date}\n";
        
        // Vérification selon CSV
        if ($raid->raid_name === 'Raid CHAMPETRE') {
            $expectedStart = '2025-08-10';
            $expectedEnd = '2025-10-30';
            $startMatch = str_starts_with($period->ins_start_date, $expectedStart);
            $endMatch = str_starts_with($period->ins_end_date, $expectedEnd);
            
            if ($startMatch && $endMatch) {
                echo "  ✅ Conforme au CSV (10/08/2025 - 30/10/2025)\n";
            } else {
                echo "  ❌ Non conforme ! Attendu: 10/08/2025 - 30/10/2025\n";
            }
        } elseif ($raid->raid_name === "Raid O'Bivwak") {
            $expectedStart = '2026-01-09';
            $expectedEnd = '2026-04-30';
            $startMatch = str_starts_with($period->ins_start_date, $expectedStart);
            $endMatch = str_starts_with($period->ins_end_date, $expectedEnd);
            
            if ($startMatch && $endMatch) {
                echo "  ✅ Conforme au CSV (09/01/2026 - 30/04/2026)\n";
            } else {
                echo "  ❌ Non conforme ! Attendu: 09/01/2026 - 30/04/2026\n";
            }
        }
    } else {
        echo "  ❌ Aucune période d'inscription trouvée !\n";
    }
    
    echo "\n";
}

echo str_repeat("=", 80) . "\n";
