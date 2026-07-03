<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        //
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Period lock: transaksi di periode terkunci → kembali dengan pesan error (bukan 500).
        $exceptions->render(function (\App\Exceptions\PeriodeTerkunciException $e, $request) {
            return redirect()->back()->withInput()->with('error', $e->getMessage());
        });
    })->create();
