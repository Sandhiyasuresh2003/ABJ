<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\User;
use App\Notifications\AccountSetupNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class EmployeeRegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_administrator_can_open_the_employee_registration_page(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->get(route('employees.create'))
            ->assertOk()
            ->assertSee('Employee registration')
            ->assertSee('name="employee_code"', false)
            ->assertSee('name="department"', false)
            ->assertSee('name="joining_date"', false)
            ->assertSee('name="role"', false)
            ->assertSee('Register employee');
    }

    public function test_an_employee_cannot_open_the_employee_registration_page(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'employee']))
            ->get(route('employees.create'))
            ->assertForbidden();
    }

    public function test_an_administrator_can_register_an_employee(): void
    {
        Notification::fake();

        $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->post(route('employees.store'), [
                'employee_code' => 'ABJ-101',
                'name' => 'Asha Kumar',
                'email' => 'asha@example.com',
                'role' => 'employee',
                'phone' => '9876543210',
                'department' => 'Sales',
                'designation' => 'Sales Executive',
                'joining_date' => '2026-10-01',
                'employment_status' => 'Active',
                'address' => 'Chennai',
            ])
            ->assertRedirect(route('employees.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('employees', [
            'employee_code' => 'ABJ-101',
            'name' => 'Asha Kumar',
            'email' => 'asha@example.com',
            'department' => 'Sales',
        ]);
        $this->assertSame(1, Employee::count());

        $account = User::where('email', 'asha@example.com')->firstOrFail();
        $this->assertSame('employee', $account->role);
        $this->assertNotEmpty($account->password);
        Notification::assertSentTo($account, AccountSetupNotification::class);
    }

    public function test_the_password_setup_link_lets_a_new_user_set_a_password_once(): void
    {
        Notification::fake();
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->post(route('employees.store'), [
            'employee_code' => 'ABJ-102',
            'name' => 'Ravi Kumar',
            'email' => 'ravi@example.com',
            'role' => 'owner',
            'department' => 'Accounts',
            'designation' => 'Accountant',
            'joining_date' => '2026-10-01',
            'employment_status' => 'Active',
        ])->assertRedirect(route('employees.index'));

        $account = User::where('email', 'ravi@example.com')->firstOrFail();
        $setupUrl = null;
        Notification::assertSentTo($account, AccountSetupNotification::class, function (AccountSetupNotification $notification) use (&$setupUrl): bool {
            $setupUrl = $notification->setupUrl;

            return true;
        });

        Auth::logout();
        $this->get($setupUrl)->assertOk()->assertSee('Set up your password');
        $this->post($setupUrl, [
            'email' => $account->email,
            'password' => 'secure-password',
            'password_confirmation' => 'secure-password',
        ])->assertRedirect(route('login'));

        $this->assertTrue(Hash::check('secure-password', $account->fresh()->password));
        $this->get($setupUrl)->assertForbidden();
    }

    public function test_an_administrator_can_change_the_role_of_an_existing_login_account(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $account = User::factory()->create(['email' => 'asha@example.com', 'role' => 'employee']);
        $employee = Employee::create([
            'employee_code' => 'ABJ-103',
            'name' => 'Asha Kumar',
            'email' => $account->email,
            'department' => 'Sales',
            'designation' => 'Sales Executive',
            'joining_date' => '2026-10-01',
            'employment_status' => 'Active',
        ]);

        $this->actingAs($admin)->put(route('employees.update', $employee), [
            'employee_code' => $employee->employee_code,
            'name' => $employee->name,
            'email' => $employee->email,
            'role' => 'owner',
            'department' => $employee->department,
            'designation' => $employee->designation,
            'joining_date' => '2026-10-01',
            'employment_status' => 'Active',
        ])->assertRedirect(route('employees.index'));

        $this->assertSame('owner', $account->fresh()->role);
    }

    public function test_role_must_be_one_of_the_configured_roles(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->post(route('employees.store'), [
                'employee_code' => 'ABJ-104',
                'name' => 'Invalid Role',
                'email' => 'invalid@example.com',
                'role' => 'superadmin',
                'department' => 'Sales',
                'designation' => 'Sales Executive',
                'joining_date' => '2026-10-01',
                'employment_status' => 'Active',
            ])
            ->assertSessionHasErrors('role');

        $this->assertDatabaseMissing('users', ['email' => 'invalid@example.com']);
    }
}
