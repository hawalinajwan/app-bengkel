<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class PublicBookingResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'booking_code' => $this->booking_code,
            'status' => $this->status,
            'booking_date' => $this->booking_date?->toDateString(),
            'slot_time' => $this->slot_time,
            'plate_number' => $this->masked($this->plate_number),
            'brand' => $this->brand,
            'model' => $this->model,
            'dp_amount' => (int) $this->dp_amount,
            'dp_paid' => (bool) $this->dp_paid_at,
            'dp_proof' => (bool) $this->dp_proof_path,
            'dp_method' => $this->dp_method,
            'dp_proof_at' => $this->dp_proof_at?->toIso8601String(),
            'staff_note' => $this->staff_note,
            'tracking_code' => $this->workOrder?->tracking_code,
        ];
    }

    private function masked(string $plate): string
    {
        if (strlen($plate) <= 4) {
            return $plate;
        }

        return substr($plate, 0, 2).str_repeat('*', strlen($plate) - 4).substr($plate, -2);
    }
}
