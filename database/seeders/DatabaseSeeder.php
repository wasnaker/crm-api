<?php

namespace Database\Seeders;

use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        User::factory()->create([
            'name' => 'Demo',
            'email' => 'demo@spine.test',
            'password' => Hash::make('password'),
        ]);

        // Create admin role (guard sanctum) and assign to demo user
        $adminRole = Role::firstOrCreate([
            'name' => 'admin',
            'guard_name' => 'sanctum',
        ]);
        $demoUser = User::where('email', 'demo@spine.test')->first();
        if ($demoUser) {
            $demoUser->assignRole($adminRole);
        }
    }
}
