<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class NotificationLogResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'channel' => $this->channel,
            'recipient' => $this->recipient,
            'status' => $this->status,
            'error' => $this->error,
            'sent_at' => $this->sent_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
            'work_order_id' => $this->work_order_id,
            'booking_id' => $this->booking_id,
        ];
    }
}
