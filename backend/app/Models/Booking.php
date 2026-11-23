<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Booking extends Model
{
    protected $fillable = ['booking_code', 'customer_name', 'phone', 'email', 'plate_number', 'vehicle_type', 'brand', 'model', 'service_id', 'complaint', 'booking_date', 'slot_time', 'dp_amount', 'dp_paid_at', 'dp_method', 'dp_proof_path', 'dp_proof_at', 'status', 'staff_note', 'work_order_id'];

    protected function casts(): array
    {
        return ['booking_date' => 'date', 'dp_paid_at' => 'datetime', 'dp_proof_at' => 'datetime', 'dp_amount' => 'integer'];
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function workOrder(): BelongsTo
    {
        return $this->belongsTo(WorkOrder::class);
    }
}
