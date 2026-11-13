<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('client_testimonials', function (Blueprint $table) {
            $table->id();
            $table->string('name', 150);
            $table->string('role', 100)->default('Client');
            $table->text('feedback');
            $table->string('image_path');
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();
        });

        $now = now();
        $feedback = "readable English. Many desktop publishing packages and web page editors now use Lorem Ipsum as their default model text, and a search for 'lorem ipsum' will uncover many web sites still in their";

        DB::table('client_testimonials')->insert([
            [
                'name' => 'Dan Balan',
                'role' => 'Client',
                'feedback' => $feedback,
                'image_path' => 'templates/agropro/images/customer1.jpg',
                'position' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'Mor Balan',
                'role' => 'Client',
                'feedback' => $feedback,
                'image_path' => 'templates/agropro/images/customer2.jpg',
                'position' => 2,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('client_testimonials');
    }
};
