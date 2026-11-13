<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('about_us_page_settings', function (Blueprint $table) {
            $table->id();
            $table->string('label', 100)->default('About Us');
            $table->string('landing_image_one')->nullable();
            $table->string('landing_image_two')->nullable();
            $table->timestamps();
        });

        DB::table('about_us_page_settings')->insert([
            'id' => 1,
            'label' => 'About Us',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('about_us_page_settings');
    }
};
