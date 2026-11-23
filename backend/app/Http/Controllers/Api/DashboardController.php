<?php

namespace App\Http\Controllers\Api;

use App\Enums\WorkOrderStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\SparepartResource;
use App\Models\Booking;
use App\Models\Payment;
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

        $payments = Payment::whereDate('paid_at', today())
            ->with('workOrder.vehicle.customer')
            ->latest('paid_at')
            ->get();

        $unpaid = (clone $orders)->where('status', WorkOrderStatus::Selesai->value)
            ->with('vehicle.customer')
            ->orderBy('queue_number')
            ->get();

        return response()->json(['data' => [
            'counts' => $counts,
            'revenue' => (int) $payments->sum('amount'),
            'low_stock' => SparepartResource::collection(Sparepart::whereColumn('stock', '<=', 'min_stock')->orderBy('name')->get())->resolve(),
            'action_items' => [
                'pending_bookings' => Booking::where('status', 'menunggu')->whereDate('booking_date', today())->count(),
                'unassigned_orders' => WorkOrder::whereDate('queue_date', today())->where('status', WorkOrderStatus::Menunggu->value)->whereNull('mechanic_id')->count(),
                'unpaid_orders' => $unpaid->count(),
            ],
            'payments' => [
                'total' => (int) $payments->sum('amount'),
                'count' => $payments->count(),
                'by_method' => $payments->groupBy('method')->map(fn ($group) => (int) $group->sum('amount')),
                'recent' => $payments->take(5)->map(fn (Payment $payment) => [
                    'id' => $payment->id,
                    'queue_number_display' => str_pad((string) $payment->workOrder->queue_number, 3, '0', STR_PAD_LEFT),
                    'queue_date' => $payment->workOrder->queue_date?->toDateString(),
                    'plate_number' => $payment->workOrder->vehicle?->plate_number,
                    'customer' => $payment->workOrder->vehicle?->customer?->name,
                    'amount' => $payment->amount,
                    'method' => $payment->method,
                    'paid_at' => $payment->paid_at?->toIso8601String(),
                ])->values(),
            ],
            'unpaid_orders' => $unpaid->map(fn (WorkOrder $order) => [
                'id' => $order->id,
                'queue_number_display' => str_pad((string) $order->queue_number, 3, '0', STR_PAD_LEFT),
                'plate_number' => $order->vehicle?->plate_number,
                'customer' => $order->vehicle?->customer?->name,
                'total' => $order->total,
            ])->values(),
        ]]);
    }
}
