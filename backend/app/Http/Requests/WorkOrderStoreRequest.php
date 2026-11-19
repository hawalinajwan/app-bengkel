<?php
namespace App\Http\Requests;
use Illuminate\Foundation\Http\FormRequest;
class WorkOrderStoreRequest extends FormRequest { public function authorize(): bool{return true;} public function rules(): array{return ['vehicle_id'=>'required|exists:vehicles,id','complaint'=>'nullable|string'];} }
