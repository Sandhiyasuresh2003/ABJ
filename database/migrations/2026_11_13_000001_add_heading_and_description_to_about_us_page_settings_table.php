<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('about_us_page_settings', function (Blueprint $table) {
            $table->string('heading', 150)->nullable()->after('label');
            $table->text('description')->nullable()->after('heading');
        });

        DB::table('about_us_page_settings')->where('id', 1)->update([
            'heading' => 'AGRICULTURE MARKET',
            'description' => 'fact that a reader will be distracted by the readable content of a page when looking at its layout. The point of using Lorem Ipsum is that itfact that a reader will be distracted by the readable content of a page when looking at its layout. The point of using Lorem Ipsum is that itfact th',
        ]);
    }

    public function down(): void
    {
        Schema::table('about_us_page_settings', function (Blueprint $table) {
            $table->dropColumn(['heading', 'description']);
        });
    }
};
