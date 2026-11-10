<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('salary_configurations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->restrictOnDelete();
            $table->date('effective_month');
            $table->decimal('basic_salary', 12, 2);
            $table->decimal('sunday_incentive', 12, 2)->default(0);
            $table->string('divisor_method', 20)->default('calendar');
            $table->unsignedSmallInteger('fixed_divisor')->nullable();
            $table->unsignedSmallInteger('working_minutes')->default(480);
            $table->unsignedSmallInteger('permission_minutes')->default(120);
            $table->unsignedSmallInteger('late_charge_minutes')->default(30);
            $table->time('reporting_time')->default('10:00:00');
            $table->unsignedSmallInteger('cl_allowance')->default(0);
            $table->string('cl_period', 10)->default('monthly');
            $table->string('status', 10)->default('Active');
            $table->timestamps();
            $table->unique(['employee_id', 'effective_month']);
        });
        Schema::create('salary_statements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->restrictOnDelete();
            $table->date('salary_month');
            $table->json('snapshot');
            $table->decimal('other_deductions', 12, 2)->default(0);
            $table->string('adjustment_reason', 1000)->nullable();
            $table->string('status', 10)->default('Draft');
            $table->unsignedInteger('revision')->default(1);
            $table->foreignId('finalized_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('finalized_at')->nullable();
            $table->string('payment_status', 10)->default('Pending');
            $table->date('payment_date')->nullable();
            $table->string('payment_reference', 255)->nullable();
            $table->timestamps();
            $table->unique(['employee_id', 'salary_month']);
        });
        Schema::create('salary_audits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->restrictOnDelete();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action', 50);
            $table->text('reason')->nullable();
            $table->json('before')->nullable();
            $table->json('after');
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('salary_audits');
        Schema::dropIfExists('salary_statements');
        Schema::dropIfExists('salary_configurations');
    }
};
