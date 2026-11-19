<?php

namespace App\Services;

use App\Enums\WorkOrderStatus as S;
use App\Exceptions\InsufficientStockException;
use App\Exceptions\InvalidTransitionException;
use App\Exceptions\MechanicUnavailableException;
use App\Exceptions\PaymentException;
use App\Models\Mechanic;
use App\Models\Payment;
use App\Models\Service;
use App\Models\Sparepart;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\WorkOrder;
use App\Models\WorkOrderStatusLog;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;

class WorkOrderService
{
    public function __construct(private readonly NotificationService $notifications) {}

    public function register(Vehicle $vehicle, string $complaint, User $user): WorkOrder
    {
        for ($attempt = 1; ; $attempt++) {
            try {
                return DB::transaction(function () use ($vehicle, $complaint, $user) {
                    $active = WorkOrder::where('vehicle_id', $vehicle->id)
                        ->whereIn('status', [S::Menunggu, S::Dikerjakan])->exists();
                    if ($active) {
                        throw new InvalidTransitionException('Kendaraan masih punya order aktif.');
                    }
                    $date = today()->toDateString();
                    $last = WorkOrder::whereDate('queue_date', $date)->lockForUpdate()->max('queue_number');
                    $wo = WorkOrder::create([
                        'vehicle_id' => $vehicle->id,
                        'user_id' => $user->id,
                        'queue_date' => $date,
                        'queue_number' => ($last ?? 0) + 1,
                        'tracking_code' => $this->trackingCode(),
                        'complaint' => $complaint,
                        'status' => S::Menunggu,
                    ]);
                    $this->logStatus($wo, null, S::Menunggu, $user);

                    return $wo;
                });
            } catch (UniqueConstraintViolationException $e) {
                if ($attempt >= 3) {
                    throw $e;
                }
            }
        }
    }

    public function start(WorkOrder $wo, Mechanic $mechanic): WorkOrder
    {
        return DB::transaction(function () use ($wo, $mechanic) {
            $wo = $this->locked($wo, S::Menunggu);
            if (! $mechanic->is_active) {
                throw new MechanicUnavailableException('Mekanik tidak aktif.');
            }
            $limit = (int) config('bengkel.max_active_orders_per_mechanic');
            $busy = WorkOrder::where('mechanic_id', $mechanic->id)->where('status', S::Dikerjakan)->count();
            if ($busy >= $limit) {
                throw new MechanicUnavailableException('Beban mekanik sudah penuh.');
            }
            $this->change($wo, S::Dikerjakan, ['mechanic_id' => $mechanic->id, 'started_at' => now()]);

            return $wo->refresh();
        });
    }

    public function addService(WorkOrder $wo, Service $service): WorkOrder
    {
        return DB::transaction(function () use ($wo, $service) {
            $wo = $this->locked($wo, S::Dikerjakan);
            $wo->serviceLines()->firstOrCreate(['service_id' => $service->id], ['price' => $service->price]);
            $this->recalculateTotal($wo);

            return $wo->refresh();
        });
    }

    public function removeService(WorkOrder $wo, Service $service): WorkOrder
    {
        return DB::transaction(function () use ($wo, $service) {
            $wo = $this->locked($wo, S::Dikerjakan);
            $wo->serviceLines()->where('service_id', $service->id)->delete();
            $this->recalculateTotal($wo);

            return $wo->refresh();
        });
    }

    public function addPart(WorkOrder $wo, Sparepart $part, int $qty): WorkOrder
    {
        return DB::transaction(function () use ($wo, $part, $qty) {
            $wo = $this->locked($wo, S::Dikerjakan);
            $part = Sparepart::lockForUpdate()->findOrFail($part->id);
            if ($qty < 1 || $part->stock < $qty) {
                throw new InsufficientStockException('Stok sparepart tidak mencukupi.');
            }
            $part->decrement('stock', $qty);
            $line = $wo->partLines()->where('sparepart_id', $part->id)->first();
            $line ? $line->increment('qty', $qty) : $wo->partLines()->create([
                'sparepart_id' => $part->id, 'qty' => $qty, 'price' => $part->price,
            ]);
            $this->recalculateTotal($wo);

            return $wo->refresh();
        });
    }

    public function updatePartQty(WorkOrder $wo, Sparepart $part, int $qty): WorkOrder
    {
        return DB::transaction(function () use ($wo, $part, $qty) {
            $wo = $this->locked($wo, S::Dikerjakan);
            $line = $wo->partLines()->where('sparepart_id', $part->id)->firstOrFail();
            $part = Sparepart::lockForUpdate()->findOrFail($part->id);
            $delta = $qty - $line->qty;
            if ($qty < 1 || ($delta > 0 && $part->stock < $delta)) {
                throw new InsufficientStockException('Stok sparepart tidak mencukupi.');
            }
            $part->increment('stock', -$delta);
            $line->update(['qty' => $qty]);
            $this->recalculateTotal($wo);

            return $wo->refresh();
        });
    }

