<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('employees', function (Blueprint $table) {
            $table->id(); $table->string('employee_code', 30)->unique(); $table->string('name', 150);
            $table->string('email', 150)->nullable()->unique(); $table->string('phone', 25)->nullable();
            $table->string('department', 100); $table->string('designation', 100); $table->date('joining_date');
            $table->string('employment_status', 20)->default('Active'); $table->text('address')->nullable();
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('employees'); }
};
