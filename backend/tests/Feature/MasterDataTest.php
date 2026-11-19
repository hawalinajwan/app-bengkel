<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Mechanic;
use App\Models\Sparepart;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MasterDataTest extends TestCase
{
    use RefreshDatabase;

    private function kasir(): User
    {
        return User::factory()->create(['role' => 'kasir']);
    }

    public function test_kasir_can_crud_customers_and_search_paginate(): void
    {
        $kasir = $this->kasir();
        Customer::factory()->count(3)->create();

        $this->actingAs($kasir, 'sanctum')
            ->getJson('/api/customers?search=TidakAda&per_page=2')
            ->assertOk()
            ->assertJsonPath('meta.total', 0)
            ->assertJsonPath('meta.per_page', 2);

        $created = $this->actingAs($kasir, 'sanctum')
            ->postJson('/api/customers', ['name' => 'Budi', 'phone' => '0812'])
            ->assertStatus(201)
            ->json('data');

        $this->actingAs($kasir, 'sanctum')
            ->putJson("/api/customers/{$created['id']}", ['name' => 'Budi Santoso'])
            ->assertOk()
            ->assertJsonPath('data.name', 'Budi Santoso');

        $this->actingAs($kasir, 'sanctum')
            ->deleteJson("/api/customers/{$created['id']}")
            ->assertOk();

        $this->assertDatabaseMissing('customers', ['id' => $created['id']]);
    }

    public function test_plate_number_is_normalized(): void
    {
        $kasir = $this->kasir();
        $customer = Customer::factory()->create();

        $this->actingAs($kasir, 'sanctum')->postJson('/api/vehicles', [
            'customer_id' => $customer->id,
            'plate_number' => 'b 1234 xyz',
            'type' => 'mobil',
            'brand' => 'Toyota',
            'model' => 'Avanza',
        ])->assertStatus(201)->assertJsonPath('data.plate_number', 'B1234XYZ');
    }

    public function test_kasir_cannot_write_master_but_admin_can(): void
    {
        $payload = ['name' => 'Joko', 'phone' => '08123', 'is_active' => true];

        $this->actingAs($this->kasir(), 'sanctum')
            ->postJson('/api/mechanics', $payload)
            ->assertStatus(403);

        $this->actingAs(User::factory()->create(['role' => 'admin']), 'sanctum')
            ->postJson('/api/mechanics', $payload)
            ->assertStatus(201);
    }

    public function test_delete_used_customer_returns_422(): void
    {
        $kasir = $this->kasir();
        $customer = Customer::factory()->create();
        Vehicle::factory()->create(['customer_id' => $customer->id]);

        $this->actingAs($kasir, 'sanctum')
            ->deleteJson("/api/customers/{$customer->id}")
            ->assertStatus(422)
            ->assertJsonPath('errors.id.0', 'Data tidak bisa dihapus karena masih dipakai data lain.');
    }

    public function test_sparepart_low_stock_filter(): void
    {
        Sparepart::factory()->create(['stock' => 2, 'min_stock' => 5]);
        Sparepart::factory()->create(['stock' => 20, 'min_stock' => 5]);

        $response = $this->actingAs($this->kasir(), 'sanctum')
            ->getJson('/api/spareparts?low_stock=1')
            ->assertOk();

        $this->assertCount(1, $response->json('data'));
        $this->assertTrue($response->json('data.0.low_stock'));
    }

    public function test_master_endpoints_require_token(): void
    {
        $this->getJson('/api/mechanics')->assertStatus(401);
        $this->getJson('/api/customers')->assertStatus(401);
    }
}
