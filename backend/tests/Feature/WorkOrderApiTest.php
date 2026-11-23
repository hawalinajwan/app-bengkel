<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Mechanic;
use App\Models\Service;
use App\Models\Sparepart;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\WorkOrder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WorkOrderApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_work_order_endpoints_require_token(): void
    {
        $this->getJson('/api/work-orders')->assertUnauthorized();
        $this->getJson('/api/work-orders/board')->assertUnauthorized();
        $this->postJson('/api/work-orders', [])->assertUnauthorized();
    }

    public function test_walk_in_creates_customer_vehicle_and_order_in_one_request(): void
    {
        $kasir = User::factory()->create(['role' => 'kasir']);

        $created = $this->actingAs($kasir, 'sanctum')
            ->postJson('/api/work-orders', [
                'customer' => ['name' => 'Rina Wijaya', 'phone' => '0812 3456 7890'],
                'vehicle' => ['plate_number' => 'b 1234 xyz', 'brand' => 'Honda', 'model' => 'Jazz', 'year' => 2019],
                'complaint' => 'Rem berdecit saat berhenti',
            ])
            ->assertCreated()
            ->assertJsonPath('data.status', 'menunggu')
            ->assertJsonPath('data.vehicle.plate_number', 'B1234XYZ')
            ->assertJsonPath('data.vehicle.customer.name', 'Rina Wijaya')
            ->json('data');

        $this->assertSame(1, $created['queue_number']);
        $this->assertDatabaseHas('vehicles', ['plate_number' => 'B1234XYZ', 'year' => 2019]);

        // Pelanggan lama dengan mobil kedua: telepon sama, pelanggan tidak diduplikasi.
        $this->actingAs($kasir, 'sanctum')
            ->postJson('/api/work-orders', [
                'customer' => ['name' => 'Rina Wijaya', 'phone' => '0812 3456 7890'],
                'vehicle' => ['plate_number' => 'B 9999 ZZ', 'brand' => 'Toyota', 'model' => 'Avanza'],
                'complaint' => 'Servis berkala',
            ])->assertCreated();

        $this->assertSame(1, Customer::count());
        $this->assertSame(2, Vehicle::where('customer_id', $created['vehicle']['customer']['id'])->count());

        // Plat yang sudah terdaftar ditolak, bukan membuat kendaraan ganda.
        $this->actingAs($kasir, 'sanctum')
            ->postJson('/api/work-orders', [
                'customer' => ['name' => 'Orang Lain'],
                'vehicle' => ['plate_number' => 'B1234XYZ', 'brand' => 'Honda', 'model' => 'Jazz'],
                'complaint' => 'x',
            ])->assertStatus(422)->assertJsonPath('errors', fn ($errors) => isset($errors['vehicle.plate_number']));

        // Nama pelanggan wajib untuk pelanggan baru.
        $this->actingAs($kasir, 'sanctum')
            ->postJson('/api/work-orders', [
                'customer' => ['phone' => '081200000000'],
                'vehicle' => ['plate_number' => 'B0000AA', 'brand' => 'Suzuki', 'model' => 'Ertiga'],
                'complaint' => 'x',
            ])->assertStatus(422);

        // Alur lama tetap harus memakai vehicle_id yang sah.
        $this->actingAs($kasir, 'sanctum')
            ->postJson('/api/work-orders', ['vehicle_id' => 99999, 'complaint' => 'x'])
            ->assertStatus(422);
    }

    public function test_board_and_index_and_show_return_work_orders(): void
    {
        $kasir = User::factory()->create(['role' => 'kasir']);
        $vehicle = Vehicle::factory()->create();

        $created = $this->actingAs($kasir, 'sanctum')
            ->postJson('/api/work-orders', ['vehicle_id' => $vehicle->id, 'complaint' => 'Berisik'])
            ->assertCreated()
            ->assertJsonPath('data.status', 'menunggu')
            ->json('data');

        $this->assertSame(1, $created['queue_number']);

        $this->actingAs($kasir, 'sanctum')->getJson('/api/work-orders/board')
            ->assertOk()->assertJsonCount(1, 'data');

        $this->actingAs($kasir, 'sanctum')->getJson('/api/work-orders')
            ->assertOk()->assertJsonPath('data.0.id', $created['id']);

        $this->actingAs($kasir, 'sanctum')->getJson("/api/work-orders/{$created['id']}")
            ->assertOk()->assertJsonPath('data.tracking_code', $created['tracking_code']);
    }

    public function test_board_can_show_another_date_and_lists_carry_over(): void
    {
        $kasir = User::factory()->create(['role' => 'kasir']);
        $vehicle = Vehicle::factory()->create();

        $created = $this->actingAs($kasir, 'sanctum')
            ->postJson('/api/work-orders', ['vehicle_id' => $vehicle->id, 'complaint' => 'Menginap'])
            ->json('data');

        $yesterday = today()->subDay()->toDateString();
        WorkOrder::whereKey($created['id'])->update(['queue_date' => $yesterday]);

        $this->actingAs($kasir, 'sanctum')->getJson("/api/work-orders/board?date={$yesterday}")
            ->assertOk()
            ->assertJsonPath('meta.date', $yesterday)
            ->assertJsonCount(1, 'data.menunggu')
            ->assertJsonCount(0, 'meta.carry_over');

        $this->actingAs($kasir, 'sanctum')->getJson('/api/work-orders/board')
            ->assertOk()
            ->assertJsonCount(0, 'data')
            ->assertJsonPath('meta.carry_over.0.id', $created['id'])
            ->assertJsonPath('meta.carry_over.0.queue_date', $yesterday);

        $this->actingAs($kasir, 'sanctum')->getJson('/api/work-orders/board?date=23-11-2026')
            ->assertStatus(422);
    }

    public function test_business_rule_violation_returns_json_422(): void
    {
        $kasir = User::factory()->create(['role' => 'kasir']);
        $vehicle = Vehicle::factory()->create();
        $mechanic = Mechanic::factory()->create(['is_active' => false]);
        $service = Service::factory()->create();
        $part = Sparepart::factory()->create(['stock' => 1]);

        $wo = $this->actingAs($kasir, 'sanctum')
            ->postJson('/api/work-orders', ['vehicle_id' => $vehicle->id, 'complaint' => 'x'])
            ->json('data');

        $this->actingAs($kasir, 'sanctum')
            ->postJson("/api/work-orders/{$wo['id']}/start", ['mechanic_id' => $mechanic->id])
            ->assertStatus(422)->assertJsonStructure(['message']);

        $this->actingAs($kasir, 'sanctum')
            ->postJson("/api/work-orders/{$wo['id']}/parts", ['sparepart_id' => $part->id, 'qty' => 1])
            ->assertStatus(422);
    }

    public function test_part_lifecycle_endpoints_and_payment(): void
    {
        $kasir = User::factory()->create(['role' => 'kasir']);
        $vehicle = Vehicle::factory()->create();
        $mechanic = Mechanic::factory()->create(['is_active' => true]);
        $service = Service::factory()->create(['price' => 100000]);
        $part = Sparepart::factory()->create(['price' => 50000, 'stock' => 10]);

        $wo = $this->actingAs($kasir, 'sanctum')
            ->postJson('/api/work-orders', ['vehicle_id' => $vehicle->id, 'complaint' => 'x'])
            ->json('data');
        $id = $wo['id'];

        $this->actingAs($kasir, 'sanctum')
            ->postJson("/api/work-orders/{$id}/start", ['mechanic_id' => $mechanic->id])
            ->assertOk()->assertJsonPath('data.status', 'dikerjakan');

        $this->actingAs($kasir, 'sanctum')
            ->postJson("/api/work-orders/{$id}/services", ['service_id' => $service->id])->assertOk();
        $this->actingAs($kasir, 'sanctum')
            ->postJson("/api/work-orders/{$id}/parts", ['sparepart_id' => $part->id, 'qty' => 2])->assertOk();
        $this->assertSame(8, $part->fresh()->stock);

        $this->actingAs($kasir, 'sanctum')
            ->patchJson("/api/work-orders/{$id}/parts/{$part->id}", ['qty' => 1])
            ->assertOk();
        $this->assertSame(9, $part->fresh()->stock);

        $this->actingAs($kasir, 'sanctum')
            ->deleteJson("/api/work-orders/{$id}/services/{$service->id}")->assertOk();

        $this->actingAs($kasir, 'sanctum')
            ->postJson("/api/work-orders/{$id}/finish", ['discount' => 0])
            ->assertOk()->assertJsonPath('data.total', 50000);

        $this->actingAs($kasir, 'sanctum')
            ->getJson("/api/work-orders/{$id}")
            ->assertOk()
            ->assertJsonPath('data.services_total', 0)
            ->assertJsonPath('data.parts_total', 50000);

        $this->actingAs($kasir, 'sanctum')
            ->postJson("/api/work-orders/{$id}/pay", ['method' => 'tunai', 'amount' => 40000])
            ->assertStatus(422);

        $this->actingAs($kasir, 'sanctum')
            ->postJson("/api/work-orders/{$id}/pay", ['method' => 'tunai', 'amount' => 50000])
            ->assertOk();

        $this->assertDatabaseHas('payments', ['work_order_id' => $id, 'amount' => 50000]);
        $this->assertSame('dibayar', WorkOrder::find($id)->status->value);
    }

    public function test_public_note_patch_and_cancel_rejections(): void
    {
        $kasir = User::factory()->create(['role' => 'kasir']);
        $vehicle = Vehicle::factory()->create();
        $wo = $this->actingAs($kasir, 'sanctum')
            ->postJson('/api/work-orders', ['vehicle_id' => $vehicle->id, 'complaint' => 'x'])
            ->json('data');
        $id = $wo['id'];

        $this->actingAs($kasir, 'sanctum')
            ->patchJson("/api/work-orders/{$id}/public-note", ['public_note' => 'Rem depan aus'])
            ->assertOk()->assertJsonPath('data.public_note', 'Rem depan aus');

        $this->actingAs($kasir, 'sanctum')
            ->postJson("/api/work-orders/{$id}/cancel")->assertOk()->assertJsonPath('data.status', 'batal');

        $this->actingAs($kasir, 'sanctum')
            ->patchJson("/api/work-orders/{$id}/public-note", ['public_note' => 'x'])
            ->assertStatus(422);
    }
}
