<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spine\Events\PasswordChanged;
use Spine\Models\User;
use Tests\TestCase;

class ChangePasswordTest extends TestCase
{
    use RefreshDatabase;

    protected string $oldPassword = 'CorrectHorseBattery1!';

    protected string $newPassword = 'DifferentStaple2@';

    protected function setUp(): void
    {
        parent::setUp();

        Event::fake([PasswordChanged::class]);

        $this->user = User::factory()->create([
            'email' => 'staff@spine.test',
            'password' => Hash::make($this->oldPassword),
        ]);
    }

    protected User $user;

    protected function token(): string
    {
        return $this->user->createToken('test-device')->plainTextToken;
    }

    protected function payload(array $overrides = []): array
    {
        return array_merge([
            'current_password' => $this->oldPassword,
            'new_password' => $this->newPassword,
            'new_password_confirmation' => $this->newPassword,
        ], $overrides);
    }

    public function test_the_route_exists(): void
    {
        $this->assertTrue(
            collect(\Illuminate\Support\Facades\Route::getRoutes()->getRoutes())
                ->contains(fn ($r) => $r->uri() === 'api/v1/auth/password'
                    && in_array('PUT', $r->methods(), true)),
            'PUT api/v1/auth/password is not registered'
        );
    }

    public function test_it_requires_authentication(): void
    {
        $this->putJson('/api/v1/auth/password', $this->payload())->assertUnauthorized();

        $this->assertTrue(Hash::check($this->oldPassword, $this->user->fresh()->password));
    }

    public function test_a_valid_change_succeeds_and_returns_the_expected_contract(): void
    {
        $response = $this->withToken($this->token())
            ->putJson('/api/v1/auth/password', $this->payload());

        $response->assertOk();

        // Profile.tsx:89 reads data.success and calls setSuccess(false) without it.
        $this->assertTrue($response->json('success'));
        $this->assertSame('Password updated.', $response->json('message'));
        $this->assertIsInt($response->json('revoked_sessions'));

        $this->assertTrue(
            Hash::check($this->newPassword, $this->user->fresh()->password),
            'the new hash was not persisted'
        );
    }

    public function test_the_old_password_stops_working(): void
    {
        $this->withToken($this->token())
            ->putJson('/api/v1/auth/password', $this->payload())
            ->assertOk();

        $this->postJson('/api/v1/auth/login', [
            'email' => 'staff@spine.test',
            'password' => $this->oldPassword,
        ])->assertJsonValidationErrors('email');

        $this->postJson('/api/v1/auth/login', [
            'email' => 'staff@spine.test',
            'password' => $this->newPassword,
        ])->assertOk();
    }

    public function test_a_wrong_current_password_is_rejected_and_changes_nothing(): void
    {
        $this->withToken($this->token())
            ->putJson('/api/v1/auth/password', $this->payload(['current_password' => 'wrong-password']))
            ->assertStatus(422)
            ->assertJsonValidationErrors('current_password');

        $this->assertTrue(Hash::check($this->oldPassword, $this->user->fresh()->password));
    }

    public function test_a_mismatched_confirmation_is_rejected(): void
    {
        $this->withToken($this->token())
            ->putJson('/api/v1/auth/password', $this->payload([
                'new_password_confirmation' => 'Something-Else3@',
            ]))
            ->assertStatus(422)
            ->assertJsonValidationErrors('new_password');
    }

    #[DataProvider('weakPasswordProvider')]
    public function test_weak_passwords_are_rejected(string $password): void
    {
        $this->withToken($this->token())
            ->putJson('/api/v1/auth/password', $this->payload([
                'new_password' => $password,
                'new_password_confirmation' => $password,
            ]))
            ->assertStatus(422)
            ->assertJsonValidationErrors('new_password');

        $this->assertTrue(Hash::check($this->oldPassword, $this->user->fresh()->password));
    }

    public static function weakPasswordProvider(): array
    {
        return [
            'too short' => ['Ab1!xy'],
            'no uppercase' => ['nouppercase1!'],
            'no lowercase' => ['NOLOWERCASE1!'],
            'no number' => ['NoNumbersHere!'],
            'no symbol' => ['NoSymbolsHere1'],
        ];
    }

