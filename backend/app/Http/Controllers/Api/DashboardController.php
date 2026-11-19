<?php

namespace App\Http\Controllers\Api;

use App\Enums\WorkOrderStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\SparepartResource;
use App\Models\Sparepart;
use App\Models\WorkOrder;
use Illuminate\Http\JsonResponse;

class DashboardController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $orders = WorkOrder::whereDate('queue_date', today());
        $counts = [];
        foreach (WorkOrderStatus::cases() as $status) {
            $counts[$status->value] = (clone $orders)->where('status', $status->value)->count();
        }

        return response()->json(['data' => [
            'counts' => $counts,
            'revenue' => (int) WorkOrder::whereDate('queue_date', today())->whereHas('payments')->withSum('payments', 'amount')->get()->sum('payments_sum_amount'),
            'low_stock' => SparepartResource::collection(Sparepart::whereColumn('stock', '<=', 'min_stock')->orderBy('name')->get())->resolve(),
        ]]);
    }
}
