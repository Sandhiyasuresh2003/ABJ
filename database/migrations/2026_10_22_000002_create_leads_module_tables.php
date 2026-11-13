<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leads', function (Blueprint $table) {
            $table->id();
            $table->string('lead_name', 150);
            $table->string('mobile_number', 25);
            $table->string('normalized_mobile', 20)->unique();
            $table->string('location', 150)->index();
            $table->string('requirement', 1000);
            $table->foreignId('assigned_to')->nullable()->constrained('employees')->restrictOnDelete();
            $table->string('status', 20)->default('Active')->index();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->longText('source_data')->nullable();
            $table->timestamps();
            $table->index(['assigned_to', 'status', 'created_at']);
            $table->index('created_at');
            $table->index('lead_name');
        });
        Schema::create('lead_followups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lead_id')->constrained()->restrictOnDelete();
            $table->string('outcome', 40)->index();
            $table->text('remarks')->nullable();
            $table->dateTime('followup_at');
            $table->dateTime('next_followup_at')->nullable()->index();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();
            $table->index(['lead_id', 'followup_at', 'id']);
        });
        Schema::create('lead_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lead_id')->constrained()->restrictOnDelete();
            $table->foreignId('previous_employee_id')->nullable()->constrained('employees')->restrictOnDelete();
            $table->foreignId('new_employee_id')->nullable()->constrained('employees')->restrictOnDelete();
            $table->foreignId('assigned_by')->constrained('users')->restrictOnDelete();
            $table->string('reason', 1000)->nullable();
            $table->timestamps();
        });
        Schema::create('lead_imports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('uploaded_by')->constrained('users')->restrictOnDelete();
            $table->string('original_filename');
            $table->string('path')->nullable();
            $table->string('status', 20)->default('uploaded')->index();
            $table->unsignedInteger('total_rows')->default(0);
            $table->unsignedInteger('imported_rows')->default(0);
            $table->unsignedInteger('duplicate_rows')->default(0);
            $table->unsignedInteger('failed_rows')->default(0);
            $table->longText('payload')->nullable();
            $table->timestamp('expires_at')->index();
            $table->timestamps();
        });
        Schema::create('lead_audits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lead_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->string('event', 50)->index();
            $table->json('metadata')->nullable();
            $table->timestamp('created_at')->useCurrent()->index();
        });
    }

    public function down(): void
    {
        foreach (['lead_audits', 'lead_imports', 'lead_assignments', 'lead_followups', 'leads'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
