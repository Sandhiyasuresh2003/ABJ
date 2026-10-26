<?php

namespace Tests\Feature;

use App\Models\PasswordRecoveryCode;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class DashboardBackgroundTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_uses_the_supplied_background_image(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('app-background')
            ->assertSee(asset('images/dashboard-background.png'))
            ->assertSee('background-size:100% auto')
            ->assertSee('background-position:center top')
            ->assertSee('background-attachment:scroll')
            ->assertSee(asset('images/abj-builders-logo.png'));
    }

    public function test_other_application_pages_use_the_same_background_image(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->get(route('employees.index'))
            ->assertOk()
            ->assertSee('app-background')
            ->assertSee(asset('images/dashboard-background.png'));
    }

    public function test_login_and_password_pages_display_the_site_logo(): void
    {
        $logo = asset('images/abj-builders-logo.png');

        $this->get(route('login'))
            ->assertOk()
            ->assertSee($logo);

        $this->get(route('password.request'))
            ->assertOk()
            ->assertSee($logo);

        $this->get(route('password.reset'))
            ->assertRedirect(route('password.request'));

        $user = User::factory()->create();
        $this->post(route('password.verify'), [
            'email' => $user->email,
            'recovery_code' => $this->issueRecoveryCode($user),
        ])->assertRedirect(route('password.reset'));

        $this->get(route('password.reset'))
            ->assertOk()
            ->assertSee($logo)
            ->assertSee($user->email);
    }

    private function issueRecoveryCode(User $user): string
    {
        $code = 'TEST-CODE-123456';

        PasswordRecoveryCode::create([
            'user_id' => $user->id,
            'code_hash' => Hash::make($code),
            'expires_at' => now()->addDay(),
        ]);

        return $code;
    }
}
