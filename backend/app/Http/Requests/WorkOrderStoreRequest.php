<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class WorkOrderStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $plate = $this->input('vehicle.plate_number');
        if (filled($plate)) {
            $this->merge([
                'vehicle' => array_merge($this->input('vehicle', []), [
                    'plate_number' => Str::upper(preg_replace('/\s+/', '', (string) $plate)),
                ]),
            ]);
        }
    }

    public function rules(): array
    {
        $walkIn = fn (array $rule) => array_merge(['required_without:vehicle_id'], $rule);

        return [
            'vehicle_id' => ['nullable', 'required_without:vehicle.plate_number', 'exists:vehicles,id'],
            'complaint' => ['required', 'string', 'max:1000'],
            'customer.name' => $walkIn(['string', 'max:255']),
            'customer.phone' => ['nullable', 'string', 'max:20'],
            'customer.email' => ['nullable', 'email', 'max:255'],
            'vehicle.plate_number' => [
                'required_without:vehicle_id', 'string', 'max:15',
                Rule::unique('vehicles', 'plate_number'),
            ],
            'vehicle.brand' => $walkIn(['string', 'max:255']),
            'vehicle.model' => $walkIn(['string', 'max:255']),
            'vehicle.year' => ['nullable', 'integer', 'between:1900,'.(now()->year + 1)],
        ];
    }

    public function messages(): array
    {
        return [
            'vehicle.plate_number.unique' => 'Plat nomor sudah terdaftar. Pilih kendaraan lama atau cek data kendaraan.',
            'vehicle_id.required_without' => 'Pilih kendaraan lama atau isi data kendaraan baru.',
            'customer.name.required_without' => 'Nama pelanggan wajib diisi untuk pelanggan baru.',
        ];
    }
}
