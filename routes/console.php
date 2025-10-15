<?php

use App\Console\Commands\SendDailyReport;
use App\Console\Tasks\TestTask;
use Illuminate\Support\Facades\Schedule;

Schedule::call(new TestTask)->daily();
