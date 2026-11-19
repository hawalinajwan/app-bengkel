<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\ServiceRequest;
use App\Http\Resources\ServiceResource;
use App\Models\Service;

class ServiceController extends MasterController
{
    protected function modelClass(): string
    {
        return Service::class;
    }

    protected function resourceClass(): string
    {
        return ServiceResource::class;
    }

    protected function searchColumns(): array
    {
        return ['name'];
    }

    public function store(ServiceRequest $request)
    {
        return new ServiceResource(Service::create($request->validated()));
    }

    public function update(ServiceRequest $request, string $id)
    {
        $service = $this->find($id);
        $service->update($request->validated());

        return new ServiceResource($service);
    }
}
