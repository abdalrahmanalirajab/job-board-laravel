<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class NotificationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $sender = null;
        if ($this->sender_id) {
            $sender = [
                'id'     => $this->sender_id,
                'name'   => $this->whenLoaded('sender', fn () => $this->sender->name),
                'avatar' => $this->whenLoaded('sender', fn () => $this->sender->avatar),
            ];
        }

        return [
            'id'         => $this->id,
            'type'       => $this->type,
            'data'       => $this->data,
            'category'   => $this->category,
            'sender'     => $sender,
            'priority'   => $this->priority ?? 'normal',
            'link'       => $this->link,
            'icon'       => $this->icon,
            'metadata'   => $this->metadata,
            'is_read'    => $this->read_at !== null,
            'read_at'    => $this->read_at,
            'created_at' => $this->created_at,
        ];
    }
}
