<?php

namespace Tests\Feature;

use App\Models\NotificationLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationLogTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_read_paginated_notification_logs(): void
    {
        $log = NotificationLog::create([
            'type' => 'order_selesai', 'channel' => 'email', 'recipient' => 'budi@example.test',
            'status' => 'terkirim', 'sent_at' => now(),
        ]);

        $this->actingAs(User::factory()->create(['role' => 'admin']), 'sanctum')
            ->getJson('/api/notification-logs')
            ->assertOk()
            ->assertJsonPath('data.0.id', $log->id)
            ->assertJsonPath('data.0.type', 'order_selesai')
            ->assertJsonPath('data.0.status', 'terkirim')
            ->assertJsonStructure(['data', 'links', 'meta']);
    }

    public function test_kasir_cannot_read_notification_logs(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'kasir']), 'sanctum')
            ->getJson('/api/notification-logs')
            ->assertForbidden();
    }
}
