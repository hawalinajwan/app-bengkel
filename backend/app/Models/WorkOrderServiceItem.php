<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WorkOrderServiceItem extends Model
{
    protected $table = 'work_order_services';

    protected $fillable = ['work_order_id', 'service_id', 'price'];

    protected function casts(): array
    {
        return ['price' => 'integer'];
    }

    public function service()
    {
        return $this->belongsTo(Service::class);
    }
}
