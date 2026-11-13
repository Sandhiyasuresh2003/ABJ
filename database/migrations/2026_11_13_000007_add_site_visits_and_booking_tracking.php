<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->date('booked_at')->nullable()->index();
            $table->foreignId('booked_by')->nullable()->constrained('users')->restrictOnDelete();
        });

        Schema::create('site_visits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lead_id')->constrained()->restrictOnDelete();
            $table->date('visit_date')->index();
            $table->text('details')->nullable();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();
            $table->index(['lead_id', 'visit_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('site_visits');

        Schema::table('leads', function (Blueprint $table) {
            $table->dropConstrainedForeignId('booked_by');
            $table->dropColumn('booked_at');
        });
    }
};
