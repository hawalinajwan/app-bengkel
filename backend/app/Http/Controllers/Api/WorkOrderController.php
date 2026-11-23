<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\WorkOrderStoreRequest;
use App\Http\Resources\WorkOrderResource;
use App\Models\Mechanic;
use App\Models\Service;
use App\Models\Sparepart;
use App\Models\Vehicle;
use App\Models\WorkOrder;
use App\Services\WorkOrderService;
use Illuminate\Http\Request;

class WorkOrderController extends Controller
{
    public function __construct(private readonly WorkOrderService $service) {}

    public function board(Request $request)
    {
        $data = $request->validate(['date' => 'nullable|date_format:Y-m-d']);
        $date = $data['date'] ?? today()->toDateString();

        $orders = WorkOrder::whereDate('queue_date', $date)
            ->with(['vehicle', 'mechanic', 'serviceLines.service', 'partLines.sparepart'])
            ->orderBy('queue_number')
            ->get();

        $carryOver = WorkOrder::whereDate('queue_date', '<', $date)
            ->whereIn('status', ['menunggu', 'dikerjakan', 'selesai'])
            ->with(['vehicle', 'mechanic'])
            ->orderBy('queue_date')
            ->orderBy('queue_number')
            ->get();

        return response()->json([
            'data' => $orders->groupBy(fn ($wo) => $wo->status->value)
                ->map(fn ($group) => WorkOrderResource::collection($group)),
            'meta' => [
                'date' => $date,
                'carry_over' => WorkOrderResource::collection($carryOver)->resolve(),
            ],
        ]);
    }

    public function index(Request $request)
    {
        $query = WorkOrder::with(['vehicle', 'mechanic'])->latest('queue_date')->latest('queue_number');
        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }
        if ($date = $request->query('date')) {
            $query->whereDate('queue_date', $date);
        }

        return WorkOrderResource::collection($query->paginate((int) $request->query('per_page', 15)));
    }

    public function store(WorkOrderStoreRequest $request)
    {
        $data = $request->validated();

        if (! empty($data['vehicle_id'])) {
            $order = $this->service->register(
                Vehicle::findOrFail($data['vehicle_id']), $data['complaint'], $request->user()
            );
        } else {
            $order = $this->service->registerWalkIn(
                $data['customer'], $data['vehicle'], $data['complaint'], $request->user()
            );
        }

        return new WorkOrderResource($order->load('vehicle.customer'));
    }

    public function show(WorkOrder $workOrder)
    {
        return new WorkOrderResource($workOrder->load([
            'vehicle.customer', 'mechanic', 'user', 'serviceLines.service',
            'partLines.sparepart', 'payments', 'statusLogs',
        ]));
    }

    public function start(Request $request, WorkOrder $workOrder)
    {
        $data = $request->validate(['mechanic_id' => 'required|exists:mechanics,id']);

        return new WorkOrderResource($this->service->start($workOrder, Mechanic::findOrFail($data['mechanic_id'])));
    }

    public function addService(Request $request, WorkOrder $workOrder)
    {
        $data = $request->validate(['service_id' => 'required|exists:services,id']);

        return new WorkOrderResource($this->service->addService($workOrder, Service::findOrFail($data['service_id'])));
    }

    public function removeService(WorkOrder $workOrder, Service $service)
    {
        return new WorkOrderResource($this->service->removeService($workOrder, $service));
    }

    public function addPart(Request $request, WorkOrder $workOrder)
    {
        $data = $request->validate([
            'sparepart_id' => 'required|exists:spareparts,id',
            'qty' => 'required|integer|min:1',
        ]);

        return new WorkOrderResource($this->service->addPart(
            $workOrder, Sparepart::findOrFail($data['sparepart_id']), (int) $data['qty']
        ));
    }

    public function updatePartQty(Request $request, WorkOrder $workOrder, Sparepart $part)
    {
        $data = $request->validate(['qty' => 'required|integer|min:1']);

        return new WorkOrderResource($this->service->updatePartQty($workOrder, $part, (int) $data['qty']));
    }

    public function removePart(WorkOrder $workOrder, Sparepart $part)
    {
        return new WorkOrderResource($this->service->removePart($workOrder, $part));
    }

    public function finish(Request $request, WorkOrder $workOrder)
    {
        $data = $request->validate(['discount' => 'nullable|integer|min:0']);

        return new WorkOrderResource($this->service->finish($workOrder, (int) ($data['discount'] ?? 0)));
    }

    public function pay(Request $request, WorkOrder $workOrder)
    {
        $data = $request->validate([
            'method' => 'required|in:tunai,transfer,qris',
            'amount' => 'required|integer|min:0',
        ]);

        $payment = $this->service->pay($workOrder, $data['method'], (int) $data['amount']);

        return response()->json([
            'data' => new WorkOrderResource($workOrder->fresh()->load(['vehicle', 'serviceLines.service', 'partLines.sparepart', 'payments'])),
            'payment' => $payment,
            'kembalian' => $payment->amount - $workOrder->fresh()->total,
        ]);
    }

    public function cancel(WorkOrder $workOrder)
    {
        return new WorkOrderResource($this->service->cancel($workOrder));
    }

    public function publicNote(Request $request, WorkOrder $workOrder)
    {
        $data = $request->validate(['public_note' => 'nullable|string|max:500']);

        return new WorkOrderResource($this->service->updatePublicNote($workOrder, $data['public_note'] ?? null));
    }
}
