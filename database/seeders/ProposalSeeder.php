<?php

namespace Database\Seeders;

use App\Models\Proposal;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ProposalSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Proposal::truncate();

        foreach (User::all() as $user) {
            Proposal::create([
                'user_id' => $user->id,
                'status'  => 'ELIGIBLE',
            ]);

            Proposal::create([
                'user_id' => $user->id,
                'status'  => 'NOT_ELIGIBLE',
            ]);
        }
    }
}
