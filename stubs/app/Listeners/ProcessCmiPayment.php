<?php

namespace App\Listeners;

use App\Events\CmiCallbackReceived;
use Illuminate\Contracts\Queue\ShouldQueue;

class ProcessCmiPayment implements ShouldQueue
{
    public function handle(CmiCallbackReceived $event): void
    {
        // TODO: Implement the logic to process the payment based on the callback data.
        // You can access the callback data via $event->data, which is an instance of CmiCallbackData.
    }
}
