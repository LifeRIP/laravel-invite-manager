<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_register_and_login(): void
    {
        $registerResponse = $this->postJson('/api/v1/auth/register', [
            'name' => 'Alice',
            'email' => 'alice@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'device_name' => 'phpunit',
        ]);

        $registerResponse
            ->assertCreated()
            ->assertJsonStructure([
                'token',
                'user' => ['id', 'name', 'email'],
            ]);

        $loginResponse = $this->postJson('/api/v1/auth/login', [
            'email' => 'alice@example.com',
            'password' => 'password123',
            'device_name' => 'phpunit-login',
        ]);

        $loginResponse
            ->assertOk()
            ->assertJsonStructure([
                'token',
                'user' => ['id', 'name', 'email'],
            ]);
    }
}
