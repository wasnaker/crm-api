<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Spine\Events\PasswordChanged;
use Spine\Models\User;
use Tests\TestCase;

/**
 * Moving the hash driver from bcrypt to argon2id must not lock anybody out.
 *
 * The guarantees under test: an existing bcrypt hash keeps verifying after the
 * switch, and is transparently upgraded on the next successful login.
 */
class PasswordHashUpgradeTest extends TestCase
{
    use RefreshDatabase;

    protected string $password = 'Legacy-Staple3@x';

    protected function makeUser(string $driver, array $options = []): User
    {
        $previous = config('hashing.driver');
        config()->set('hashing.driver', $driver);
        $hash = Hash::make($this->password, $options);
        config()->set('hashing.driver', $previous);

        $user = User::factory()->create(['email' => 'legacy@spine.test']);

        // Written through the query builder, not the model, on purpose.
        // The 'hashed' cast verifies that an assigned value matches the
        // *current* driver config and throws otherwise, which is the right
        // production behaviour but makes it impossible to stage a legacy hash.
        // A real pre-migration database just has the old hash sitting in the
        // column, so that is what is reproduced here.
        $user->newQuery()->whereKey($user->id)->update(['password' => $hash]);

        return $user->fresh();
    }

    protected function algorithmOf(User $user): string
    {
        $hash = (string) $user->fresh()->password;

        return match (true) {
            str_starts_with($hash, '$argon2id$') => 'argon2id',
            str_starts_with($hash, '$argon2i$') => 'argon',
            str_starts_with($hash, '$2y$') => 'bcrypt',
            default => 'unknown',
        };
    }

    public function test_the_default_driver_is_argon2id(): void
    {
        $this->assertSame('argon2id', config('hashing.driver'));
        $this->assertSame(65536, config('hashing.argon.memory'));
    }

    public function test_algorithm_verification_stays_disabled(): void
    {
        // Regression guard. With verify on, Argon2IdHasher::check() throws for
        // any non-argon2id hash, so a pre-migration bcrypt account gets a 500
        // instead of a login. That would make the driver switch lock out every
        // existing user and would stop needsRehash() from ever running.
        $this->assertFalse(config('hashing.argon.verify'));
        $this->assertFalse(config('hashing.bcrypt.verify'));
    }

    public function test_a_legacy_hash_does_not_throw_on_check(): void
    {
        // The direct expression of the failure: password_verify accepts any
        // algorithm, the config-driven guard would not.
        $legacy = password_hash($this->password, PASSWORD_BCRYPT, ['cost' => 12]);

        $this->assertStringStartsWith('$2y$', $legacy);
        $this->assertStringNotContainsString('argon2id', $legacy);
        $this->assertTrue(Hash::check($this->password, $legacy));
        $this->assertTrue(Hash::needsRehash($legacy));
    }

    public function test_new_passwords_are_hashed_with_argon2id(): void
    {
        $user = User::factory()->create();

        $this->assertSame('argon2id', $this->algorithmOf($user));
    }

    public function test_a_bcrypt_account_still_logs_in_after_the_switch(): void
    {
        $user = $this->makeUser('bcrypt', ['cost' => 12]);

        $this->assertSame('bcrypt', $this->algorithmOf($user));

        $this->postJson('/api/v1/auth/login', [
            'email' => 'legacy@spine.test',
            'password' => $this->password,
        ])->assertOk();
    }

    public function test_a_bcrypt_hash_is_upgraded_on_successful_login(): void
    {
        $user = $this->makeUser('bcrypt', ['cost' => 12]);

        $this->assertSame('bcrypt', $this->algorithmOf($user));

        $this->postJson('/api/v1/auth/login', [
            'email' => 'legacy@spine.test',
            'password' => $this->password,
        ])->assertOk();

        $this->assertSame('argon2id', $this->algorithmOf($user));
    }

