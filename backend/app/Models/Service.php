<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Service extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'price', 'est_minutes'];

    protected function casts(): array
    {
        return ['price' => 'integer', 'est_minutes' => 'integer'];
    }

    public function workOrderLines(): HasMany
    {
        return $this->hasMany(WorkOrderServiceItem::class);
    }
}
