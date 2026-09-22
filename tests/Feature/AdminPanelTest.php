<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminPanelTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_sent_to_admin_login(): void
    {
        $this->get('/admin')->assertRedirect(route('admin.login'));
    }

    public function test_admin_can_open_every_record_list(): void
    {
        $admin = User::factory()->create([
            'email' => 'admin@falcore.test',
            'is_admin' => true,
        ]);

        $this->actingAs($admin)
            ->get('/admin')
            ->assertOk()
            ->assertSee('Dashboard');

        foreach (['airlines', 'airports', 'flights', 'bookings', 'passengers', 'users', 'messages', 'subscribers'] as $section) {
            $this->actingAs($admin)
                ->get('/admin/'.$section)
                ->assertOk();
        }
    }

    public function test_non_admin_cannot_open_the_panel(): void
    {
        $user = User::factory()->create(['is_admin' => false]);

        $this->actingAs($user)
            ->get('/admin')
            ->assertRedirect(route('admin.login'));
    }
}
