<?php

namespace App\Services;

use App\Exceptions\InvalidTransitionException;
use App\Exceptions\SlotFullException;
use App\Models\Booking;
use App\Models\Customer;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\WorkOrder;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class BookingService
{
    private const ACTIVE = ['menunggu', 'dikonfirmasi'];

    public function __construct(
        private readonly WorkOrderService $workOrders,
        private readonly NotificationService $notifications,
    ) {}

    public function availableSlots(string $date): array
    {
        $day = $this->bookableDate($date);
        $capacity = (int) config('bengkel.booking.slot_capacity');
        $taken = Booking::whereDate('booking_date', $day->toDateString())
            ->whereIn('status', self::ACTIVE)
            ->selectRaw('slot_time, COUNT(*) as total')
            ->groupBy('slot_time')
            ->pluck('total', 'slot_time');

        return array_map(function (string $time) use ($taken, $capacity): array {
            $used = (int) ($taken[$time] ?? 0);

            return ['time' => $time, 'remaining' => max(0, $capacity - $used), 'available' => $used < $capacity];
        }, $this->slotTimes());
    }

    public function create(array $data): Booking
    {
        $booking = DB::transaction(function () use ($data) {
            $day = $this->bookableDate($data['booking_date']);
            $data['booking_date'] = $day->toDateString();
            $data['slot_time'] = $this->assertSlot($data['slot_time']);
            $data['plate_number'] = $this->normalizePlate($data['plate_number']);

            $duplicate = Booking::whereDate('booking_date', $data['booking_date'])
                ->where('plate_number', $data['plate_number'])
                ->whereIn('status', self::ACTIVE)
                ->lockForUpdate()
                ->exists();
            if ($duplicate) {
                throw new InvalidTransitionException('Plat ini sudah punya booking aktif pada tanggal tersebut.');
            }

            $taken = Booking::whereDate('booking_date', $data['booking_date'])
                ->where('slot_time', $data['slot_time'])
                ->whereIn('status', self::ACTIVE)
                ->lockForUpdate()
                ->count();
            if ($taken >= (int) config('bengkel.booking.slot_capacity')) {
                throw new SlotFullException('Slot '.$data['slot_time'].' sudah penuh.');
            }

            return Booking::create($data + [
                'booking_code' => $this->uniqueCode(),
                'status' => 'menunggu',
                'dp_amount' => (int) config('bengkel.booking.dp_amount'),
            ]);
        });

        $this->notifications->bookingEvent($booking, 'booking_dibuat');

        return $booking;
    }

    public function confirm(Booking $booking, User $user): Booking
    {
        $booking = DB::transaction(function () use ($booking) {
            $booking = $this->locked($booking);
            if ($booking->status !== 'menunggu') {
                throw new InvalidTransitionException('Transisi status booking tidak sah.');
            }
            if ((int) $booking->dp_amount > 0 && ! $booking->dp_paid_at) {
                throw new InvalidTransitionException('DP booking belum diterima. Catat DP sebelum konfirmasi.');
            }
            $booking->update(['status' => 'dikonfirmasi']);

            return $booking->refresh();
        });
        $this->notifications->bookingEvent($booking, 'booking_dikonfirmasi');

        return $booking;
    }

    public function submitDpProof(Booking $booking, string $method, string $proofPath): Booking
    {
        if (! in_array($method, ['transfer', 'qris'], true)) {
            throw new InvalidTransitionException('DP hanya bisa dibayar transfer atau QRIS.');
        }

        $old = $booking->dp_proof_path;

        $booking = DB::transaction(function () use ($booking, $method, $proofPath) {
            $booking = $this->locked($booking);
            if (! in_array($booking->status, self::ACTIVE, true)) {
                throw new InvalidTransitionException('Bukti DP tidak bisa dikirim pada status booking ini.');
            }
            if ((int) $booking->dp_amount <= 0) {
                throw new InvalidTransitionException('Booking ini tidak memakai DP.');
            }
            if ($booking->dp_paid_at) {
                throw new InvalidTransitionException('DP booking sudah diterima.');
            }
            $booking->update(['dp_method' => $method, 'dp_proof_path' => $proofPath, 'dp_proof_at' => now()]);

            return $booking->refresh();
        });

        if ($old && $old !== $proofPath) {
            Storage::disk('local')->delete($old);
        }

        return $booking;
    }

    public function receiveDp(Booking $booking, string $method, ?string $proofPath = null): Booking
    {
        if (! in_array($method, ['transfer', 'qris'], true)) {
            throw new InvalidTransitionException('DP hanya bisa dibayar transfer atau QRIS.');
        }

        return DB::transaction(function () use ($booking, $method, $proofPath) {
            $booking = $this->locked($booking);
            if (! in_array($booking->status, self::ACTIVE, true)) {
                throw new InvalidTransitionException('DP tidak bisa dicatat pada status booking ini.');
            }
            if ((int) $booking->dp_amount <= 0) {
                throw new InvalidTransitionException('Booking ini tidak memakai DP.');
            }
            if ($booking->dp_paid_at) {
                throw new InvalidTransitionException('DP booking sudah tercatat.');
            }
            $proof = $proofPath ?: $booking->dp_proof_path;
            if (blank($proof)) {
                throw new InvalidTransitionException('Belum ada bukti transfer dari pelanggan.');
            }
            $booking->update([
                'dp_paid_at' => now(),
                'dp_method' => $method,
                'dp_proof_path' => $proof,
                'dp_proof_at' => $booking->dp_proof_at ?: now(),
            ]);

            return $booking->refresh();
        });
    }

    public function reject(Booking $booking, User $user, string $note): Booking
    {
        $booking = DB::transaction(function () use ($booking, $note) {
            if (trim($note) === '') {
                throw new InvalidTransitionException('Catatan penolakan wajib diisi.');
            }

            return $this->change($booking, ['menunggu'], 'ditolak', ['staff_note' => $note]);
        });
        $this->notifications->bookingEvent($booking, 'booking_ditolak');

        return $booking;
    }

    public function cancel(Booking $booking, ?User $user = null): Booking
    {
        $booking = DB::transaction(fn () => $this->change($booking, self::ACTIVE, 'dibatalkan'));
        $this->notifications->bookingEvent($booking, 'booking_dibatalkan');

        return $booking;
    }

    public function checkIn(Booking $booking, User $user): WorkOrder
    {
        return DB::transaction(function () use ($booking, $user) {
            $booking = $this->locked($booking);
            if ($booking->status !== 'dikonfirmasi') {
                throw new InvalidTransitionException('Transisi status booking tidak sah.');
            }
            if ($booking->booking_date->toDateString() !== today()->toDateString()) {
                throw new InvalidTransitionException('Check-in hanya bisa dilakukan pada tanggal booking.');
            }

            $customer = Customer::where('phone', $booking->phone)->first();
            if (! $customer) {
                $customer = Customer::create([
                    'name' => $booking->customer_name,
                    'phone' => $booking->phone,
                    'email' => $booking->email,
                ]);
            } elseif (blank($customer->email) && filled($booking->email)) {
                $customer->update(['email' => $booking->email]);
            }

            $plate = $this->normalizePlate($booking->plate_number);
            $vehicle = Vehicle::where('plate_number', $plate)->first();
            if (! $vehicle) {
                $vehicle = Vehicle::create([
                    'customer_id' => $customer->id,
                    'plate_number' => $plate,
                    'type' => $booking->vehicle_type,
                    'brand' => $booking->brand,
                    'model' => $booking->model,
                ]);
            }

            $workOrder = $this->workOrders->register(
                $vehicle,
                $booking->complaint ?: 'Booking '.$booking->booking_code,
                $user,
            );

            if ($booking->dp_paid_at && (int) $booking->dp_amount > 0) {
                $workOrder->payments()->create([
                    'method' => 'dp',
                    'amount' => (int) $booking->dp_amount,
                    'paid_at' => $booking->dp_paid_at,
                ]);
            }

            $booking->update(['status' => 'check_in', 'work_order_id' => $workOrder->id]);

            return $workOrder;
        });
    }

    public function markNoShow(Booking $booking, User $user): Booking
    {
        return DB::transaction(function () use ($booking) {
            $booking = $this->locked($booking);
            if ($booking->status !== 'dikonfirmasi') {
                throw new InvalidTransitionException('Transisi status booking tidak sah.');
            }
            $booking->update(['status' => 'tidak_hadir']);

            return $booking->refresh();
        });
    }

    private function change(Booking $booking, array $from, string $to, array $data = []): Booking
    {
        $booking = $this->locked($booking);
        if (! in_array($booking->status, $from, true)) {
            throw new InvalidTransitionException('Transisi status booking tidak sah.');
        }
        $booking->update($data + ['status' => $to]);

        return $booking->refresh();
    }

    private function locked(Booking $booking): Booking
    {
        return Booking::lockForUpdate()->findOrFail($booking->id);
    }

    private function bookableDate(string $date): CarbonImmutable
    {
        if (! CarbonImmutable::hasFormat($date, 'Y-m-d')) {
            throw new InvalidTransitionException('Tanggal booking tidak valid.');
        }

        $day = CarbonImmutable::parse($date)->startOfDay();
        $today = CarbonImmutable::today();
        if ($day->lt($today->addDay())) {
            throw new InvalidTransitionException('Booking paling cepat untuk besok.');
        }
        if ($day->gt($today->addDays((int) config('bengkel.booking.max_days_ahead')))) {
            throw new InvalidTransitionException('Tanggal booking melewati batas yang diperbolehkan.');
        }
        if (in_array($day->locale('id')->dayName, config('bengkel.booking.closed_weekdays', []), true)) {
            throw new InvalidTransitionException('Bengkel tutup pada hari tersebut.');
        }

        return $day;
    }

    private function assertSlot(string $slot): string
    {
        $slot = trim($slot);
        if (! in_array($slot, $this->slotTimes(), true)) {
            throw new InvalidTransitionException('Slot waktu tidak valid.');
        }

        return $slot;
    }

    private function slotTimes(): array
    {
        $cursor = CarbonImmutable::parse((string) config('bengkel.booking.open_time'));
        $close = CarbonImmutable::parse((string) config('bengkel.booking.close_time'));
        $step = (int) config('bengkel.booking.slot_minutes');
        $slots = [];
        while ($cursor->lt($close)) {
            $slots[] = $cursor->format('H:i');
            $cursor = $cursor->addMinutes($step);
        }

        return $slots;
    }

    private function normalizePlate(string $plate): string
    {
        return strtoupper(preg_replace('/\s+/', '', $plate));
    }

    private function uniqueCode(): string
    {
        $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        do {
            $code = '';
            for ($i = 0; $i < 8; $i++) {
                $code .= $alphabet[random_int(0, strlen($alphabet) - 1)];
            }
        } while (Booking::where('booking_code', $code)->exists());

        return $code;
    }
}