    public function test_reusing_the_current_password_is_rejected(): void
    {
        $this->withToken($this->token())
            ->putJson('/api/v1/auth/password', $this->payload([
                'new_password' => $this->oldPassword,
                'new_password_confirmation' => $this->oldPassword,
            ]))
            ->assertStatus(422)
            ->assertJsonValidationErrors('new_password');
    }

    public function test_the_performing_token_survives_so_the_session_continues(): void
    {
        $token = $this->token();

        $this->withToken($token)->putJson('/api/v1/auth/password', $this->payload())->assertOk();

        // The frontend immediately PUTs /auth/me with the same session.
        $this->assertSame(200, $this->statusForToken($token));
    }

    public function test_other_sessions_are_revoked(): void
    {
        $phone = $this->user->createToken('phone')->plainTextToken;
        $laptop = $this->user->createToken('laptop')->plainTextToken;
        $current = $this->token();

        $this->assertSame(3, $this->user->tokens()->count());

        $response = $this->withToken($current)
            ->putJson('/api/v1/auth/password', $this->payload());

        $response->assertOk();
        $this->assertSame(2, $response->json('revoked_sessions'));

        // Assert the rows, not only the count: this is the actual guarantee.
        $this->assertSame(1, $this->user->fresh()->tokens()->count());
        $this->assertSame('test-device', $this->user->fresh()->tokens()->first()->name);

        // The guard caches the user it already resolved and the container is not
        // rebuilt between requests inside one test, so a revoked token would
        // still appear to authenticate unless the guards are dropped before
        // every call. Each real request gets a fresh container; the test has to
        // model that or the assertion passes for the wrong reason.
        $this->assertSame(200, $this->statusForToken($current));
        $this->assertSame(401, $this->statusForToken($phone));
        $this->assertSame(401, $this->statusForToken($laptop));
    }

    /**
     * The status a fresh request bearing $token gets.
     */
    protected function statusForToken(string $token): int
    {
        \Illuminate\Support\Facades\Auth::forgetGuards();

        return $this->withToken($token)->getJson('/api/v1/auth/me')->status();
    }

    public function test_it_dispatches_the_password_changed_event(): void
    {
        $token = $this->token();

        $this->withToken($token)->putJson('/api/v1/auth/password', $this->payload())->assertOk();

        Event::assertDispatched(PasswordChanged::class, function (PasswordChanged $event) use ($token) {
            return $event->user->is($this->user)
                && $event->revokedSessions === 0
                && $event->tokenId !== null;
        });
    }

    public function test_any_authenticated_account_may_change_its_own_password(): void
    {
        // Deliberately no role and no permissions: self-service rotation must
        // not require the staff.password.change grant.
        $bare = User::factory()->create([
            'email' => 'bare@spine.test',
            'password' => Hash::make($this->oldPassword),
        ]);

        $this->assertSame(0, $bare->getAllPermissions()->count());

        $this->withToken($bare->createToken('bare')->plainTextToken)
            ->putJson('/api/v1/auth/password', $this->payload())
            ->assertOk();

        $this->assertTrue(Hash::check($this->newPassword, $bare->fresh()->password));
    }

    public function test_missing_fields_are_rejected(): void
    {
        $this->withToken($this->token())
            ->putJson('/api/v1/auth/password', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['current_password', 'new_password']);
    }

    public function test_the_password_is_never_returned_or_echoed(): void
    {
        $response = $this->withToken($this->token())
            ->putJson('/api/v1/auth/password', $this->payload())
            ->assertOk();

        $body = $response->getContent();

        $this->assertStringNotContainsString($this->newPassword, $body);
        $this->assertStringNotContainsString($this->oldPassword, $body);
    }

    public function test_a_long_password_is_accepted(): void
    {
        $long = Str::random(72) . 'Aa1!';

        $this->withToken($this->token())
            ->putJson('/api/v1/auth/password', $this->payload([
                'new_password' => $long,
                'new_password_confirmation' => $long,
            ]))
            ->assertOk();
    }
}
