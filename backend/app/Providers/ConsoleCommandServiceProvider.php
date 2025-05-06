<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Console\Commands\RebuildScannerImages;

class ConsoleCommandServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->commands([
            RebuildScannerImages::class,
        ]);
    }

    public function boot(): void
    {
        //
    }
}
