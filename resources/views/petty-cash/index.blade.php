@extends('layouts.app')
@section('title','Petty cash')
@section('breadcrumb','Petty cash')
@section('heading','Petty cash management')
@section('content')
@php
    $isEditing = $editingTransaction !== null;
    $defaultItem = $isEditing
        ? ['product_name' => $editingTransaction->product_name ?: $editingTransaction->description, 'price' => $editingTransaction->amount]
        : ['product_name' => '', 'price' => ''];
    $initialItems = old('items', [$defaultItem]);
@endphp

<section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-soft sm:p-7">
    <div class="flex flex-col justify-between gap-2 sm:flex-row sm:items-end">
        <div>
            <h2 class="text-xl font-bold text-navy-950">{{ $isEditing ? 'Edit transaction' : 'Add daily transaction' }}</h2>
            <p class="mt-1 text-sm text-slate-500">Choose a date and credit/debit type, then add item names and prices.</p>
        </div>
        @if($isEditing)<a class="text-sm font-semibold text-gold-600" href="{{ route('petty-cash.index',['date'=>$selectedDate]) }}">Cancel edit</a>@endif
    </div>
    <form method="POST" action="{{ $isEditing ? route('petty-cash.update',$editingTransaction) : route('petty-cash.store') }}" class="mt-5">
        @csrf
        @if($isEditing)@method('PUT')@endif
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div>
                <label for="transaction_date" class="mb-1.5 block text-sm font-semibold">Transaction date *</label>
                <input id="transaction_date" class="field" type="date" name="transaction_date" value="{{ old('transaction_date',$isEditing ? $editingTransaction->transaction_date->format('Y-m-d') : $selectedDate) }}" required>
                @error('transaction_date')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="type" class="mb-1.5 block text-sm font-semibold">Type *</label>
                <select id="type" class="field" name="type" required>
                    <option value="in" @selected(old('type',$isEditing ? $editingTransaction->type : 'out')==='in')>Credit</option>
                    <option value="out" @selected(old('type',$isEditing ? $editingTransaction->type : 'out')==='out')>Debit</option>
                </select>
                @error('type')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
            </div>
        </div>

        <div class="mt-6">
            <div class="mb-3 flex items-center justify-between">
                <div><h3 class="font-bold text-navy-950">Items</h3><p class="text-xs text-slate-500">Each item is saved as a separate transaction line.</p></div>
                @if(!$isEditing)<button id="add-transaction-item" type="button" class="btn btn-light">＋ Add item</button>@endif
            </div>
            <div id="transaction-items" class="space-y-3">
                @foreach($initialItems as $index => $item)
                    <div data-item-row class="grid grid-cols-1 items-end gap-3 rounded-xl border border-slate-200 bg-slate-50 p-4 sm:grid-cols-[1fr_12rem_auto]">
                        <div>
                            <label class="mb-1.5 block text-sm font-semibold">Item name *</label>
                            <input class="field" name="items[{{ $index }}][product_name]" value="{{ old("items.$index.product_name",$item['product_name'] ?? '') }}" maxlength="150" placeholder="e.g. Cement bags" required>
                            @error("items.$index.product_name")<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="mb-1.5 block text-sm font-semibold">Price (₹) *</label>
                            <input class="field" type="number" min="0.01" step="0.01" name="items[{{ $index }}][price]" value="{{ old("items.$index.price",$item['price'] ?? '') }}" placeholder="0.00" required>
                            @error("items.$index.price")<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
                        </div>
                        <button type="button" data-remove-item class="btn btn-danger {{ $isEditing || count($initialItems) < 2 ? 'hidden' : '' }}">Remove</button>
                    </div>
                @endforeach
            </div>
            @error('items')<p class="mt-2 text-xs text-rose-600">{{ $message }}</p>@enderror
            <div class="mt-4 flex justify-end rounded-xl bg-slate-50 px-4 py-3">
                <p class="font-semibold text-navy-950">Total: <output id="items-total">₹0.00</output></p>
            </div>
        </div>
        <div class="mt-5 flex justify-end gap-3 border-t border-slate-100 pt-5">
            @if($isEditing)<a class="btn btn-light" href="{{ route('petty-cash.index',['date'=>$selectedDate]) }}">Cancel</a>@endif
            <button class="btn btn-primary">{{ $isEditing ? 'Save transaction' : 'Save transactions' }}</button>
        </div>
    </form>
</section>

