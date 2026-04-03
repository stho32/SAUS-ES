<?php

uses(
    Tests\DuskTestCase::class,
    // Illuminate\Foundation\Testing\DatabaseMigrations::class,
)->in('Browser');

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature', 'Unit');

/**
 * Helper: Create a master link and set it in the session.
 */
function authenticateWithMasterLink(): void
{
    \App\Models\MasterLink::create([
        'link_code' => 'test_master_link',
        'description' => 'Test',
        'is_active' => true,
    ]);

    test()->withSession([
        'master_code' => 'test_master_link',
        'username' => 'TestUser',
    ]);
}

/**
 * Helper: Seed ticket statuses for tests.
 */
function seedStatuses(): void
{
    (new \Database\Seeders\TicketStatusSeeder())->run();
}
