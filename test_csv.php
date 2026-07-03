<?php
$f = fopen("Seed Data/HAGOS_Master_Data (3) - M1_Bibit (1).csv", "r");
fgetcsv($f); fgetcsv($f); fgetcsv($f);
$header = fgetcsv($f);
$header = array_map(function($col) {
    $col = preg_replace('/^\xEF\xBB\xBF/', '', $col);
    return trim($col);
}, $header);
print_r($header);

$row = fgetcsv($f);
print_r($row);

$rowData = [];
foreach ($header as $index => $colName) {
    if (empty($colName)) continue;
    $val = isset($row[$index]) ? trim($row[$index]) : null;
    $rowData[$colName] = $val === '' ? null : $val;
}
print_r($rowData);
