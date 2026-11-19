<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use App\Http\Resources\PublicTrackingResource;
use App\Models\WorkOrder;
use Illuminate\Http\Request;
class PublicTrackingController extends Controller { public function show(string $code) { $wo=WorkOrder::with(['vehicle','statusLogs','serviceLines.service','partLines.sparepart'])->where('tracking_code',strtoupper(trim($code)))->firstOrFail(); return new PublicTrackingResource($wo); } public function lookup(Request $request) { $data=$request->validate(['plate_number'=>'required|string','phone_last4'=>'required|digits:4']); $plate=strtoupper(preg_replace('/\s+/','',$data['plate_number'])); $wo=WorkOrder::with('vehicle.customer')->whereHas('vehicle',fn($q)=>$q->where('plate_number',$plate)->whereHas('customer',fn($c)=>$c->whereRaw("REPLACE(REPLACE(REPLACE(phone, '-', ''), ' ', ''), '+', '') LIKE ?",['%'.$data['phone_last4']])))->whereDate('queue_date','>=',now()->subDays(7))->latest('queue_date')->first(); if(!$wo) abort(404,'Data pelacakan tidak ditemukan.'); return response()->json(['data'=>['tracking_code'=>$wo->tracking_code]]); } }