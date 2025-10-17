<?php

namespace App\Jobs;

use App\Models\Proposal;
use App\Services\CardClientService;
use App\Services\CardService;
use App\Services\ProposalService;

    class AttachCardsToProposalJob
    {
        public function __construct(
            private CardClientService $cardClientService,
            private CardService       $cardService,
            private ProposalService   $proposalService,
        ){}


        public function __invoke(): void
        {

            $proposals = $this->proposalService->findAllByStatusOrderByCreatedAtAsc(status: 'ELIGIBLE');

            $proposals->each(function (Proposal $proposal) {

                $cardData = $this->cardClientService->findCardsByProposalId(proposalId: $proposal->id);

                $newCard = $this->cardService->createCard($cardData);

                $proposal->update([
                    'status' => 'ELIGIBLE_WITH_ATTACHED_CARD',
                    'card_id' => $newCard->id,
                ]);

            });
        }

    }

//kkkw
