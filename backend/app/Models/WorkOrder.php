<?php
namespace App\Models;
use App\Enums\WorkOrderStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
class WorkOrder extends Model { protected $fillable=['vehicle_id','user_id','mechanic_id','queue_date','queue_number','tracking_code','complaint','status','public_note','started_at','finished_at','discount','total']; protected function casts(): array { return ['status'=>WorkOrderStatus::class,'queue_date'=>'date','started_at'=>'datetime','finished_at'=>'datetime','discount'=>'integer','total'=>'integer']; } public function vehicle(): BelongsTo{return $this->belongsTo(Vehicle::class);} public function mechanic(): BelongsTo{return $this->belongsTo(Mechanic::class);} public function user(): BelongsTo{return $this->belongsTo(User::class);} public function serviceLines(): HasMany{return $this->hasMany(WorkOrderServiceItem::class);} public function partLines(): HasMany{return $this->hasMany(WorkOrderPart::class);} public function payments(): HasMany{return $this->hasMany(Payment::class);} public function statusLogs(): HasMany{return $this->hasMany(WorkOrderStatusLog::class);} }
