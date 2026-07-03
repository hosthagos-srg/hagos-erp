<?php
$zip = new ZipArchive;
$res = $zip->open('File marketplace/Pesanan Shopee/Order.all.20260501_20260531 (1).xlsx');
if ($res === TRUE) {
    $xml = simplexml_load_string($zip->getFromName('xl/sharedStrings.xml'));
    $strings = [];
    foreach($xml->si as $si) {
        $strings[] = (string)$si->t;
    }
    // Asumsikan 50 string pertama adalah header
    print_r(array_slice($strings, 0, 50));
    $zip->close();
} else {
    echo 'failed';
}
