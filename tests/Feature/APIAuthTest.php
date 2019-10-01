<?php

namespace Tests\Feature;

use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class APIAuthTest extends TestCase
{
    use RefreshDatabase;

    private function getRegisterPayload(array $overrides = [])
    {
        return array_merge([
            'name' => 'Test User',
            'email' => 'some@example.com',
            'password' => 'password',
        ], $overrides);
    }

    public function invalidRegistrationDataProvider()
    {
        return [
            ['name', ['name' => '']],
            ['name', ['name' => 'q']],

            ['email', ['email' => '']],
            ['email', ['email' => 'some']],

            ['password', ['password' => '']],
            ['password', ['password' => 'pasword']],
        ];
    }

    /** @test */
    public function it_will_register_a_user()
    {
        $payload = $this->getRegisterPayload();

        $response = $this->postJson('api/auth/register', $payload);
        $response->assertSuccessful();
        $response->assertJsonStructure(['access_token', 'valid_until']);

        unset($payload['password']);
        $this->assertDatabaseHas('users', $payload);
    }

    /**
     * @test
     * @dataProvider invalidRegistrationDataProvider
     */
    public function it_will_not_register_a_user_with_invalid_payload($errorField, $payload)
    {
        $payload = $this->getRegisterPayload($payload);

        $response = $this->postJson('api/auth/register', $payload);
        $response->assertStatus(422);
        $response->assertJsonValidationErrors($errorField);
    }

    /** @test */
    public function it_will_log_a_user_in()
    {
        $user = factory(User::class)->create();

        $response = $this->postJson('api/auth/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);
        $response->assertSuccessful();
        $response->assertJsonStructure(['access_token', 'valid_until']);
    }

    /** @test */
    public function it_will_not_log_an_invalid_user_in()
    {
        factory(User::class)->create();

        $response = $this->postJson('api/auth/login', [
            'email' => 'some@example.com',
            'password' => 'password',
        ]);
        $response->assertUnauthorized();
        $response->assertJson(['message' => 'Invalid username or password.']);
    }

    /** @test */
    public function it_will_return_user_info_if_authenticated()
    {
        $user = factory(User::class)->create();

        $response = $this->actingAs($user)->getJson('api/auth/user');
        $response->assertSuccessful();
        $response->assertJson($user->toArray());
    }

    /** @test */
    public function it_will_not_return_user_info_if_unauthenticated()
    {
        $response = $this->getJson('api/auth/user');
        $response->assertUnauthorized();
        $response->assertJson(['message' => 'Unauthenticated.']);
    }

    /** @test */
    public function it_will_refresh_user_token()
    {
        $user = factory(User::class)->create();

        $response = $this->actingAs($user)->postJson('api/auth/refresh');
        $response->assertSuccessful();
        $response->assertJsonStructure(['access_token', 'valid_until']);
    }

    /** @test */
    public function it_will_log_a_user_out()
    {
        $user = factory(User::class)->create();

        $response = $this->actingAs($user)->postJson('api/auth/logout');
        $response->assertSuccessful();
        $response->assertJson(['message' => 'Successfully logged out.']);
    }
}
