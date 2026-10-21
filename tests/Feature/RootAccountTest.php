<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\PermissionCatalog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class RootAccountTest extends TestCase
{
    use RefreshDatabase;

    public function test_without_a_password_no_root_account_is_made(): void
    {
        config(['app.root_password' => null]);

        $this->artisan('root:ensure')->assertSuccessful();

        $this->assertFalse(User::where('username', 'root')->exists());
    }

    public function test_it_creates_the_root_account_once(): void
    {
        config(['app.root_password' => 'root123']);

        $this->artisan('root:ensure')->expectsOutput('Root account created.');
        $this->artisan('root:ensure')->expectsOutput('Root account already exists.');

        $root = User::where('username', 'root')->sole();
        $this->assertSame([PermissionCatalog::ROOT], $root->getRoleNames()->all());
        $this->assertTrue(Hash::check('root123', $root->password));
        $this->assertNotNull($root->email_verified_at);
    }

    public function test_an_existing_root_keeps_its_password(): void
    {
        $root = User::factory()->create(['username' => 'root']);
        config(['app.root_password' => 'something-else']);

        $this->artisan('root:ensure')->assertSuccessful();

        $root->refresh();
        $this->assertTrue(Hash::check('password', $root->password));
        $this->assertTrue($root->hasRole(PermissionCatalog::ROOT));
    }
}
