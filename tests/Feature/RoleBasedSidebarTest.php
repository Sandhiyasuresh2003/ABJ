<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\ViewErrorBag;
use Tests\TestCase;

class RoleBasedSidebarTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_admin_sidebar_shows_only_the_requested_menus_without_duplicate_entries(): void
    {
        $response = $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->get(route('dashboard'))
            ->assertOk();

        $adminLabels = array_column(config('sidebar.roles.admin'), 'label');
        $this->assertSame(['Documents', 'Leads', 'Attendance', 'Employees', 'Leave Request', 'Permissions', 'Petty Cash', 'Properties', 'Recovery codes'], $adminLabels);

        foreach ($adminLabels as $label) {
            $response->assertSee($label);
        }

        foreach (['Bookings', 'Reports', 'Settings'] as $label) {
            $response->assertDontSee('>'.$label.'</a>', false);
        }

        foreach (config('sidebar.roles.admin') as $menu) {
            $this->assertSame(1, substr_count($response->getContent(), '>'.$menu['icon'].' '.$menu['label'].'</a>'));
        }
        $this->assertSame(1, substr_count($response->getContent(), '>♧ Recovery codes</a>'));
        $this->assertStringContainsString(
            '<div class="px-3 pt-5"><p class="px-3 pb-2 text-[10px] font-bold uppercase tracking-[.2em] text-slate-500">Workspace</p>',
            $response->getContent()
        );
    }

    public function test_employee_sidebar_shows_only_their_allowed_modules(): void
    {
        $employee = User::factory()->create(['role' => 'employee']);
        $this->actingAs($employee);
        $content = view('layouts.app', ['errors' => new ViewErrorBag()])->render();
        $employeeLabels = array_column(config('sidebar.roles.employee'), 'label');
        $this->assertSame(['Attendance', 'Bookings', 'Dashboard', 'Leads', 'Leave Request', 'Permissions', 'Reports'], $employeeLabels);

        foreach ($employeeLabels as $label) {
            $this->assertStringContainsString(' '.$label.'</a>', $content);
        }

        foreach (['Properties', 'Settings', 'Petty Cash', 'Employees', 'Recovery codes'] as $label) {
            $this->assertStringNotContainsString('>'.$label.'</a>', $content);
        }

        $owner = User::factory()->create(['role' => 'owner']);
        $ownerLabels = array_column(config('sidebar.roles.owner'), 'label');
        $this->assertSame([
            'Documents',
            'Attendance',
            'Bookings',
            'Dashboard',
            'Employees',
            'Leads',
            'Leave Request',
            'Permissions',
            'Petty Cash',
            'Properties',
            'Recovery codes',
            'Reports',
            'Settings',
        ], $ownerLabels);

        $this->actingAs($owner);
        $ownerContent = view('layouts.app', ['errors' => new ViewErrorBag()])->render();
        foreach ($ownerLabels as $label) {
            $this->assertStringContainsString(' '.$label.'</a>', $ownerContent);
        }
    }

    public function test_admin_and_employee_placeholder_module_pages_respect_role_access(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->get(route('admin.pending', ['module' => 'leads']))
            ->assertRedirect(route('leads.dashboard'));

        $this->actingAs(User::factory()->create(['role' => 'employee']))
            ->get(route('admin.pending', ['module' => 'leads']))
            ->assertForbidden();

        $this->actingAs(User::factory()->create(['role' => 'employee']))
            ->get(route('employee.module', ['module' => 'reports']))
            ->assertOk()
            ->assertSee('Reports module')
            ->assertSee('Coming soon');

        $this->get('/employee/modules/properties')->assertNotFound();

        $this->actingAs($admin)->get('/admin/pending/not-a-module')->assertNotFound();
    }

    public function test_owner_can_access_the_admin_workspace_and_all_configured_admin_pages(): void
    {
        $owner = User::factory()->create(['role' => 'owner']);

        $this->actingAs($owner)
            ->get(route('dashboard'))
            ->assertOk();
        $this->get(route('admin.pending', ['module' => 'properties']))->assertOk();
        $this->get(route('petty-cash.index'))->assertOk();
        $this->get(route('employees.index'))->assertOk();
        $this->get(route('attendance.index'))->assertOk();
        $this->get(route('leaves.index'))->assertOk();
        $this->get(route('permissions.index'))->assertOk();
        $this->get(route('admin.password-recovery-codes.index'))->assertOk();
        $this->get(route('admin.pending', ['module' => 'leads']))->assertRedirect(route('leads.dashboard'));
        foreach (['bookings', 'properties', 'reports', 'settings'] as $module) {
            $this->get(route('admin.pending', ['module' => $module]))->assertOk();
        }
    }
}
