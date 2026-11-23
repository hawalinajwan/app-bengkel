<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class PublicTrackingResource extends JsonResource
{
    public function toArray($request): array
    {
        $status = $this->status->value;
        $plate = $this->vehicle->plate_number;
        $finishAt = null;

        if ($status === 'dikerjakan' && $this->started_at) {
            $minutes = $this->serviceLines->sum(fn ($line) => $line->service->est_minutes);
            $finishAt = $this->started_at->copy()->addMinutes($minutes)->toIso8601String();
        }

        return [
            'queue_number' => $this->queue_number,
            'status' => $status,
            'timeline' => $this->statusLogs
                ->map(fn ($log) => ['status' => $log->to_status, 'at' => $log->created_at?->toIso8601String()])
                ->values(),
            'vehicle' => [
                'brand' => $this->vehicle->brand,
                'model' => $this->vehicle->model,
                'plate_masked' => $this->masked($plate),
            ],
            'public_note' => $this->public_note,
            'services' => $this->serviceLines->map(fn ($line) => $line->service->name)->values(),
            'parts' => $this->partLines
                ->map(fn ($line) => ['name' => $line->sparepart->name, 'qty' => $line->qty])
                ->values(),
            'estimated_finish_at' => $finishAt,
            'total' => in_array($status, ['selesai', 'dibayar'], true) ? $this->total : null,
        ];
    }

    private function masked(string $plate): string
    {
        if (strlen($plate) <= 4) {
            return $plate;
        }

        return substr($plate, 0, 2).str_repeat('*', max(0, strlen($plate) - 4)).substr($plate, -2);
    }
}
