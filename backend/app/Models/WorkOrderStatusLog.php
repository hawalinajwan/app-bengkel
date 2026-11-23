<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WorkOrderStatusLog extends Model
{
    public $timestamps = false;

    protected $fillable = ['work_order_id', 'user_id', 'from_status', 'to_status'];

    protected function casts(): array
    {
        return ['created_at' => 'datetime'];
    }
}
