<?php

namespace Tests\Feature;

use App\Models\PasswordRecoveryCode;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_page_links_to_forgot_password_form(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertSee(route('password.request'));

        $this->get(route('password.request'))
            ->assertOk()
            ->assertSee(route('password.verify'))
            ->assertSee('recovery_code');
    }

    public function test_recovery_code_moves_matching_account_to_password_form(): void
    {
        $user = User::factory()->create();
        $code = $this->issueCodeFor($user);

        $this->post(route('password.verify'), [
            'email' => $user->email,
            'recovery_code' => $code,
        ])
            ->assertRedirect()
            ->assertSessionHas('password_recovery_user_id', $user->id);

        $this->get(route('password.reset'))
            ->assertOk()
            ->assertSee($user->email)
            ->assertSee('password_confirmation');
    }

    public function test_valid_recovery_code_resets_password_and_cannot_be_reused(): void
    {
        $user = User::factory()->create(['password' => 'old-password']);
        $code = $this->issueCodeFor($user);

        $this->post(route('password.verify'), ['email' => $user->email, 'recovery_code' => $code])
            ->assertRedirect(route('password.reset'));

        $this->post(route('password.update'), [
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ])->assertRedirect(route('login'))
            ->assertSessionHas('status');

        $this->assertTrue(password_verify('new-password', $user->fresh()->password));
        $this->assertNotSame($user->getRememberToken(), $user->fresh()->getRememberToken());

        $this->post(route('password.verify'), ['email' => $user->email, 'recovery_code' => $code])
            ->assertSessionHasErrors('recovery_code');
    }

    public function test_reset_requires_a_confirmed_password_of_at_least_eight_characters(): void
    {
        $user = User::factory()->create();
        $code = $this->issueCodeFor($user);
        $this->post(route('password.verify'), ['email' => $user->email, 'recovery_code' => $code])
            ->assertRedirect(route('password.reset'));

        $this->post(route('password.update'), [
            'password' => 'short',
            'password_confirmation' => 'different',
        ])->assertSessionHasErrors(['password']);
    }

    public function test_recovery_form_requires_a_matching_email_and_code(): void
    {
        $user = User::factory()->create();
        $code = $this->issueCodeFor($user);

        $this->post(route('password.verify'), [
            'email' => 'other@example.com',
            'recovery_code' => $code,
        ])->assertSessionHasErrors('recovery_code');

        $this->post(route('password.verify'), [
            'email' => $user->email,
            'recovery_code' => 'wrong-code',
        ])->assertSessionHasErrors('recovery_code');
    }

    public function test_recovery_code_expires(): void
    {
        $user = User::factory()->create();
        $code = $this->issueCodeFor($user);
        PasswordRecoveryCode::where('user_id', $user->id)->update(['expires_at' => now()->subMinute()]);

        $this->post(route('password.verify'), [
            'email' => $user->email,
            'recovery_code' => $code,
        ])->assertSessionHasErrors('recovery_code');
    }

    public function test_admin_can_issue_a_single_use_recovery_code(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create();

        $this->actingAs($admin)
            ->get(route('admin.password-recovery-codes.index'))
            ->assertOk()
            ->assertSee($user->email);

        $response = $this->post(route('admin.password-recovery-codes.store'), ['user_id' => $user->id])
            ->assertRedirect(route('admin.password-recovery-codes.index'));

        $code = session('issuedRecoveryCode');
        $this->assertNotEmpty($code);
        $this->assertDatabaseHas('password_recovery_codes', [
            'user_id' => $user->id,
            'used_at' => null,
        ]);

        $storedHash = PasswordRecoveryCode::where('user_id', $user->id)->value('code_hash');
        $this->assertTrue(Hash::check($code, $storedHash));
    }

    public function test_only_administrators_can_issue_recovery_codes(): void
    {
        $user = User::factory()->create(['role' => 'employee']);

        $this->actingAs($user)
            ->get(route('admin.password-recovery-codes.index'))
            ->assertForbidden();

        $this->post(route('admin.password-recovery-codes.store'), ['user_id' => $user->id])
            ->assertForbidden();
    }

    public function test_issuing_a_new_code_invalidates_the_previous_code(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create();

        $this->actingAs($admin);
        $this->post(route('admin.password-recovery-codes.store'), ['user_id' => $user->id]);
        $firstCode = session('issuedRecoveryCode');

        $this->post(route('admin.password-recovery-codes.store'), ['user_id' => $user->id]);
        $secondCode = session('issuedRecoveryCode');

        $this->assertNotSame($firstCode, $secondCode);
        $this->assertDatabaseCount('password_recovery_codes', 1);

        $storedHash = PasswordRecoveryCode::where('user_id', $user->id)->value('code_hash');
        $this->assertFalse(Hash::check($firstCode, $storedHash));
        $this->assertTrue(Hash::check($secondCode, $storedHash));
    }

    private function issueCodeFor(User $user): string
    {
        $code = 'ABCD1234-EFGH5678-IJKL9012';

        PasswordRecoveryCode::create([
            'user_id' => $user->id,
            'code_hash' => Hash::make($code),
            'expires_at' => now()->addDays(30),
        ]);

        return $code;
    }
}