    public function removePart(WorkOrder $wo, Sparepart $part): WorkOrder
    {
        return DB::transaction(function () use ($wo, $part) {
            $wo = $this->locked($wo, S::Dikerjakan);
            $line = $wo->partLines()->where('sparepart_id', $part->id)->firstOrFail();
            Sparepart::whereKey($part->id)->increment('stock', $line->qty);
            $line->delete();
            $this->recalculateTotal($wo);

            return $wo->refresh();
        });
    }

    public function finish(WorkOrder $wo, int $discount): WorkOrder
    {
        $wo = DB::transaction(function () use ($wo, $discount) {
            $wo = $this->locked($wo, S::Dikerjakan);
            $this->recalculateTotal($wo);
            $subtotal = $wo->total;
            if ($wo->serviceLines()->doesntExist() && $wo->partLines()->doesntExist()) {
                throw new InvalidTransitionException('Minimal satu jasa atau sparepart harus ditambahkan.');
            }
            if ($discount < 0 || $discount > $subtotal) {
                throw new InvalidTransitionException('Diskon melebihi subtotal.');
            }
            $this->change($wo, S::Selesai, [
                'discount' => $discount,
                'finished_at' => now(),
                'total' => $subtotal - $discount,
            ]);

            return $wo->refresh();
        });
        $this->notifications->orderFinished($wo);

        return $wo;
    }

    public function pay(WorkOrder $wo, string $method, int $amount): Payment
    {
        return DB::transaction(function () use ($wo, $method, $amount) {
            $wo = $this->locked($wo, S::Selesai);
            if ($wo->payments()->exists()) {
                throw new PaymentException('Order sudah dibayar.');
            }
            if ($amount < $wo->total) {
                throw new PaymentException('Jumlah pembayaran kurang.');
            }
            $payment = $wo->payments()->create(['method' => $method, 'amount' => $amount, 'paid_at' => now()]);
            $this->change($wo, S::Dibayar);

            return $payment;
        });
    }

    public function cancel(WorkOrder $wo): WorkOrder
    {
        return DB::transaction(function () use ($wo) {
            $wo = $this->locked($wo, S::Menunggu, S::Dikerjakan);
            foreach ($wo->partLines as $line) {
                Sparepart::whereKey($line->sparepart_id)->increment('stock', $line->qty);
            }
            $this->change($wo, S::Batal);

            return $wo->refresh();
        });
    }

    public function updatePublicNote(WorkOrder $wo, ?string $note): WorkOrder
    {
        return DB::transaction(function () use ($wo, $note) {
            $wo = WorkOrder::lockForUpdate()->findOrFail($wo->id);
            if ($wo->status === S::Batal) {
                throw new InvalidTransitionException('Order sudah dibatalkan.');
            }
            $wo->update(['public_note' => $note]);

            return $wo;
        });
    }

    public function recalculateTotal(WorkOrder $wo): void
    {
        $wo->load(['serviceLines', 'partLines']);
        $wo->update([
            'total' => max(0, $wo->serviceLines->sum('price')
                + $wo->partLines->sum(fn ($p) => $p->price * $p->qty)
                - $wo->discount),
        ]);
    }

    public function logStatus(WorkOrder $wo, ?S $from, S $to, ?User $user = null): void
    {
        WorkOrderStatusLog::create([
            'work_order_id' => $wo->id,
            'user_id' => $user?->id ?? auth()->id(),
            'from_status' => $from?->value,
            'to_status' => $to->value,
        ]);
    }

    private function locked(WorkOrder $wo, S ...$allowed): WorkOrder
    {
        $wo = WorkOrder::lockForUpdate()->findOrFail($wo->id);
        $this->assertStatus($wo, ...$allowed);

        return $wo;
    }

    private function assertStatus(WorkOrder $wo, S ...$allowed): void
    {
        if (! in_array($wo->status, $allowed, true)) {
            throw new InvalidTransitionException('Transisi status tidak sah.');
        }
    }

    private function change(WorkOrder $wo, S $to, array $data = []): void
    {
        $from = $wo->status;
        $wo->update($data + ['status' => $to]);
        $this->logStatus($wo, $from, $to);
    }

    private function trackingCode(): string
    {
        $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        do {
            $code = '';
            for ($i = 0; $i < 8; $i++) {
                $code .= $alphabet[random_int(0, strlen($alphabet) - 1)];
            }
        } while (WorkOrder::where('tracking_code', $code)->exists());

        return $code;
    }
}