<section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-soft sm:p-7">
    <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
        <div><h2 class="text-xl font-bold text-navy-950">Transactions for {{ \Illuminate\Support\Carbon::parse($selectedDate)->format('d M Y') }}</h2><p class="mt-1 text-sm text-slate-500">Select a date to view its separate credit and debit items.</p></div>
        <form method="GET" action="{{ route('petty-cash.index') }}" class="flex items-end gap-2">
            <div><label for="filter-date" class="mb-1.5 block text-sm font-semibold">Filter by date</label><input id="filter-date" class="field" type="date" name="date" value="{{ $selectedDate }}" required></div>
            <button class="btn btn-primary">View</button>
        </form>
    </div>

    <div class="mt-5">
        <h3 class="text-sm font-bold uppercase tracking-wide text-slate-500">Overall totals · all dates</h3>
        <div class="mt-3 grid grid-cols-1 gap-4 sm:grid-cols-3">
            <div class="rounded-xl border border-emerald-100 bg-emerald-50 p-4"><p class="text-sm text-emerald-800">Overall credit</p><p class="mt-1 text-xl font-bold text-emerald-800">₹{{ number_format($cashIn,2) }}</p></div>
            <div class="rounded-xl border border-rose-100 bg-rose-50 p-4"><p class="text-sm text-rose-800">Overall debit</p><p class="mt-1 text-xl font-bold text-rose-800">₹{{ number_format($cashOut,2) }}</p></div>
            <div class="rounded-xl border border-slate-200 bg-slate-50 p-4"><p class="text-sm text-slate-600">Current balance</p><p class="mt-1 text-xl font-bold {{ $balance < 0 ? 'text-rose-700' : 'text-navy-950' }}">₹{{ number_format($balance,2) }}</p><p class="mt-1 text-xs text-slate-500">Overall credit minus overall debit</p></div>
        </div>
    </div>

    <div class="mt-5 overflow-x-auto rounded-xl border border-slate-200">
        <table class="w-full min-w-[700px] text-left">
            <thead class="bg-slate-50 text-[11px] uppercase tracking-wider text-slate-500"><tr><th class="px-4 py-3">Type</th><th class="px-4 py-3">Item</th><th class="px-4 py-3">Credit</th><th class="px-4 py-3">Debit</th><th class="px-4 py-3">Actions</th></tr></thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($transactions as $transaction)
                    <tr>
                        <td class="px-4 py-3 text-sm font-semibold {{ $transaction->type==='in'?'text-emerald-700':'text-rose-700' }}">{{ $transaction->type==='in'?'Credit':'Debit' }}</td>
                        <td class="px-4 py-3 text-sm font-semibold">{{ $transaction->product_name ?: $transaction->description }}</td>
                        <td class="px-4 py-3 text-sm font-bold text-emerald-700">{{ $transaction->type==='in'?'₹'.number_format($transaction->amount,2):'—' }}</td>
                        <td class="px-4 py-3 text-sm font-bold text-rose-700">{{ $transaction->type==='out'?'₹'.number_format($transaction->amount,2):'—' }}</td>
                        <td class="px-4 py-3"><div class="flex gap-2">
                            <a class="btn btn-light !px-3 !py-2 !text-xs" href="{{ route('petty-cash.edit',$transaction) }}">Edit</a>
                            <form method="POST" action="{{ route('petty-cash.destroy',$transaction) }}" onsubmit="return confirm('Delete this transaction?')">@csrf @method('DELETE')<button class="btn btn-danger !px-3 !py-2 !text-xs">Delete</button></form>
                        </div></td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-5 py-10 text-center text-sm text-slate-400">No transactions recorded for this date.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</section>

<template id="transaction-item-template">
    <div data-item-row class="grid grid-cols-1 items-end gap-3 rounded-xl border border-slate-200 bg-slate-50 p-4 sm:grid-cols-[1fr_12rem_auto]">
        <div><label class="mb-1.5 block text-sm font-semibold">Item name *</label><input class="field" name="items[__INDEX__][product_name]" maxlength="150" placeholder="e.g. Cement bags" required></div>
        <div><label class="mb-1.5 block text-sm font-semibold">Price (₹) *</label><input class="field" type="number" min="0.01" step="0.01" name="items[__INDEX__][price]" placeholder="0.00" required></div>
        <button type="button" data-remove-item class="btn btn-danger">Remove</button>
    </div>
</template>
<script>
    (() => {
        const container = document.getElementById('transaction-items');
        const template = document.getElementById('transaction-item-template');
        const addButton = document.getElementById('add-transaction-item');
        const totalOutput = document.getElementById('items-total');
        if (!container || !template || !totalOutput) return;

        const updateTotal = () => {
            const total = [...container.querySelectorAll('[name$="[price]"]')]
                .reduce((sum, input) => sum + (Number(input.value) || 0), 0);
            totalOutput.textContent = `₹${total.toFixed(2)}`;
            const rows = [...container.querySelectorAll('[data-item-row]')];
            rows.forEach((row) => row.querySelector('[data-remove-item]')?.classList.toggle('hidden', rows.length < 2));
        };

        addButton?.addEventListener('click', () => {
            const index = container.querySelectorAll('[data-item-row]').length;
            const row = template.content.cloneNode(true);
            row.querySelectorAll('[name]').forEach((input) => {
                input.name = input.name.replaceAll('__INDEX__', index);
            });
            container.appendChild(row);
            updateTotal();
        });
        container.addEventListener('input', updateTotal);
        container.addEventListener('click', (event) => {
            const remove = event.target.closest('[data-remove-item]');
            if (remove) {
                remove.closest('[data-item-row]').remove();
                updateTotal();
            }
        });
        updateTotal();
    })();
</script>
@endsection
