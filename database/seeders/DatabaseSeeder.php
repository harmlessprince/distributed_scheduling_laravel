<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        DB::listen(function ($query) {
            Log::info('SQL:', [$query->sql]);
            Log::info('Bindings:', $query->bindings);
            Log::info('Time:', [$query->time]);
        });
        $this->call([
            UserSeeder::class,
            ProposalSeeder::class,
            CardSeeder::class,
        ]);
    }
}
