<?php

namespace Database\Seeders;

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
        // Seed users (including admin)
        $this->call([
            UserSeeder::class,
        ]);

        // Seed projects and logs
        $this->call([
            ProjectSeeder::class,
        ]);
    }
}
