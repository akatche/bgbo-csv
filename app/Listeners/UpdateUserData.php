<?php

namespace App\Listeners;

use App\Events\CustomerHasBeenProcessed;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class UpdateUserData
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(CustomerHasBeenProcessed $event): void
    {
        //
    }
}
