<?php

use App\Jobs\TestJob;
use Illuminate\Support\Facades\Schedule;

Schedule::call(new TestJob)->daily();
