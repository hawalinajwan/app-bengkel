<?php

namespace Tests\Feature;

use App\Enums\WorkOrderStatus;
use App\Exceptions\InsufficientStockException;
use App\Exceptions\InvalidTransitionException;
use App\Exceptions\MechanicUnavailableException;
use App\Exceptions\PaymentException;
use App\Models\Customer;
use App\Models\Mechanic;
use App\Models\Service;
use App\Models\Sparepart;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\WorkOrder;
use App\Services\WorkOrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WorkOrderTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Vehicle $vehicle;
    private Service $service;
    private Sparepart $part;
    private Mechanic $mechanic;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create(['role' => 'kasir']);
        $customer = Customer::factory()->create();
        $this->vehicle = Vehicle::factory()->create(['customer_id' => $customer->id]);
        $this->service = Service::factory()->create(['price' => 100000]);
        $this->part = Sparepart::factory()->create(['price' => 50000, 'stock' => 10]);
        $this->mechanic = Mechanic::factory()->create(['is_active' => true]);
        $this->actingAs($this->user, 'sanctum');
    }

    private function svc(): WorkOrderService
    {
        return app(WorkOrderService::class);
    }

    public function test_queue_numbers_increase_and_reset_daily(): void
    {
        $first = $this->svc()->register($this->vehicle, 'a', $this->user);
        $this->assertSame(1, $first->queue_number);
        $v2 = Vehicle::factory()->create(['customer_id' => $this->vehicle->customer_id]);
        $second = $this->svc()->register($v2, 'b', $this->user);
        $this->assertSame(2, $second->queue_number);

        $this->travelTo(now()->addDay());
        $v3 = Vehicle::factory()->create(['customer_id' => $this->vehicle->customer_id]);
        $next = $this->svc()->register($v3, 'c', $this->user);
        $this->assertSame(1, $next->queue_number);
    }

    public function test_vehicle_with_active_order_rejected(): void
    {
        $this->svc()->register($this->vehicle, 'a', $this->user);
        $this->expectException(InvalidTransitionException::class);
        $this->svc()->register($this->vehicle, 'b', $this->user);
    }

    public function test_start_rejects_inactive_or_full_mechanic(): void
    {
        $wo = $this->svc()->register($this->vehicle, 'a', $this->user);
        $this->mechanic->update(['is_active' => false]);
        try {
            $this->svc()->start($wo, $this->mechanic);
            $this->fail('inactive mechanic accepted');
        } catch (MechanicUnavailableException) {
        }
        $this->mechanic->update(['is_active' => true]);

        for ($i = 0; $i < 2; $i++) {
            $v = Vehicle::factory()->create(['customer_id' => $this->vehicle->customer_id]);
            $this->svc()->start($this->svc()->register($v, 'x', $this->user), $this->mechanic);
        }
        $this->expectException(MechanicUnavailableException::class);
        $this->svc()->start($wo, $this->mechanic);
    }

    public function test_add_part_decrements_stock_and_rolls_back_when_insufficient(): void
    {
        $wo = $this->svc()->start($this->svc()->register($this->vehicle, 'a', $this->user), $this->mechanic);
        $this->svc()->addPart($wo, $this->part, 2);
        $this->assertSame(8, $this->part->fresh()->stock);

        try {
            $this->svc()->addPart($wo, $this->part, 9);
            $this->fail('insufficient stock accepted');
        } catch (InsufficientStockException) {
        }
        $this->assertSame(8, $this->part->fresh()->stock);
        $this->assertSame(100000, $wo->fresh()->total);
        $this->assertSame(1, $wo->partLines()->count());
    }

    public function test_update_part_qty_and_remove_part_return_stock(): void
    {
        $wo = $this->svc()->start($this->svc()->register($this->vehicle, 'a', $this->user), $this->mechanic);
        $this->svc()->addPart($wo, $this->part, 5);
        $this->svc()->updatePartQty($wo, $this->part, 2);
        $this->assertSame(8, $this->part->fresh()->stock);

        $this->svc()->removePart($wo, $this->part);
        $this->assertSame(10, $this->part->fresh()->stock);
    }

    public function test_finish_computes_total_and_rejects_discount_above_subtotal(): void
    {
        $wo = $this->svc()->start($this->svc()->register($this->vehicle, 'a', $this->user), $this->mechanic);
        $this->svc()->addPart($wo, $this->part, 2);
        $this->svc()->addService($wo, $this->service);

        try {
            $this->svc()->finish($wo, 300000);
            $this->fail('discount above subtotal accepted');
        } catch (InvalidTransitionException) {
        }

        $this->svc()->finish($wo, 50000);
        $this->assertSame(150000, $wo->fresh()->total);
        $this->assertSame(WorkOrderStatus::Selesai, $wo->fresh()->status);
    }

    public function test_pay_rejects_short_amount_and_records_payment(): void
    {
        $wo = $this->svc()->start($this->svc()->register($this->vehicle, 'a', $this->user), $this->mechanic);
        $this->svc()->addService($wo, $this->service);
        $this->svc()->finish($wo, 0);

        try {
            $this->svc()->pay($wo, 'tunai', 1);
            $this->fail('short payment accepted');
        } catch (PaymentException) {
        }

        $payment = $this->svc()->pay($wo, 'tunai', 100000);
        $this->assertSame(100000, $payment->amount);
        $this->assertSame(WorkOrderStatus::Dibayar, $wo->fresh()->status);
    }

    public function test_cancel_returns_stock_and_finished_cannot_cancel(): void
    {
        $wo = $this->svc()->start($this->svc()->register($this->vehicle, 'a', $this->user), $this->mechanic);
        $this->svc()->addPart($wo, $this->part, 3);
        $this->svc()->cancel($wo);
        $this->assertSame(10, $this->part->fresh()->stock);
        $this->assertSame(WorkOrderStatus::Batal, $wo->fresh()->status);

        $v2 = Vehicle::factory()->create(['customer_id' => $this->vehicle->customer_id]);
        $w2 = $this->svc()->start($this->svc()->register($v2, 'b', $this->user), $this->mechanic);
        $this->svc()->addService($w2, $this->service);
        $this->svc()->finish($w2, 0);
        $this->expectException(InvalidTransitionException::class);
        $this->svc()->cancel($w2);
    }

    public function test_invalid_transitions_are_rejected(): void
    {
        $wo = $this->svc()->register($this->vehicle, 'a', $this->user);
        try {
            $this->svc()->pay($wo, 'tunai', 1);
            $this->fail('pay while menunggu accepted');
        } catch (InvalidTransitionException) {
        }
        $this->expectException(InvalidTransitionException::class);
        $this->svc()->finish($wo, 0);
    }

    public function test_register_creates_tracking_code_and_logs_every_status_change(): void
    {
        $wo = $this->svc()->start($this->svc()->register($this->vehicle, 'a', $this->user), $this->mechanic);
        $this->assertMatchesRegularExpression('/^[ABCDEFGHJKLMNPQRSTUVWXYZ23456789]{8}$/', $wo->tracking_code);

        $this->svc()->addService($wo, $this->service);
        $this->svc()->finish($wo, 0);
        $this->svc()->pay($wo, 'tunai', 100000);

        $logs = $wo->statusLogs()->orderBy('id')->get();
        $this->assertSame(['menunggu', 'dikerjakan', 'selesai', 'dibayar'], $logs->pluck('to_status')->all());
        $this->assertNull($logs[0]->from_status);
        $this->assertSame('menunggu', $logs[1]->from_status);
    }
}
