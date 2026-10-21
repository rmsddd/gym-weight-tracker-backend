<?php

namespace Tests\Unit;

use App\Support\PermissionCatalog;
use PHPUnit\Framework\TestCase;

class PermissionCatalogTest extends TestCase
{
    public function test_names_are_unique_and_follow_area_dot_action(): void
    {
        $names = PermissionCatalog::names();

        $this->assertSame($names, array_values(array_unique($names)));

        foreach ($names as $name) {
            $this->assertMatchesRegularExpression('/^[a-z-]+\.[a-z-]+$/', $name);
        }
    }

    public function test_each_permission_sits_in_the_group_named_by_its_prefix(): void
    {
        foreach (PermissionCatalog::GROUPS as $area => $group) {
            foreach (array_keys($group['permissions']) as $name) {
                $this->assertStringStartsWith("{$area}.", $name);
            }
        }
    }

    public function test_has_only_knows_catalog_names(): void
    {
        $this->assertTrue(PermissionCatalog::has('workouts.delete'));
        $this->assertFalse(PermissionCatalog::has('delete'));
    }
}
