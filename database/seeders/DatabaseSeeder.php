<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     *
     * Roles and the admin account are always seeded. The demo catalog is
     * skipped in production so a real deployment is never polluted with it.
     */
    public function run(): void
    {
        $this->call(RoleAndAdminSeeder::class);

        if (!app()->environment('production')) {
            $this->call(DemoDataSeeder::class);
        }
    }
}
