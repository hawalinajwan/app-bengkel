<?php

namespace Tests\Feature;

use App\Exceptions\InvalidTransitionException;
use App\Exceptions\PaymentException;
use App\Exceptions\SlotFullException;
use App\Models\Booking;
use App\Models\Customer;
use App\Models\Mechanic;
use App\Models\Service;
use App\Models\User;
use App\Models\WorkOrder;
use App\Services\BookingService;
use App\Services\WorkOrderService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class BookingRulesTest extends TestCase
{
    use RefreshDatabase;

    private User $staff;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        $this->staff = User::factory()->create(['role' => 'kasir']);
        $this->actingAs($this->staff, 'sanctum');
    }

    private function data(?string $date = null, string $slot = '08:00', string $plate = 'B 1234 CD'): array
    {
        return [
            'customer_name' => 'Budi', 'phone' => '08123456789', 'email' => 'budi@example.test',
            'plate_number' => $plate, 'vehicle_type' => 'mobil', 'brand' => 'Toyota', 'model' => 'Avanza',
            'complaint' => 'Servis rutin', 'booking_date' => $date ?: $this->nextBookableDate(), 'slot_time' => $slot,
        ];
    }

    private function nextBookableDate(): string
    {
        $day = CarbonImmutable::tomorrow();
        while ($day->dayOfWeek === CarbonImmutable::SUNDAY) {
            $day = $day->addDay();
        }

        return $day->toDateString();
    }

    private function booking(array $overrides = []): Booking
    {
        return app(BookingService::class)->create(array_merge($this->data(), $overrides));
    }

    public function test_valid_create_returns_code_and_notification_log(): void
    {
        $booking = $this->booking();

        $this->assertMatchesRegularExpression('/^[ABCDEFGHJKLMNPQRSTUVWXYZ23456789]{8}$/', $booking->booking_code);
        $this->assertSame('menunggu', $booking->status);
        $this->assertSame((int) config('bengkel.booking.dp_amount'), $booking->dp_amount);
        $this->assertNull($booking->dp_paid_at);
        $this->assertDatabaseHas('notification_logs', ['booking_id' => $booking->id, 'type' => 'booking_dibuat']);
    }

    public function test_rejects_today_past_too_far_sunday_and_invalid_slot(): void
    {
        $service = app(BookingService::class);
        $cases = [
            today()->toDateString(),
            today()->subDay()->toDateString(),
            today()->addDays(config('bengkel.booking.max_days_ahead') + 1)->toDateString(),
            CarbonImmutable::tomorrow()->next(CarbonImmutable::SUNDAY)->toDateString(),
        ];
        foreach ($cases as $date) {
            $this->expectException(InvalidTransitionException::class);
            try {
                $service->create($this->data($date));
            } catch (InvalidTransitionException $e) {
                throw $e;
            }
        }
        $this->expectException(InvalidTransitionException::class);
        $service->create($this->data(null, '07:00'));
    }

    public function test_full_slot_throws_slot_full_exception(): void
    {
        for ($i = 0; $i < config('bengkel.booking.slot_capacity'); $i++) {
            $this->booking(['plate_number' => 'B 123'.$i.' CD', 'slot_time' => '08:00']);
        }
        $this->expectException(SlotFullException::class);
        $this->booking(['plate_number' => 'B 9999 CD']);
    }

    public function test_duplicate_active_plate_on_date_is_rejected(): void
    {
        $this->booking();
        $this->expectException(InvalidTransitionException::class);
        $this->booking(['plate_number' => 'b 1234 cd', 'slot_time' => '09:00']);
    }

    public function test_public_service_list_includes_price_and_duration(): void
    {
        $service = Service::factory()->create(['name' => 'Ganti Oli', 'price' => 350000, 'est_minutes' => 30]);

        $this->getJson('/api/public/services')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $service->id)
            ->assertJsonPath('data.0.price', 350000)
            ->assertJsonPath('data.0.est_minutes', 30);
    }

    public function test_public_status_and_cancel(): void
    {
        $booking = $this->booking();
        $this->getJson('/api/public/bookings/'.$booking->booking_code)->assertOk()->assertJsonPath('data.booking_code', $booking->booking_code);
        $this->postJson('/api/public/bookings/'.$booking->booking_code.'/cancel')->assertOk()->assertJsonPath('data.status', 'dibatalkan');
    }

    public function test_public_booking_slots_include_dp_amount(): void
    {
        $this->getJson('/api/public/booking-slots?date='.$this->nextBookableDate())
            ->assertOk()
            ->assertJsonPath('meta.dp_amount', (int) config('bengkel.booking.dp_amount'));
    }

    public function test_staff_confirm_and_reject_require_note(): void
    {
        $booking = $this->booking();
        $this->postJson('/api/bookings/'.$booking->id.'/confirm')->assertStatus(422);
        $this->post('/api/bookings/'.$booking->id.'/dp', ['method' => 'transfer'], ['Accept' => 'application/json'])
            ->assertStatus(422);
        $this->post('/api/public/bookings/'.$booking->booking_code.'/dp', ['method' => 'tunai', 'proof' => UploadedFile::fake()->image('bukti.jpg')], ['Accept' => 'application/json'])
            ->assertStatus(422);
        $this->post('/api/public/bookings/'.$booking->booking_code.'/dp', ['method' => 'transfer', 'proof' => UploadedFile::fake()->image('bukti.jpg')], ['Accept' => 'application/json'])
            ->assertOk()
            ->assertJsonPath('data.dp_proof', true)
            ->assertJsonPath('data.dp_paid', false);
        $this->getJson('/api/bookings/'.$booking->id.'/dp-proof')->assertOk();
        $this->postJson('/api/bookings/'.$booking->id.'/confirm')->assertStatus(422);
        $this->postJson('/api/bookings/'.$booking->id.'/dp', ['method' => 'transfer'])
            ->assertOk()
            ->assertJsonPath('data.dp_method', 'transfer')
            ->assertJsonPath('data.dp_proof', true)
            ->assertJsonPath('data.dp_amount', (int) config('bengkel.booking.dp_amount'));
        $this->assertNotNull($booking->fresh()->dp_paid_at);
        $this->postJson('/api/public/bookings/'.$booking->booking_code.'/cancel')->assertStatus(422);
        $this->postJson('/api/bookings/'.$booking->id.'/confirm')->assertOk()->assertJsonPath('data.status', 'dikonfirmasi');

        $rejected = $this->booking(['plate_number' => 'B 8888 CD', 'slot_time' => '09:00']);
        $this->postJson('/api/bookings/'.$rejected->id.'/reject', [])->assertStatus(422);
        $this->postJson('/api/bookings/'.$rejected->id.'/reject', ['staff_note' => 'Slot tidak tersedia'])->assertOk()->assertJsonPath('data.status', 'ditolak');
        $this->assertSame('Slot tidak tersedia', $rejected->fresh()->staff_note);
    }

    public function test_down_payment_is_credited_at_check_in_and_only_remaining_is_paid(): void
    {
        $service = Service::factory()->create(['price' => 200000]);
        $mechanic = Mechanic::factory()->create(['is_active' => true]);
        $booking = $this->booking();
        $this->postJson('/api/bookings/'.$booking->id.'/dp', ['method' => 'invald'])->assertStatus(422);
        $this->postJson('/api/bookings/'.$booking->id.'/dp', ['method' => 'transfer'])->assertStatus(422);
        $this->post('/api/public/bookings/'.$booking->booking_code.'/dp', ['method' => 'transfer', 'proof' => UploadedFile::fake()->image('bukti.jpg')], ['Accept' => 'application/json'])->assertOk();
        $this->postJson('/api/bookings/'.$booking->id.'/dp', ['method' => 'transfer'])->assertOk();
        $this->postJson('/api/bookings/'.$booking->id.'/dp', ['method' => 'transfer'])->assertStatus(422);
        $this->post('/api/public/bookings/'.$booking->booking_code.'/dp', ['method' => 'qris', 'proof' => UploadedFile::fake()->image('bukti.jpg')], ['Accept' => 'application/json'])->assertStatus(422);
        $this->postJson('/api/bookings/'.$booking->id.'/confirm')->assertOk();

        Booking::whereKey($booking->id)->update(['booking_date' => today()]);
        $orders = app(WorkOrderService::class);
        $order = app(BookingService::class)->checkIn($booking->fresh(), $this->staff);

        $dp = (int) config('bengkel.booking.dp_amount');
        $this->assertSame($dp, (int) $order->payments()->sum('amount'));
        $this->assertDatabaseHas('payments', ['work_order_id' => $order->id, 'method' => 'dp', 'amount' => $dp]);

        $order = $orders->start($order, $mechanic);
        $orders->addService($order, $service);
        $orders->finish($order, 0);
        $remaining = (int) $order->fresh()->total - $dp;
        $this->assertSame(150000, $remaining);

        try {
            $orders->pay($order, 'tunai', $remaining - 1);
            $this->fail('short payment accepted');
        } catch (PaymentException) {
        }

        $orders->pay($order, 'tunai', $remaining);
        $this->assertSame('dibayar', $order->fresh()->status->value);
        $this->assertSame(200000, (int) $order->fresh()->payments()->sum('amount'));
    }

    public function test_check_in_today_creates_work_order_and_links_customer_vehicle(): void
    {
        $booking = Booking::create($this->data(today()->toDateString()) + ['booking_code' => 'CHECK123', 'status' => 'dikonfirmasi']);
        $workOrder = app(BookingService::class)->checkIn($booking, $this->staff);

        $this->assertInstanceOf(WorkOrder::class, $workOrder);
        $this->assertDatabaseHas('customers', ['phone' => '08123456789']);
        $this->assertDatabaseHas('vehicles', ['plate_number' => 'B1234CD', 'customer_id' => Customer::where('phone', '08123456789')->value('id')]);
        $this->assertSame('check_in', $booking->fresh()->status);
        $this->assertSame($workOrder->id, $booking->fresh()->work_order_id);
    }

    public function test_no_show_transition(): void
    {
        $booking = Booking::create($this->data() + ['booking_code' => 'NOSHOW12', 'status' => 'dikonfirmasi']);
        app(BookingService::class)->markNoShow($booking, $this->staff);
        $this->assertSame('tidak_hadir', $booking->fresh()->status);
    }
}
