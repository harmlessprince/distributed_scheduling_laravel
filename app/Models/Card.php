<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property string $card_name
 * @property string $card_number
 * @property string $cvv
 * @property string $card_type
 * @property string $expiry_date
 */
class Card extends Model
{
    protected $guarded = [];

    public function proposal()
    {
        return $this->belongsTo(Proposal::class);
    }
}
