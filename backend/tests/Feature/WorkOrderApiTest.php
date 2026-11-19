<?php

namespace Tests\Feature;

use App\Models\{Mechanic,Service,Sparepart,User,Vehicle,WorkOrder};
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
