<?php

namespace App\Services;

use App\Models\Card;

class CardService
{
    public function createCard(CardResponse $cardData)
    {
        $newCard = new Card();
        $newCard->card_name = $cardData->card_name;
        $newCard->card_number = $cardData->card_number;
        $newCard->cvv = $cardData->cvv;
        $newCard->card_type = $cardData->card_type;
        $newCard->expiry_date = $cardData->expiry_date;
        return $newCard->save();
    }

    public function createCards($cardsToInsert)
    {
        DB::table('cards')->insert($cardsToInsert);
    }
}
