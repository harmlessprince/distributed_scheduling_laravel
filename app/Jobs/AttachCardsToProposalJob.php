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
        $pending =  true;
        while ($pending) {

           $pending = DB::transaction(function () {

                $proposals = $this->proposalService->findAllByStatusOrderByCreatedAtAsc(status: 'ELIGIBLE', limit: 50);
                if ($proposals->isEmpty()) {
                    return false;
                }
                $proposals->each(function (Proposal $proposal) {

                    $cardData = $this->cardClientService->findCardsByProposalId(proposalId: $proposal->id);
                    $newCard = $this->cardService->createCard($cardData);
                    $proposal->update([
                        'status' => 'ELIGIBLE_WITH_ATTACHED_CARD',
                        'card_id' => $newCard->id,
                    ]);

                });
                return false;
            });
        }


    }

    public function isLeader()
    {
        return false;
    }

}

//kkkw
