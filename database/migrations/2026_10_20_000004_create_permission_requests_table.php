<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('permission_requests', function (Blueprint $table) {
            $table->id(); $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->date('permission_date'); $table->time('start_time'); $table->time('end_time');
            $table->text('reason'); $table->string('status', 20)->default('Pending');
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable(); $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('permission_requests'); }
};
