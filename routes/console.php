<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('app:process-subscriptions')->daily();
Schedule::command('app:check-grace-period')->daily();
Schedule::command('app:check-expired-licenses')->daily();
