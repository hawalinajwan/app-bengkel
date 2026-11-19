<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Sparepart extends Model
{
    use HasFactory;

    protected $fillable = ['sku', 'name', 'price', 'stock', 'min_stock'];

    protected function casts(): array
    {
        return ['price' => 'integer', 'stock' => 'integer', 'min_stock' => 'integer'];
    }

    public function workOrderLines(): HasMany
    {
        return $this->hasMany(WorkOrderPart::class);
    }
}
