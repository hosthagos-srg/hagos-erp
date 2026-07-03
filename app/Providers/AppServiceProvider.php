<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Observers\AuditObserver;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Model finansial & stok yang dicatat ke audit log.
     */
    private array $auditedModels = [
        \App\Models\MutasiKas::class,
        \App\Models\PenjualanHeader::class,
        \App\Models\PenjualanDetail::class,
        \App\Models\BelanjaHeader::class,
        \App\Models\BelanjaDetail::class,
        \App\Models\KoreksiStok::class,
        \App\Models\StokJadiLog::class,
        \App\Models\UtangCicilan::class,
        \App\Models\CicilanPembayaran::class,
        \App\Models\Kasbon::class,
        \App\Models\Gaji::class,
        \App\Models\SampelAffiliate::class,
        \App\Models\RekonsiliasiMp::class,
        \App\Models\ProduksiLog::class,
    ];

    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        foreach ($this->auditedModels as $model) {
            $model::observe(AuditObserver::class);
        }
    }
}
