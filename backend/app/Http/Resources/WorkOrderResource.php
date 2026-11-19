<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class WorkOrderResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'queue_number' => $this->queue_number,
            'queue_number_display' => str_pad((string) $this->queue_number, 3, '0', STR_PAD_LEFT),
            'queue_date' => $this->queue_date?->toDateString(),
            'tracking_code' => $this->tracking_code,
            'status' => $this->status->value,
            'complaint' => $this->complaint,
            'public_note' => $this->public_note,
            'discount' => $this->discount,
            'total' => $this->total,
            'started_at' => $this->started_at?->toIso8601String(),
            'finished_at' => $this->finished_at?->toIso8601String(),
            'vehicle' => $this->whenLoaded('vehicle', fn () => [
                'id' => $this->vehicle->id,
                'plate_number' => $this->vehicle->plate_number,
                'brand' => $this->vehicle->brand,
                'model' => $this->vehicle->model,
                'type' => $this->vehicle->type,
                'customer' => $this->vehicle->relationLoaded('customer') ? [
                    'id' => $this->vehicle->customer->id,
                    'name' => $this->vehicle->customer->name,
                    'phone' => $this->vehicle->customer->phone,
                    'email' => $this->vehicle->customer->email,
                ] : null,
            ]),
            'mechanic' => $this->whenLoaded('mechanic', fn () => $this->mechanic ? [
                'id' => $this->mechanic->id,
                'name' => $this->mechanic->name,
            ] : null),
            'services' => $this->whenLoaded('serviceLines', fn () => $this->serviceLines->map(fn ($line) => [
                'service_id' => $line->service_id,
                'name' => $line->service?->name,
                'price' => $line->price,
            ])),
            'parts' => $this->whenLoaded('partLines', fn () => $this->partLines->map(fn ($line) => [
                'sparepart_id' => $line->sparepart_id,
                'name' => $line->sparepart?->name,
                'qty' => $line->qty,
                'price' => $line->price,
            ])),
            'payments' => $this->whenLoaded('payments'),
            'status_logs' => $this->whenLoaded('statusLogs', fn () => $this->statusLogs->map(fn ($log) => [
                'from_status' => $log->from_status,
                'to_status' => $log->to_status,
                'at' => $log->created_at?->toIso8601String(),
            ])),
        ];
    }
}
