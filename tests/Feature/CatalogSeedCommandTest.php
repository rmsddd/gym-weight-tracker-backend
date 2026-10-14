<?php

namespace Tests\Feature;

use App\Models\Exercise;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class CatalogSeedCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_seeds_the_catalog_on_first_run(): void
    {
        $this->artisan('catalog:seed')->assertSuccessful();

        $this->assertTrue(Exercise::whereNull('user_id')->where('name', 'Barbell Bench Press')->exists());
        $this->assertNotNull(Cache::store('database')->get('catalog_seed_hash'));
    }

    public function test_it_skips_the_seeders_when_they_have_not_changed(): void
    {
        $this->artisan('catalog:seed')->assertSuccessful();
        Exercise::whereNull('user_id')->where('name', 'Plank')->delete();

        $this->artisan('catalog:seed')
            ->expectsOutput('Catalog is up to date, skipping the seeders.')
            ->assertSuccessful();

        $this->assertFalse(Exercise::whereNull('user_id')->where('name', 'Plank')->exists());
    }

    public function test_it_reseeds_when_the_seeders_have_changed_or_when_forced(): void
    {
        $this->artisan('catalog:seed')->assertSuccessful();
        Exercise::whereNull('user_id')->where('name', 'Plank')->delete();

        $this->artisan('catalog:seed', ['--always' => true])->assertSuccessful();
        $this->assertTrue(Exercise::whereNull('user_id')->where('name', 'Plank')->exists());

        Exercise::whereNull('user_id')->where('name', 'Plank')->delete();
        Cache::store('database')->forever('catalog_seed_hash', 'an-older-version');

        $this->artisan('catalog:seed')->assertSuccessful();
        $this->assertTrue(Exercise::whereNull('user_id')->where('name', 'Plank')->exists());
    }
}
