<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Mechanic;
use App\Models\Service;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\WorkOrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VehicleHistoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_history_lists_past_orders_with_services_for_staff(): void
    {
        $kasir = User::factory()->create(['role' => 'kasir']);
        $vehicle = Vehicle::factory()->create(['type' => 'mobil', 'customer_id' => Customer::factory()]);
        $service = Service::factory()->create(['name' => 'Ganti Oli Mesin Mobil']);
        $mechanic = Mechanic::factory()->create();
        $wo = app(WorkOrderService::class);

        $order = $wo->register($vehicle, 'Ganti oli', $kasir);
        $wo->start($order, $mechanic);
        $wo->addService($order, $service);

        $this->actingAs($kasir, 'sanctum')
            ->getJson("/api/vehicles/{$vehicle->id}/history")
            ->assertOk()
            ->assertJsonPath('data.vehicle.plate_number', $vehicle->plate_number)
            ->assertJsonPath('data.orders.0.status', 'dikerjakan')
            ->assertJsonPath('data.orders.0.services.0', 'Ganti Oli Mesin Mobil');
    }

    public function test_plate_lookup_endpoint_is_removed(): void
    {
        $this->postJson('/api/public/track/lookup', ['plate_number' => 'B001ABC', 'phone_last4' => '1234'])
            ->assertStatus(405);
    }
}
