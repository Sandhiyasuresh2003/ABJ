<?php

namespace App\Http\Controllers;

use App\Models\PettyCashTransaction;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PettyCashController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'date' => ['nullable', 'date'],
            'edit' => ['nullable', 'integer', 'exists:petty_cash_transactions,id'],
        ]);
        $selectedDate = $filters['date'] ?? now()->toDateString();
        $transactions = PettyCashTransaction::query()
            ->with('creator')
            ->whereDate('transaction_date', $selectedDate)
            ->orderBy('id')
            ->get();
        $editingTransaction = isset($filters['edit'])
            ? PettyCashTransaction::findOrFail($filters['edit'])
            : null;
        $cashIn = (float) PettyCashTransaction::query()->where('type', 'in')->sum('amount');
        $cashOut = (float) PettyCashTransaction::query()->where('type', 'out')->sum('amount');
        $balance = $cashIn - $cashOut;

        return view('petty-cash.index', compact(
            'transactions',
            'editingTransaction',
            'selectedDate',
            'cashIn',
            'cashOut',
            'balance',
        ));
    }

    public function create(): RedirectResponse
    {
        return redirect()->route('petty-cash.index');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request, true);

        DB::transaction(function () use ($data, $request): void {
            foreach ($data['items'] as $item) {
                PettyCashTransaction::create($this->transactionData(
                    $item,
                    $data['transaction_date'],
                    $data['type'],
                    $request->user()->id,
                ));
            }
        });

        return redirect()->route('petty-cash.index', ['date' => $data['transaction_date']])
            ->with('success', 'Daily transactions recorded.');
    }

    public function edit(PettyCashTransaction $pettyCash): RedirectResponse
    {
        return redirect()->route('petty-cash.index', [
            'date' => $pettyCash->transaction_date->toDateString(),
            'edit' => $pettyCash->id,
        ]);
    }

    public function update(Request $request, PettyCashTransaction $pettyCash): RedirectResponse
    {
        $data = $this->validated($request, false);
        $pettyCash->update($this->transactionData(
            $data['items'][0],
            $data['transaction_date'],
            $data['type'],
            $pettyCash->created_by,
        ));

        return redirect()->route('petty-cash.index', ['date' => $data['transaction_date']])
            ->with('success', 'Transaction updated.');
    }

    public function destroy(PettyCashTransaction $pettyCash): RedirectResponse
    {
        $date = $pettyCash->transaction_date->toDateString();
        $pettyCash->delete();

        return redirect()->route('petty-cash.index', ['date' => $date])
            ->with('success', 'Transaction deleted.');
    }

    private function validated(Request $request, bool $multiple): array
    {
        $rules = [
            'transaction_date' => ['required', 'date'],
            'type' => ['required', Rule::in(['in', 'out'])],
            'items' => ['required', 'array', $multiple ? 'min:1' : 'size:1'],
            'items.*.product_name' => ['required', 'string', 'max:150'],
            'items.*.price' => ['required', 'numeric', 'gt:0', 'max:9999999999.99'],
        ];

        return $request->validate($rules);
    }

    private function transactionData(array $item, string $date, string $type, ?int $creatorId): array
    {
        $price = round((float) $item['price'], 2);

        return [
            'transaction_date' => $date,
            'type' => $type,
            'category' => 'Other',
            'product_name' => $item['product_name'],
            'quantity' => 1,
            'rate' => $price,
            'description' => $item['product_name'],
            'amount' => $price,
            'receipt_reference' => null,
            'created_by' => $creatorId,
        ];
    }
}
