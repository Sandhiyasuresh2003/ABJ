<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('services', function (Blueprint $table) {
            $table->id();
            $table->string('title', 150);
            $table->text('description');
            $table->string('image_path');
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();
        });

        $description = 'sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip';
        $now = now();

        DB::table('services')->insert([
            [
                'title' => 'FRESH VEGETABLES',
                'description' => $description,
                'image_path' => 'templates/agropro/images/service1.jpg',
                'position' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'title' => 'AGRICULTURE PRODUCTS',
                'description' => $description,
                'image_path' => 'templates/agropro/images/service2.jpg',
                'position' => 2,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'title' => 'ORGANIC PRODUCTS',
                'description' => $description,
                'image_path' => 'templates/agropro/images/service3.jpg',
                'position' => 3,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('services');
    }
};
