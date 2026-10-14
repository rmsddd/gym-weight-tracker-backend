<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class HealthCheckTest extends TestCase
{
    public function test_health_check_queries_the_database(): void
    {
        DB::enableQueryLog();

        $this->get('/up')->assertOk();

        $this->assertContains('select 1', array_column(DB::getQueryLog(), 'query'));
    }

    public function test_health_check_fails_when_the_database_is_unreachable(): void
    {
        config(['app.debug' => false]);
        DB::shouldReceive('select')->once()->andThrow(new \RuntimeException('Database unreachable'));

        $this->get('/up')->assertStatus(500);
    }
}
