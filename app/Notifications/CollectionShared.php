<?php

namespace App\Notifications;

use App\Models\Collection;
use App\Models\User;
use Illuminate\Notifications\Messages\MailMessage;

class CollectionShared extends CollectionNotification
{
    protected $permission;

    public function __construct(Collection $collection, User $sender, string $permission)
    {
        parent::__construct($collection, $sender);
        $this->permission = $permission;
    }

    public function toMail($notifiable): MailMessage
    {
        $roleText = match($this->permission) {
            'admin' => 'admin (full control)',
            'edit' => 'editor (can add and remove PDFs)',
            default => 'viewer (can view and download)'
        };

        return (new MailMessage)
            ->subject("{$this->sender->name} shared a collection with you")
            ->greeting("Hello {$notifiable->name}!")
            ->line("{$this->sender->name} has shared the collection '{$this->collection->name}' with you.")
            ->line("You have been granted {$roleText} access.")
            ->action('View Collection', url("/collections/{$this->collection->id}"))
            ->line('Thank you for using BraineBase!');
    }

    public function toArray($notifiable): array
    {
        return array_merge($this->getBaseData(), [
            'type' => 'collection_shared',
            'permission' => $this->permission,
            'message' => "{$this->sender->name} shared the collection '{$this->collection->name}' with you"
        ]);
    }
}
