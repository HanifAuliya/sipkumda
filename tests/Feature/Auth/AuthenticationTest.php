<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_screen_can_be_rendered()
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
    }

    public function test_users_can_authenticate_using_the_login_screen()
    {
        $user = User::factory()->create([
            'NIP' => '123456789',
            'password' => bcrypt('password'),
        ]);

        $response = $this->post('/login', [
            'NIP' => $user->NIP,
            'password' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard'));
    }

    public function test_users_can_not_authenticate_with_invalid_password()
    {
        $user = User::factory()->create([
            'NIP' => '123456789',
            'password' => bcrypt('password'),
        ]);

        $this->post('/login', [
            'NIP' => $user->NIP,
            'password' => 'wrong-password',
        ]);

        $this->assertGuest();
    }
}
