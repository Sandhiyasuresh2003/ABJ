<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('latest_news', function (Blueprint $table) {
            $table->string('project_name', 150)->nullable();
            $table->string('project_location', 255)->nullable();
            $table->text('project_about')->nullable();
        });

        foreach (DB::table('latest_news')->get(['id', 'title', 'description']) as $newsItem) {
            DB::table('latest_news')->where('id', $newsItem->id)->update([
                'project_name' => $newsItem->title,
                'project_location' => 'Not specified',
                'project_about' => $newsItem->description,
            ]);
        }

        Schema::create('latest_news_gallery_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('latest_news_id')->constrained('latest_news')->cascadeOnDelete();
            $table->string('image_path');
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('latest_news_gallery_images');

        Schema::table('latest_news', function (Blueprint $table) {
            $table->dropColumn(['project_name', 'project_location', 'project_about']);
        });
    }
};
