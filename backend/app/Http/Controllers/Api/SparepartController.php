<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\SparepartRequest;
use App\Http\Resources\SparepartResource;
use App\Models\Sparepart;
use Illuminate\Http\Request;

class SparepartController extends MasterController
{
    protected function modelClass(): string
    {
        return Sparepart::class;
    }

    protected function resourceClass(): string
    {
        return SparepartResource::class;
    }

    protected function searchColumns(): array
    {
        return ['sku', 'name'];
    }

    protected function applyFilters($query, Request $request): void
    {
        if ($request->boolean('low_stock')) {
            $query->whereColumn('stock', '<=', 'min_stock');
        }
    }

    public function store(SparepartRequest $request)
    {
        return new SparepartResource(Sparepart::create($request->validated()));
    }

    public function update(SparepartRequest $request, string $id)
    {
        $sparepart = $this->find($id);
        $sparepart->update($request->validated());

        return new SparepartResource($sparepart);
    }
}
