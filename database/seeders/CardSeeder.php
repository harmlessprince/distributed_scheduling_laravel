<?php

namespace Database\Seeders;

use App\Models\Card;
use App\Models\Proposal;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Random\RandomException;

class CardSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * @throws RandomException
     */
    public function run(): void
    {
        Card::truncate();
        $eligibleProposals = Proposal::where('status', 'ELIGIBLE')->get();
        foreach ($eligibleProposals as $proposal) {
            $newCard = new Card([
                'card_name' => 'John Doe',
                'card_number' => '5260-9991-9040-' . random_int(1000, 9999),
                'cvv' => '123',
                'expiry_date' => '2028-05',
                'card_type' => 'Visa',
            ]);
            $newCard->save();

            $proposal->update([
                'status' => 'ELIGIBLE_WITH_ATTACHED_CARD',
                'card_id' => $newCard->id,
            ]);
        }
    }
}
