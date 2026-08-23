<?php

use App\Jobs\AttachCardsToProposalJob;
use App\Jobs\TestJob;
use Illuminate\Support\Facades\Schedule;


Schedule::call(new TestJob);

Schedule::call(AttachCardsToProposalJob::class)->everyMinute();


















//Schedule::call(new AttachCardsToProposalJob)->daily();
