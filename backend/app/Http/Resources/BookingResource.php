<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class BookingResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'booking_code' => $this->booking_code,
            'customer_name' => $this->customer_name,
            'phone' => $this->phone,
            'email' => $this->email,
            'plate_number' => $this->plate_number,
            'vehicle_type' => $this->vehicle_type,
            'brand' => $this->brand,
            'model' => $this->model,
            'service_id' => $this->service_id,
            'service' => $this->whenLoaded('service', fn () => [
                'id' => $this->service->id,
                'name' => $this->service->name,
            ]),
            'complaint' => $this->complaint,
            'booking_date' => $this->booking_date?->toDateString(),
            'slot_time' => $this->slot_time,
            'dp_amount' => (int) $this->dp_amount,
            'dp_paid_at' => $this->dp_paid_at?->toIso8601String(),
            'dp_method' => $this->dp_method,
            'dp_proof' => (bool) $this->dp_proof_path,
            'dp_proof_at' => $this->dp_proof_at?->toIso8601String(),
            'status' => $this->status,
            'staff_note' => $this->staff_note,
            'work_order_id' => $this->work_order_id,
            'tracking_code' => $this->when($this->workOrder, fn () => $this->workOrder?->tracking_code),
        ];
    }
}
