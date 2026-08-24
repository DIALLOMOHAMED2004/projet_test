<?php

namespace Tests\Feature;

use App\Modules\Administration\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationFoundationTest extends TestCase
{
    use RefreshDatabase;

    public function test_authentication_provider_uses_the_administration_user_model(): void
    {
        $this->assertSame(User::class, config('auth.providers.users.model'));
    }

    public function test_user_can_create_a_sanctum_token(): void
    {
        $user = User::factory()->create();

        $token = $user->createToken('test-token');

        $this->assertNotEmpty($token->plainTextToken);
        $this->assertDatabaseHas('personal_access_tokens', [
            'tokenable_type' => User::class,
            'tokenable_id' => $user->getKey(),
            'name' => 'test-token',
        ]);
    }
}
