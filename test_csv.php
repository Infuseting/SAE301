<?php

// Test CSV header parsing
$csvContent = "CLT;PUCE;EQUIPE;CATÉGORIE;TEMPS;PTS
11;7000586;GELEMENTALMAISPASLESCUISSES;Masculin;-6:06:12;180";

$lines = explode("\n", $csvContent);
$header = str_getcsv($lines[0], ';');

echo "Raw header:\n";
print_r($header);

echo "\nNormalized header:\n";
$normalizedHeader = array_map(function ($col) {
    $col = trim($col);
    $col = preg_replace('/^\xEF\xBB\xBF/', '', $col);
    $col = strtolower($col);
    $col = str_replace('é', 'e', $col);
    
    $mapping = [
        'clt' => 'clt',
        'puce' => 'puce',
        'equipe' => 'equipe',
        'categorie' => 'category',
        'temps' => 'temps',
        'pts' => 'points',
        'points' => 'points',
    ];
    return $mapping[$col] ?? $col;
}, $header);

print_r($normalizedHeader);

// Test with first data row
$row = str_getcsv($lines[1], ';');
echo "\nData row:\n";
print_r($row);

$combined = array_combine($normalizedHeader, $row);
echo "\nCombined data:\n";
print_r($combined);

echo "\nCategory value: " . ($combined['category'] ?? 'NULL') . "\n";
