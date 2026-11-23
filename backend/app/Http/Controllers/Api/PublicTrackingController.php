<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\PublicTrackingResource;
use App\Models\WorkOrder;

class PublicTrackingController extends Controller
{
    public function show(string $code)
    {
        $wo = WorkOrder::with(['vehicle', 'statusLogs', 'serviceLines.service', 'partLines.sparepart'])->where('tracking_code', strtoupper(trim($code)))->firstOrFail();

        return new PublicTrackingResource($wo);
    }
}
