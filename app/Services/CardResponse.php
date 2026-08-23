<?php

namespace App\Services;

class CardResponse
{
    public int $id;
    public int $proposal_id;
    public string $card_name;
    public string $card_number;
    public string $cvv;
    public string $expiry_date;
    public string $card_type;

    public function __construct()
    {
        $this->id = 1;
        $this->proposal_id = 1001;
        $this->card_name = "Taofeeq";
        $this->card_number = "10011001100193993";
        $this->cvv = "093";
        $this->expiry_date = "12/25";
        $this->card_type = "MASTERCARD";
    }
}
