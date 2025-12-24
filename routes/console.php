<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('monitor:websites')->everyMinute();
