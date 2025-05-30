<?php

namespace App\Notifications;

use App\Models\Collection;
use App\Models\User;
use Illuminate\Notifications\Messages\MailMessage;

class CollectionRoleUpdated extends CollectionNotification
{
    protected $newPermission;
    protected $oldPermission;

    public function __construct(Collection $collection, User $sender, string $newPermission, string $oldPermission)
    {
        parent::__construct($collection, $sender);
        $this->newPermission = $newPermission;
        $this->oldPermission = $oldPermission;
    }

    public function toMail($notifiable): MailMessage
    {
        $roleText = match($this->newPermission) {
            'admin' => 'an administrator (full control)',
            'edit' => 'an editor (can add and remove PDFs)',
            default => 'a viewer (can view and download)'
        };

        return (new MailMessage)
            ->subject("Your role was updated in {$this->collection->name}")
            ->greeting("Hello {$notifiable->name}!")
            ->line("{$this->sender->name} has updated your role in the collection '{$this->collection->name}'.")
            ->line("You are now {$roleText}.")
            ->action('View Collection', url("/collections/{$this->collection->id}"))
            ->line('Thank you for using BraineBase!');
    }

    public function toArray($notifiable): array
    {
        return array_merge($this->getBaseData(), [
            'type' => 'collection_role_updated',
            'old_permission' => $this->oldPermission,
            'new_permission' => $this->newPermission,
            'message' => "{$this->sender->name} changed your role in the collection '{$this->collection->name}'"
        ]);
    }
}
