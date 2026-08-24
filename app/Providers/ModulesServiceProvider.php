<?php

namespace App\Providers;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class ModulesServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $modulesPath = app_path('Modules');

        if (! File::isDirectory($modulesPath)) {
            return;
        }

        foreach (File::directories($modulesPath) as $modulePath) {
            $routesPath = $modulePath.'/Routes/api.php';

            if (File::exists($routesPath)) {
                Route::middleware('api')
                    ->prefix('api')
                    ->group($routesPath);
            }
        }
    }
}
