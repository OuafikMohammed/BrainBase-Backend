<?php

namespace App\Notifications;

use App\Models\Collection;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;

abstract class CollectionNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $collection;
    protected $sender;

    public function __construct(Collection $collection, User $sender)
    {
        $this->collection = $collection;
        $this->sender = $sender;
    }

    public function via($notifiable): array
    {
        return ['database', 'broadcast'];
    }

    public function toBroadcast($notifiable): BroadcastMessage
    {
        return new BroadcastMessage($this->toArray($notifiable));
    }

    protected function getBaseData(): array
    {
        return [
            'collection_id' => $this->collection->id,
            'collection_name' => $this->collection->name,
            'sender' => [
                'id' => $this->sender->id_profile,
                'name' => $this->sender->name,
                'profile_image' => $this->sender->profile_image ?? '/placeholder-user.jpg'
            ],
            'timestamp' => now()
        ];
    }
}
