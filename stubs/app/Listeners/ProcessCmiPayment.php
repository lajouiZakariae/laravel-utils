<?php

namespace App\Listeners;

use App\Events\CmiCallbackReceived;
use Illuminate\Contracts\Queue\ShouldQueue;

class ProcessCmiPayment implements ShouldQueue
{
    public function handle(CmiCallbackReceived $event): void {}
}
