<?php
namespace App\Http\Resources;
use Illuminate\Http\Resources\Json\JsonResource;
class PublicBookingResource extends JsonResource
{
    public function toArray($request): array
    {
        return ['booking_code' => $this->booking_code, 'status' => $this->status, 'booking_date' => $this->booking_date?->toDateString(), 'slot_time' => $this->slot_time, 'plate_number' => $this->masked($this->plate_number), 'brand' => $this->brand, 'model' => $this->model, 'staff_note' => $this->staff_note, 'tracking_code' => $this->workOrder?->tracking_code];
    }
    private function masked(string $plate): string { return strlen($plate) <= 4 ? $plate : substr($plate, 0, 2).str_repeat('*', strlen($plate) - 4).substr($plate, -2); }
}
