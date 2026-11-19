<?php

namespace Tests\Feature;

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
