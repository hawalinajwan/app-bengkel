<?php

namespace Tests\Feature;

use Tests\TestCase;

class HealthTest extends TestCase
{
    public function test_root_returns_application_status(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertJsonPath('status', 'ok');
    }
}
