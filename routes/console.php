<?php

use Illuminate\Support\Facades\Artisan;

Artisan::command('about:nso', function (): void {
    $this->info('Ninja School Blue Laravel application.');
})->purpose('Show NSO project information');
