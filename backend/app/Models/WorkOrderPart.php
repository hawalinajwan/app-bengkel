<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class WorkOrderPart extends Model { protected $table='work_order_parts'; protected $fillable=['work_order_id','sparepart_id','qty','price']; protected function casts(): array{return ['qty'=>'integer','price'=>'integer'];} public function sparepart(){return $this->belongsTo(Sparepart::class);} }
