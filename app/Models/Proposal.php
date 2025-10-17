<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Proposal extends Model
{
    public function card()
    {
        return $this->hasOne(Card::class);
    }
}
