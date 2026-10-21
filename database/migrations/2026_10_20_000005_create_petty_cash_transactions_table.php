<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('petty_cash_transactions', function (Blueprint $table) {
            $table->id(); $table->date('transaction_date'); $table->string('type', 10);
            $table->string('category', 60); $table->string('description', 255);
            $table->decimal('amount', 12, 2); $table->string('receipt_reference', 100)->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('petty_cash_transactions'); }
};
