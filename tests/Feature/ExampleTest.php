<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Database\Seeders\UserSeeder;
use Livewire\Livewire;
use App\Livewire\Auth\Login;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        $this->seed(UserSeeder::class);
    }

    /**
     * Unauthenticated guest is redirected to login.
     */
    public function test_guest_is_redirected_to_login(): void
    {
        $response = $this->get('/');
        $response->assertRedirect('/login');
    }

    /**
     * Authenticated user is redirected to tables.
     */
    public function test_authenticated_user_accesses_tables(): void
    {
        $user = User::where('email', 'admin@jsfood.dev')->first();
        $response = $this->actingAs($user)->get('/');
        $response->assertRedirect('/tables');
    }

    /**
     * Login component renders and authenticates.
     */
    public function test_login_component_renders_and_logs_in(): void
    {
        Livewire::test(Login::class)
            ->assertStatus(200)
            ->assertSee('FOOD')
            ->assertSee('admin@jsfood.dev')
            ->call('quickLogin', 'admin@jsfood.dev')
            ->assertRedirect(route('tables.index'));

        $this->assertAuthenticated();
    }
}
