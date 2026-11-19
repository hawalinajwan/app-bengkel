<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\MechanicRequest;
use App\Http\Resources\MechanicResource;
use App\Models\Mechanic;

class MechanicController extends MasterController
{
    protected function modelClass(): string
    {
        return Mechanic::class;
    }

    protected function resourceClass(): string
    {
        return MechanicResource::class;
    }

    protected function searchColumns(): array
    {
        return ['name', 'phone'];
    }

    public function store(MechanicRequest $request)
    {
        return new MechanicResource(Mechanic::create($request->validated()));
    }

    public function update(MechanicRequest $request, string $id)
    {
        $mechanic = $this->find($id);
        $mechanic->update($request->validated());

        return new MechanicResource($mechanic);
    }
}
