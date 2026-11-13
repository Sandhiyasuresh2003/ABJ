<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('petty_cash_transactions', function (Blueprint $table) {
            $table->string('product_name', 150)->nullable()->after('category');
            $table->decimal('quantity', 12, 3)->default(1)->after('product_name');
            $table->decimal('rate', 12, 2)->nullable()->after('quantity');
        });
    }

    public function down(): void
    {
        Schema::table('petty_cash_transactions', function (Blueprint $table) {
            $table->dropColumn(['product_name', 'quantity', 'rate']);
        });
    }
};
