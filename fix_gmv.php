<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$headers = \App\Models\PenjualanHeader::where('gmv_kotor', '<', 1000)->get();
foreach ($headers as $p) {
    if ($p->gmv_kotor > 0) {
        $p->gmv_kotor = $p->gmv_kotor * 1000;
        $p->save();
        echo "Fixed {$p->external_order_id} to {$p->gmv_kotor}\n";
    }
}
echo "Done.\n";
