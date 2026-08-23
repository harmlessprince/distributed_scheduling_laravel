<?php

namespace App\Services;

use App\Models\Proposal;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProposalService
{
    public function findAllByStatusOrderByCreatedAtAsc($status, $limit)
    {
        $proposals = Proposal::query()->where('status', '=', $status)
            ->orderBy('created_at', 'asc')
            ->lock('FOR UPDATE SKIP LOCKED')
            ->limit($limit);
        return $proposals->get();
    }
}



