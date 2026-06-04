<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_page_loads_successfully(): void
    {
        $response = $this->get('/login');
        $response->assertStatus(200);
    }

    public function test_pos_page_loads_for_cashier(): void
    {
        $user = User::factory()->create(['role' => 'cashier']);
        $response = $this->actingAs($user)->get('/pos');
        $response->assertStatus(200);
    }

    public function test_kds_page_loads_for_chef(): void
    {
        $user = User::factory()->create(['role' => 'chef']);
        $response = $this->actingAs($user)->get('/kds');
        $response->assertStatus(200);
    }

    public function test_admin_stats_page_loads_for_admin(): void
    {
        $user = User::factory()->create(['role' => 'admin']);
        $response = $this->actingAs($user)->get('/admin');
        $response->assertStatus(200);
    }

    public function test_admin_inventory_page_loads_for_admin(): void
    {
        $user = User::factory()->create(['role' => 'admin']);
        $response = $this->actingAs($user)->get('/admin/inventory');
        $response->assertStatus(200);
    }

    public function test_admin_orders_page_loads_for_admin(): void
    {
        $user = User::factory()->create(['role' => 'admin']);
        $response = $this->actingAs($user)->get('/admin/orders');
        $response->assertStatus(200);
    }
}
