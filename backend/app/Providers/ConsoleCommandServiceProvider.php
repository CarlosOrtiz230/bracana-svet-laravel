<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Console\Commands\RebuildDockerImages;

class ConsoleCommandServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->commands([
            RebuildDockerImages::class,
        ]);
    }

    public function boot(): void
    {
        //
    }
}
