<?php
namespace App\Http\Requests;
use Illuminate\Foundation\Http\FormRequest;
class BookingRequest extends FormRequest
{
    public function authorize(): bool { return true; }
    public function rules(): array { return ['customer_name'=>'required|string|max:255','phone'=>'required|string|max:30','email'=>'nullable|email|max:255','plate_number'=>'required|string|max:15','vehicle_type'=>'required|in:motor,mobil','brand'=>'required|string|max:255','model'=>'required|string|max:255','service_id'=>'nullable|exists:services,id','complaint'=>'nullable|string|max:2000','booking_date'=>'required|date_format:Y-m-d','slot_time'=>'required|date_format:H:i']; }
}
