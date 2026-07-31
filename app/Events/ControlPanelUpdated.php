<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ControlPanelUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public array $data;

    public function __construct(array $data)
    {
        // Pasamos la data limpia que necesita la TV
        $this->data = $data;
    }

    public function broadcastOn(): array
    {
        return [
            new Channel('sala-control'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'tv.refresh';
    }
}