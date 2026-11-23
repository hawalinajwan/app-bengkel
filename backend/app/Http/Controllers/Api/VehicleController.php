<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\VehicleRequest;
use App\Http\Resources\VehicleResource;
use App\Models\Vehicle;
use Illuminate\Http\Request;

class VehicleController extends MasterController
{
    protected function modelClass(): string
    {
        return Vehicle::class;
    }

    protected function resourceClass(): string
    {
        return VehicleResource::class;
    }

    protected function searchColumns(): array
    {
        return ['plate_number', 'brand', 'model'];
    }

    protected function eagerLoad(): array
    {
        return ['customer'];
    }

    protected function applyFilters($query, Request $request): void
    {
        $query->when($request->query('customer_id'), fn ($q, $id) => $q->where('customer_id', $id));
    }

    public function store(VehicleRequest $request)
    {
        return new VehicleResource(Vehicle::create($request->validated())->load('customer'));
    }

    public function show(string $id)
    {
        return new VehicleResource($this->find($id)->load('customer'));
    }

    public function update(VehicleRequest $request, string $id)
    {
        $vehicle = $this->find($id);
        $vehicle->update($request->validated());

        return new VehicleResource($vehicle->load('customer'));
    }

    public function history(string $id)
    {
        $vehicle = $this->find($id)->load('customer');
        $orders = $vehicle->workOrders()
            ->with(['serviceLines.service', 'partLines.sparepart'])
            ->latest('queue_date')
            ->latest('id')
            ->get();

        return response()->json([
            'data' => [
                'vehicle' => new VehicleResource($vehicle),
                'orders' => $orders->map(fn ($wo) => [
                    'id' => $wo->id,
                    'queue_date' => $wo->queue_date?->toDateString(),
                    'queue_number' => str_pad((string) $wo->queue_number, 3, '0', STR_PAD_LEFT),
                    'status' => $wo->status->value,
                    'complaint' => $wo->complaint,
                    'finished_at' => $wo->finished_at?->toIso8601String(),
                    'total' => $wo->total,
                    'services' => $wo->serviceLines->map(fn ($line) => $line->service->name)->values(),
                    'parts' => $wo->partLines->map(fn ($line) => ['name' => $line->sparepart->name, 'qty' => $line->qty])->values(),
                ]),
            ],
        ]);
    }
}