    public function test_the_upgraded_hash_still_verifies_the_same_password(): void
    {
        $user = $this->makeUser('bcrypt', ['cost' => 12]);

        $this->postJson('/api/v1/auth/login', [
            'email' => 'legacy@spine.test',
            'password' => $this->password,
        ])->assertOk();

        $this->assertTrue(Hash::check($this->password, $user->fresh()->password));

        $this->postJson('/api/v1/auth/login', [
            'email' => 'legacy@spine.test',
            'password' => 'wrong-password',
        ])->assertStatus(422);
    }

    public function test_a_failed_login_does_not_upgrade_the_hash(): void
    {
        $user = $this->makeUser('bcrypt', ['cost' => 12]);

        $this->postJson('/api/v1/auth/login', [
            'email' => 'legacy@spine.test',
            'password' => 'wrong-password',
        ])->assertStatus(422);

        $this->assertSame(
            'bcrypt',
            $this->algorithmOf($user),
            'an unverified password must never be written back to the account'
        );
    }

    public function test_an_already_current_hash_is_not_rewritten(): void
    {
        $user = $this->makeUser('argon2id');
        $before = $user->fresh()->password;

        $this->postJson('/api/v1/auth/login', [
            'email' => 'legacy@spine.test',
            'password' => $this->password,
        ])->assertOk();

        $this->assertSame(
            $before,
            $user->fresh()->password,
            'needsRehash() returned false, so the stored hash must be untouched'
        );
    }

    public function test_a_weak_bcrypt_cost_is_also_upgraded(): void
    {
        // cost 4 is far below the current floor; needsRehash() must catch
        // strength regressions, not just algorithm changes.
        $user = $this->makeUser('bcrypt', ['cost' => 4]);

        $this->assertSame('bcrypt', $this->algorithmOf($user));

        $this->postJson('/api/v1/auth/login', [
            'email' => 'legacy@spine.test',
            'password' => $this->password,
        ])->assertOk();

        $this->assertSame('argon2id', $this->algorithmOf($user));
    }

    public function test_an_argon2i_hash_is_upgraded_to_argon2id(): void
    {
        $user = $this->makeUser('argon');

        $this->assertSame('argon', $this->algorithmOf($user));

        $this->postJson('/api/v1/auth/login', [
            'email' => 'legacy@spine.test',
            'password' => $this->password,
        ])->assertOk();

        $this->assertSame('argon2id', $this->algorithmOf($user));
    }

    public function test_the_upgrade_survives_a_password_change(): void
    {
        $user = $this->makeUser('bcrypt', ['cost' => 12]);

        $this->postJson('/api/v1/auth/login', [
            'email' => 'legacy@spine.test',
            'password' => $this->password,
        ])->assertOk();

        $token = $user->fresh()->createToken('after-upgrade')->plainTextToken;

        Event::fake([PasswordChanged::class]);

        $this->withToken($token)->putJson('/api/v1/auth/password', [
            'current_password' => $this->password,
            'new_password' => 'Rotated-Staple9@z',
            'new_password_confirmation' => 'Rotated-Staple9@z',
        ])->assertOk();

        $this->assertSame('argon2id', $this->algorithmOf($user));
        $this->assertTrue(Hash::check('Rotated-Staple9@z', $user->fresh()->password));
    }

    public function test_two_accounts_with_the_same_password_get_different_hashes(): void
    {
        // Per-hash salting must survive the driver switch.
        $previous = config('hashing.driver');
        config()->set('hashing.driver', 'argon2id');
        $a = Hash::make($this->password);
        $b = Hash::make($this->password);
        config()->set('hashing.driver', $previous);

        $this->assertNotSame($a, $b);
        $this->assertNotSame(
            substr($a, strrpos($a, '$') + 1),
            substr($b, strrpos($b, '$') + 1),
            'the per-hash salt segment must differ'
        );
    }

    public function test_register_hashes_with_the_current_driver(): void
    {
        $this->postJson('/api/v1/auth/register', [
            'name' => 'New Person',
            'email' => 'fresh@spine.test',
            'password' => 'Brand-New7!pass',
        ])->assertCreated();

        $this->assertSame(
            'argon2id',
            $this->algorithmOf(User::where('email', 'fresh@spine.test')->first())
        );
    }
}
