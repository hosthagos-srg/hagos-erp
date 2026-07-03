<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$view = view('penjualan.index', ['pesanans' => \App\Models\PenjualanHeader::paginate(50), 'warningCount' => 0, 'tab' => 'semua'])->render();

$matches = [];
preg_match_all("/openTesterModal\(.*?\)/", $view, $matches);
foreach ($matches[0] as $match) {
    echo $match . "\n";
}
