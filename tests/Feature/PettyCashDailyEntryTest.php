<?php

namespace Tests\Feature;

use App\Models\PettyCashTransaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class PettyCashDailyEntryTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_record_multiple_items_with_one_transaction_type_for_a_day(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->post(route('petty-cash.store'), [
            'transaction_date' => '2026-10-22',
            'type' => 'out',
            'items' => [
                ['product_name' => 'Cement bags', 'price' => 4500],
                ['product_name' => 'Fuel', 'price' => 1250],
            ],
        ])->assertRedirect(route('petty-cash.index', ['date' => '2026-10-22']))
            ->assertSessionHas('success');

        $this->assertDatabaseCount('petty_cash_transactions', 2);
        $this->assertDatabaseHas('petty_cash_transactions', [
            'type' => 'out',
            'product_name' => 'Cement bags',
            'amount' => 4500,
            'quantity' => 1,
            'rate' => 4500,
            'created_by' => $admin->id,
        ]);
        $this->assertSame('2026-10-22', PettyCashTransaction::firstOrFail()->transaction_date->toDateString());

        $this->get(route('petty-cash.index', ['date' => '2026-10-22']))
            ->assertOk()
            ->assertSee('Transactions for 22 Oct 2026')
            ->assertSee('Overall credit')
            ->assertSee('Overall debit')
            ->assertSee('₹5,750.00')
            ->assertSee('Cement bags')
            ->assertSee('Fuel');
    }

    public function test_items_require_a_valid_transaction_type_and_positive_price(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->post(route('petty-cash.store'), [
                'transaction_date' => '2026-10-22',
                'type' => 'unknown',
                'items' => [['product_name' => 'Cement', 'price' => 0]],
            ])
            ->assertSessionHasErrors(['type', 'items.0.price']);

        $this->assertDatabaseCount('petty_cash_transactions', 0);
    }

    public function test_page_defaults_to_today_and_date_filter_updates_rows_and_totals(): void
    {
        Carbon::setTestNow('2026-10-22 12:00:00');

        try {
            $admin = User::factory()->create(['role' => 'admin']);
            PettyCashTransaction::create([
                'transaction_date' => '2026-10-21',
                'type' => 'in',
                'category' => 'Other',
                'description' => 'Yesterday credit',
                'amount' => 900,
                'created_by' => $admin->id,
            ]);
            PettyCashTransaction::create([
                'transaction_date' => '2026-10-22',
                'type' => 'in',
                'category' => 'Other',
                'description' => 'Today credit',
                'amount' => 1200,
                'created_by' => $admin->id,
            ]);
            PettyCashTransaction::create([
                'transaction_date' => '2026-10-22',
                'type' => 'out',
                'category' => 'Other',
                'description' => 'Today debit',
                'amount' => 300,
                'created_by' => $admin->id,
            ]);

            $this->actingAs($admin)->get(route('petty-cash.index'))
                ->assertOk()
                ->assertSee('value="2026-10-22"', false)
                ->assertSee('Today credit')
                ->assertSee('Today debit')
                ->assertDontSee('Yesterday credit')
                ->assertSee('₹2,100.00')
                ->assertSee('₹300.00')
                ->assertSee('₹1,800.00')
                ->assertSee('Overall totals · all dates')
                ->assertSee('Current balance');

            $this->get(route('petty-cash.index', ['date' => '2026-10-21']))
                ->assertOk()
                ->assertSee('Yesterday credit')
                ->assertDontSee('Today credit')
                ->assertSee('₹2,100.00')
                ->assertSee('₹300.00')
                ->assertSee('₹1,800.00');
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_legacy_transaction_rows_still_render_with_item_name_fallback(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        PettyCashTransaction::create([
            'transaction_date' => '2026-10-21',
            'type' => 'out',
            'category' => 'Office Supplies',
            'description' => 'Old paper purchase',
            'amount' => 125,
            'created_by' => $admin->id,
        ]);

        $this->actingAs($admin)->get(route('petty-cash.index', ['date' => '2026-10-21']))
            ->assertOk()
            ->assertSee('Old paper purchase')
            ->assertSee('₹125.00');
    }
}
