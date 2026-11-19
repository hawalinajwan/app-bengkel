<?php
namespace App\Services;
use App\Models\NotificationLog;
use App\Models\WorkOrder;
use Illuminate\Support\Facades\Mail;
use App\Mail\WorkOrderFinishedMail;
class NotificationService { public function orderFinished(WorkOrder $workOrder): void { $email=$workOrder->vehicle?->customer?->email; $log=NotificationLog::create(['type'=>'order_selesai','channel'=>'email','recipient'=>$email??'','work_order_id'=>$workOrder->id,'status'=>$email?'terkirim':'dilewati','sent_at'=>$email?now():null]); if(!$email)return; try { Mail::to($email)->queue(new WorkOrderFinishedMail($workOrder)); } catch (\Throwable $e) { $log->update(['status'=>'gagal','error'=>$e->getMessage(),'sent_at'=>null]); } } }