<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    protected $fillable = ['work_order_id', 'method', 'amount', 'paid_at'];

    protected function casts(): array
    {
        return ['amount' => 'integer', 'paid_at' => 'datetime'];
    }

    public function workOrder()
    {
        return $this->belongsTo(WorkOrder::class);
    }
}
