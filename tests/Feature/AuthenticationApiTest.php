<?php

namespace Tests\Feature;

use App\Modules\Administration\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthenticationApiTest extends TestCase
{
    use RefreshDatabase;

    private const PASSWORD = 'SecurePass123';

    public function test_user_can_sign_up(): void
    {
        $response = $this->postJson('/api/v1/auth/signup', [
            'name' => 'Test User',
            'email' => 'user@example.com',
            'password' => self::PASSWORD,
            'password_confirmation' => self::PASSWORD,
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('user.name', 'Test User')
            ->assertJsonPath('user.email', 'user@example.com')
            ->assertJsonStructure(['user' => ['id', 'name', 'email'], 'token'])
            ->assertJsonMissingPath('user.password');

        $user = User::where('email', 'user@example.com')->firstOrFail();

        $this->assertTrue(Hash::check(self::PASSWORD, $user->password));
        $this->assertNotSame(self::PASSWORD, $user->password);
        $this->assertDatabaseCount('personal_access_tokens', 1);
    }

    public function test_signup_requires_a_name(): void
    {
        $this->postJson('/api/v1/auth/signup', $this->validSignupData(['name' => null]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('name');
    }

    public function test_signup_requires_an_email(): void
    {
        $this->postJson('/api/v1/auth/signup', $this->validSignupData(['email' => null]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('email');
    }

    public function test_signup_requires_a_valid_email(): void
    {
        $this->postJson('/api/v1/auth/signup', $this->validSignupData(['email' => 'invalid']))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('email');
    }

    public function test_signup_requires_a_unique_email(): void
    {
        User::factory()->create(['email' => 'user@example.com']);

        $this->postJson('/api/v1/auth/signup', $this->validSignupData())
            ->assertUnprocessable()
            ->assertJsonValidationErrors('email');
    }

    public function test_signup_requires_a_password(): void
    {
        $this->postJson('/api/v1/auth/signup', $this->validSignupData([
            'password' => null,
            'password_confirmation' => null,
        ]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('password');
    }

    public function test_signup_rejects_a_weak_password(): void
    {
        $this->postJson('/api/v1/auth/signup', $this->validSignupData([
            'password' => 'password',
            'password_confirmation' => 'password',
        ]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('password');
    }

    public function test_signup_requires_matching_password_confirmation(): void
    {
        $this->postJson('/api/v1/auth/signup', $this->validSignupData([
            'password_confirmation' => 'DifferentPass123',
        ]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('password');
    }

    public function test_user_can_log_in(): void
    {
        $user = User::factory()->create(['password' => self::PASSWORD]);

        $this->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => self::PASSWORD,
        ])
            ->assertOk()
            ->assertJsonPath('user.id', $user->id)
            ->assertJsonStructure(['user' => ['id', 'name', 'email'], 'token'])
            ->assertJsonMissingPath('user.password');

        $this->assertDatabaseCount('personal_access_tokens', 1);
    }

    public function test_login_rejects_an_incorrect_password(): void
    {
        $user = User::factory()->create(['password' => self::PASSWORD]);

        $this->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => 'WrongPassword123',
        ])
            ->assertUnauthorized()
            ->assertExactJson(['message' => 'Identifiants invalides.']);
    }

    public function test_login_rejects_an_unknown_email(): void
    {
        $this->postJson('/api/v1/auth/login', [
            'email' => 'missing@example.com',
            'password' => self::PASSWORD,
        ])
            ->assertUnauthorized()
            ->assertExactJson(['message' => 'Identifiants invalides.']);
    }

    public function test_login_requires_an_email(): void
    {
        $this->postJson('/api/v1/auth/login', ['password' => self::PASSWORD])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('email');
    }

    public function test_login_requires_a_password(): void
    {
        $this->postJson('/api/v1/auth/login', ['email' => 'user@example.com'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('password');
    }

    public function test_authenticated_user_can_be_retrieved(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token')->plainTextToken;

        $this->withToken($token)
            ->getJson('/api/v1/auth/me')
            ->assertOk()
            ->assertJsonPath('user.id', $user->id)
            ->assertJsonMissingPath('user.password');
    }

    public function test_me_rejects_a_request_without_a_token(): void
    {
        $this->getJson('/api/v1/auth/me')
            ->assertUnauthorized()
            ->assertJsonStructure(['message']);
    }

    public function test_me_rejects_an_invalid_token(): void
    {
        $this->withToken('invalid-token')
            ->getJson('/api/v1/auth/me')
            ->assertUnauthorized()
            ->assertJsonStructure(['message']);
    }

    public function test_logout_revokes_only_the_current_token(): void
    {
        $user = User::factory()->create(['password' => self::PASSWORD]);
        $otherToken = $user->createToken('other-token')->plainTextToken;

        $loginResponse = $this->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => self::PASSWORD,
        ])->assertOk();

        $currentToken = $loginResponse->json('token');

        $this->withToken($currentToken)
            ->getJson('/api/v1/auth/me')
            ->assertOk()
            ->assertJsonPath('user.id', $user->id);

        $this->withToken($currentToken)
            ->postJson('/api/v1/auth/logout')
            ->assertOk()
            ->assertExactJson(['message' => 'Déconnexion réussie.']);

        $this->assertDatabaseCount('personal_access_tokens', 1);

        // Each production request resolves a fresh guard; the test application is reused.
        $this->app['auth']->forgetGuards();

        $this->withToken($currentToken)
            ->getJson('/api/v1/auth/me')
            ->assertUnauthorized();

        $this->withToken($otherToken)
            ->getJson('/api/v1/auth/me')
            ->assertOk()
            ->assertJsonPath('user.id', $user->id);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function validSignupData(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Test User',
            'email' => 'user@example.com',
            'password' => self::PASSWORD,
            'password_confirmation' => self::PASSWORD,
        ], $overrides);
    }
}
