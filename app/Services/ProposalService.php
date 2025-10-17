<?php

namespace App\Services;

use App\Models\Proposal;
use Illuminate\Support\Facades\Log;

class ProposalService
{
    public function findAllByStatusOrderByCreatedAtAsc($status)
    {
         $proposals = Proposal::query()->where('status', '=', $status)
            ->orderBy('created_at', 'asc');
         return $proposals->get();
    }
}
