<?php

namespace App\Http\Controllers;

use App\Models\PettyCashTransaction;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class PettyCashController extends Controller
{
    public function index(): View
    {
        $transactions = PettyCashTransaction::with('creator')->latest('transaction_date')->latest('id')->paginate(20);
        $cashIn = (float) PettyCashTransaction::where('type', 'in')->sum('amount');
        $cashOut = (float) PettyCashTransaction::where('type', 'out')->sum('amount');
        $balance = $cashIn - $cashOut;
        return view('petty-cash.index', compact('transactions', 'cashIn', 'cashOut', 'balance'));
    }

    public function create(): View { return view('petty-cash.form', ['transaction' => new PettyCashTransaction(), 'editing' => false]); }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $data['created_by'] = $request->user()->id;
        PettyCashTransaction::create($data);
        return redirect()->route('petty-cash.index')->with('success', 'Petty cash transaction recorded.');
    }

    public function edit(PettyCashTransaction $pettyCash): View { return view('petty-cash.form', ['transaction' => $pettyCash, 'editing' => true]); }

    public function update(Request $request, PettyCashTransaction $pettyCash): RedirectResponse
    {
        $data = $this->validated($request);
        $pettyCash->update($data);
        return redirect()->route('petty-cash.index')->with('success', 'Petty cash transaction updated.');
    }

    public function destroy(PettyCashTransaction $pettyCash): RedirectResponse
    {
        $pettyCash->delete();
        return back()->with('success', 'Petty cash transaction deleted.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'transaction_date' => ['required', 'date'],
            'type' => ['required', Rule::in(['in', 'out'])],
            'category' => ['required', Rule::in(['Opening Balance', 'Replenishment', 'Travel', 'Office Supplies', 'Site Expense', 'Refreshments', 'Repairs', 'Other'])],
            'description' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'gt:0', 'max:99999999'],
            'receipt_reference' => ['nullable', 'string', 'max:100'],
        ]);
    }
}
