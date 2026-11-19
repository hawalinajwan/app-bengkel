<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class VehicleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->filled('plate_number')) {
            $this->merge([
                'plate_number' => Str::upper(preg_replace('/\s+/', '', (string) $this->input('plate_number'))),
            ]);
        }
    }

    public function rules(): array
    {
        return [
            'customer_id' => ['required', 'integer', 'exists:customers,id'],
            'plate_number' => [
                'required', 'string', 'max:15',
                Rule::unique('vehicles', 'plate_number')->ignore($this->route('vehicle')),
            ],
            'type' => ['required', 'in:motor,mobil'],
            'brand' => ['required', 'string', 'max:255'],
            'model' => ['required', 'string', 'max:255'],
            'year' => ['nullable', 'integer', 'between:1900,'.(now()->year + 1)],
        ];
    }
}
