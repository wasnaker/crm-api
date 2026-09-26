<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spine\Database\Seeders\AdminUserSeeder;

/**
 * Host-level seed entry point.
 *
 * All Spine domain logic lives in the package (spine/laravel-spine), so this
 * class only orchestrates and stays free of model or schema knowledge.
 *
 * Credentials come from the environment — see Spine\Database\Seeders\AdminUserSeeder.
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(AdminUserSeeder::class);
    }
}
