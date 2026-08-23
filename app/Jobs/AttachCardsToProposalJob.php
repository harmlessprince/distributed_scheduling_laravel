<?php

namespace App\Jobs;

use App\Models\Proposal;
use App\Services\CardClientService;
use App\Services\CardService;
use App\Services\ProposalService;
use Illuminate\Support\Facades\DB;

class AttachCardsToProposalJob
{
    public function __construct(
        private CardClientService $cardClientService,
        private CardService       $cardService,
        private ProposalService   $proposalService,
    )
    {
    }


    public function __invoke(): void
    {
        while (true) {
            $processedCount = DB::transaction(function () {
                $proposals = $this->proposalService->findAllByStatusOrderByCreatedAtAsc(status: 'ELIGIBLE', limit: 50);
                if ($proposals->isEmpty()) return 0;
                $proposalCardMap = []; // proposal_id => card_id
                $cardsToInsert = [];
                foreach ($proposals as $proposal) {
                    $cardData = $this->cardClientService->findCardsByProposalId(proposalId: $proposal->id);
                    $proposalCardMap[$proposal->id] = $cardData->id;
                    $cardsToInsert[] = $cardData;
                }
                if (!empty($cardsToInsert)) $this->cardService->createCards($cardsToInsert);
                if (!empty($proposalCardMap)) {
                    $caseSql = "CASE ";
                    foreach ($proposalCardMap as $proposalId => $cardId) {
                        $caseSql .= "WHEN id = $proposalId THEN '$cardId' ";
                    }
                    $caseSql .= "END";
                    DB::table('proposals')
                        ->whereIn('id', array_keys($proposalCardMap))
                        ->update([
                            'card_id' => DB::raw($caseSql),
                            'status' => 'ELIGIBLE_WITH_ATTACHED_CARD',
                            'updated_at' => now(),
                        ]);

                }
                return count($proposals);
            });
            if ($processedCount == 0) break;
        }
    }

    public function isLeader()
    {
        return false;
    }

}

//kkkw
