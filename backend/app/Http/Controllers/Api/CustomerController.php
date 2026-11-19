<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\CustomerRequest;
use App\Http\Resources\CustomerResource;
use App\Models\Customer;

class CustomerController extends MasterController
{
    protected function modelClass(): string
    {
        return Customer::class;
    }

    protected function resourceClass(): string
    {
        return CustomerResource::class;
    }

    protected function searchColumns(): array
    {
        return ['name', 'phone', 'email', 'address'];
    }

    public function store(CustomerRequest $request)
    {
        return new CustomerResource(Customer::create($request->validated()));
    }

    public function update(CustomerRequest $request, string $id)
    {
        $customer = $this->find($id);
        $customer->update($request->validated());

        return new CustomerResource($customer);
    }
}
