<?php

namespace Tests\Feature;

use App\Models\BannerImage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class BannerImageManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_upload_multiple_banners_and_the_homepage_displays_them(): void
    {
        Storage::fake('public');
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->post(route('admin.banners.store'), [
            'images' => [
                UploadedFile::fake()->image('banner-one.jpg'),
                UploadedFile::fake()->image('banner-two.jpg'),
            ],
        ]);

        $response->assertRedirect(route('admin.banners.index'));
        $banners = BannerImage::query()->orderBy('position')->get();
        $this->assertCount(2, $banners);
        foreach ($banners as $banner) {
            Storage::disk('public')->assertExists($banner->image_path);
        }

        Auth::logout();
        $homepage = $this->get(route('home'))->assertOk();
        foreach ($banners as $banner) {
            $homepage->assertSee(asset('storage/'.$banner->image_path), false);
        }
    }

    public function test_admin_can_remove_a_banner_image(): void
    {
        Storage::fake('public');
        $admin = User::factory()->create(['role' => 'admin']);
        $path = UploadedFile::fake()->image('banner.jpg')->storePublicly('banners', 'public');
        $banner = BannerImage::query()->create(['image_path' => $path, 'position' => 1]);

        $response = $this->actingAs($admin)->delete(route('admin.banners.destroy', $banner));

        $response->assertRedirect(route('admin.banners.index'));
        $this->assertDatabaseMissing('banner_images', ['id' => $banner->id]);
        Storage::disk('public')->assertMissing($path);
    }

}
