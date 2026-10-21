<?php

use App\Models\User;
use App\Support\PermissionCatalog;
use Database\Seeders\CategorySeeder;
use Database\Seeders\ExerciseSeeder;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

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

Artisan::command('permissions:sync', function () {
    DB::transaction(function () {
        $created = collect(PermissionCatalog::names())
            ->reject(fn (string $name) => Permission::where('name', $name)->where('guard_name', 'web')->exists())
            ->each(fn (string $name) => Permission::create(['name' => $name, 'guard_name' => 'web']));

        // A permission no code checks any more would only be a checkbox that does nothing
        Permission::where('guard_name', 'web')->whereNotIn('name', PermissionCatalog::names())->delete();

        Role::firstOrCreate(['name' => PermissionCatalog::ROOT, 'guard_name' => 'web']);
        $default = Role::firstOrCreate(['name' => PermissionCatalog::DEFAULT_ROLE, 'guard_name' => 'web']);

        // A new feature works for everyone by default; roles made on the root page opt in by hand
        $default->givePermissionTo($created->all());

        User::doesntHave('roles')->chunkById(200, function ($users) {
            $users->each->assignRole(PermissionCatalog::DEFAULT_ROLE);
        });
    });

    app(PermissionRegistrar::class)->forgetCachedPermissions();

    $this->info('Permissions and roles are in sync with the catalog.');
})->purpose('Make the permissions and the root and sportiv roles match the permission catalog');
