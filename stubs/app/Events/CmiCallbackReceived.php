<?php

namespace App\Events;

use App\ValueObject\CmiCallbackData;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CmiCallbackReceived
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly CmiCallbackData $data,
    ) {}
}
