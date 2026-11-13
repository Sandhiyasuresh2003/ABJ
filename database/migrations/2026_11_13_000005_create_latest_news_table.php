<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('latest_news', function (Blueprint $table) {
            $table->id();
            $table->string('title', 150);
            $table->text('description');
            $table->string('image_path');
            $table->date('published_at');
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();
        });

        $now = now();
        $description = "has a more-or-less normal distribution of letters, as opposed to using 'Content here, content here', making iteditors";

        DB::table('latest_news')->insert([
            [
                'title' => 'Alteration in somer',
                'description' => $description,
                'image_path' => 'templates/agropro/images/news1.jpg',
                'published_at' => '2026-03-15',
                'position' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'title' => 'Alteration in somer',
                'description' => $description,
                'image_path' => 'templates/agropro/images/news2.jpg',
                'published_at' => '2026-03-15',
                'position' => 2,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'title' => 'Alteration in somer',
                'description' => $description,
                'image_path' => 'templates/agropro/images/news3.jpg',
                'published_at' => '2026-03-15',
                'position' => 3,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('latest_news');
    }
};
