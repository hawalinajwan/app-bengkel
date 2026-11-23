<?php

namespace App\Http\Controllers\Api;

use App\Enums\WorkOrderStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\ReportDailyRequest;
use App\Models\WorkOrder;
use Illuminate\Http\JsonResponse;

class ReportController extends Controller
{
    public function daily(ReportDailyRequest $request): JsonResponse
    {
        $date = $request->validated('date');
        $query = WorkOrder::whereDate('queue_date', $date);
        $counts = [];
        foreach (WorkOrderStatus::cases() as $status) {
            $counts[$status->value] = (clone $query)->where('status', $status->value)->count();
        }
        $revenue = (int) (clone $query)->whereHas('payments')->withSum('payments', 'amount')->get()->sum('payments_sum_amount');

        return response()->json(['data' => compact('date', 'counts', 'revenue')]);
    }
}
