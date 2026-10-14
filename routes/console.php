<?php

use Database\Seeders\CategorySeeder;
use Database\Seeders\ExerciseSeeder;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('catalog:seed {--always : Seed even if the seeders have not changed}', function () {
    $seeders = [CategorySeeder::class, ExerciseSeeder::class];

    // A hash of the seeder files: the catalog only needs reseeding when one of them changes
    $hash = sha1(implode('', array_map(
        fn ($seeder) => file_get_contents((new ReflectionClass($seeder))->getFileName()),
        $seeders,
    )));

    // The database store explicitly, so the hash survives container restarts whatever CACHE_STORE is
    $cache = Cache::store('database');

    if (! $this->option('always') && $cache->get('catalog_seed_hash') === $hash) {
        $this->info('Catalog is up to date, skipping the seeders.');

        return;
    }

    foreach ($seeders as $seeder) {
        $this->call('db:seed', ['--class' => $seeder, '--force' => true]);
    }

    $cache->forever('catalog_seed_hash', $hash);
})->purpose('Seed the categories and the shared exercise catalog when their seeders have changed');
