<?php

namespace Tests\Feature;

use App\Models\Mechanic;
use App\Models\Service;
use App\Models\Sparepart;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_is_available_to_admin_and_kasir(): void
    {
        foreach (['admin', 'kasir'] as $role) {
            $this->actingAs(User::factory()->create(['role' => $role]), 'sanctum')
                ->getJson('/api/dashboard')->assertOk()->assertJsonStructure(['data' => ['counts', 'revenue', 'low_stock']]);
        }
    }

    public function test_dashboard_includes_low_stock(): void
    {
        $part = Sparepart::factory()->create(['stock' => 2, 'min_stock' => 5]);
        $this->actingAs(User::factory()->create(['role' => 'admin']), 'sanctum')
            ->getJson('/api/dashboard')->assertOk()->assertJsonPath('data.low_stock.0.id', $part->id);
    }

    public function test_dashboard_includes_action_items(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'admin']), 'sanctum')
            ->getJson('/api/dashboard')->assertOk()
            ->assertJsonStructure(['data' => ['action_items' => ['pending_bookings', 'unassigned_orders', 'unpaid_orders']]]);
    }

    public function test_dashboard_summarises_today_payments_and_unpaid_orders(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $mechanic = Mechanic::factory()->create(['is_active' => true]);
        $service = Service::factory()->create(['price' => 150000]);

        $paid = $this->actingAs($admin, 'sanctum')
            ->postJson('/api/work-orders', [
                'customer' => ['name' => 'Bayu', 'phone' => '081211112222'],
                'vehicle' => ['plate_number' => 'D 1234 AB', 'brand' => 'Daihatsu', 'model' => 'Xenia'],
                'complaint' => 'Servis rutin',
            ])->json('data');

        $this->actingAs($admin, 'sanctum')->postJson("/api/work-orders/{$paid['id']}/start", ['mechanic_id' => $mechanic->id])->assertOk();
        $this->actingAs($admin, 'sanctum')->postJson("/api/work-orders/{$paid['id']}/services", ['service_id' => $service->id])->assertOk();
        $this->actingAs($admin, 'sanctum')->postJson("/api/work-orders/{$paid['id']}/finish", ['discount' => 0])->assertOk();
        $this->actingAs($admin, 'sanctum')->postJson("/api/work-orders/{$paid['id']}/pay", ['method' => 'tunai', 'amount' => 150000])->assertOk();

        $unpaid = $this->actingAs($admin, 'sanctum')
            ->postJson('/api/work-orders', [
                'customer' => ['name' => 'Sari'],
                'vehicle' => ['plate_number' => 'D 5555 CD', 'brand' => 'Toyota', 'model' => 'Yaris'],
                'complaint' => 'Servis rutin',
            ])->json('data');
        $this->actingAs($admin, 'sanctum')->postJson("/api/work-orders/{$unpaid['id']}/start", ['mechanic_id' => $mechanic->id])->assertOk();
        $this->actingAs($admin, 'sanctum')->postJson("/api/work-orders/{$unpaid['id']}/services", ['service_id' => $service->id])->assertOk();
        $this->actingAs($admin, 'sanctum')->postJson("/api/work-orders/{$unpaid['id']}/finish", ['discount' => 0])->assertOk();

        $this->actingAs($admin, 'sanctum')->getJson('/api/dashboard')
            ->assertOk()
            ->assertJsonPath('data.payments.total', 150000)
            ->assertJsonPath('data.payments.count', 1)
            ->assertJsonPath('data.payments.by_method.tunai', 150000)
            ->assertJsonPath('data.payments.recent.0.plate_number', 'D1234AB')
            ->assertJsonPath('data.unpaid_orders.0.id', $unpaid['id'])
            ->assertJsonPath('data.action_items.unpaid_orders', 1)
            ->assertJsonPath('data.revenue', 150000);
    }

    public function test_daily_report_validates_date_and_is_admin_only(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin, 'sanctum')->getJson('/api/reports/daily?date=2026-01-15')
            ->assertOk()->assertJsonPath('data.date', '2026-01-15');
        $this->actingAs($admin, 'sanctum')->getJson('/api/reports/daily?date=bad')->assertStatus(422);
        $this->actingAs(User::factory()->create(['role' => 'kasir']), 'sanctum')
            ->getJson('/api/reports/daily?date=2026-01-15')->assertForbidden();
    }
}
