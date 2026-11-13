<?php

namespace Tests\Feature;

use App\Models\LatestNews;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class LatestNewsManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_add_and_update_news_displayed_on_the_homepage(): void
    {
        Storage::fake('public');
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->post(route('admin.latest-news.store'), [
            'title' => 'New Harvest Update',
            'description' => 'Harvest season has started.',
            'published_at' => '2027-01-05',
            'project_name' => 'North Field Upgrade',
            'project_location' => 'Coimbatore',
            'project_about' => 'A detailed account of the north field upgrade.',
            'image' => UploadedFile::fake()->image('harvest.jpg'),
            'gallery_images' => [
                UploadedFile::fake()->image('field-before.jpg'),
                UploadedFile::fake()->image('field-after.jpg'),
            ],
        ])->assertRedirect(route('admin.latest-news.index'));

        $newsItem = LatestNews::query()->where('title', 'New Harvest Update')->firstOrFail();
        Storage::disk('public')->assertExists($newsItem->image_path);
        $this->assertCount(2, $newsItem->galleryImages);
        $this->get(route('admin.latest-news.index'))
            ->assertOk()
            ->assertSee('New Harvest Update')
            ->assertSee('Harvest season has started.')
            ->assertSee('North Field Upgrade')
            ->assertSee('Current project gallery');

        $this->put(route('admin.latest-news.update', $newsItem), [
            'title' => 'Harvest Report',
            'description' => 'The harvest is complete.',
            'published_at' => '2027-02-10',
            'project_name' => 'North Field Harvest',
            'project_location' => 'Tiruppur',
            'project_about' => 'The north field harvest has been completed.',
            'gallery_images' => [UploadedFile::fake()->image('harvest-result.jpg')],
        ])->assertRedirect(route('admin.latest-news.index'));

        $newsItem->refresh();
        $this->assertSame('Harvest Report', $newsItem->title);
        $this->assertSame('2027-02-10', $newsItem->published_at->toDateString());
        $this->assertSame('North Field Harvest', $newsItem->project_name);
        $this->assertCount(3, $newsItem->galleryImages);

        Auth::logout();
        $this->get(route('home'))
            ->assertOk()
            ->assertSee('class="owl-carousel latest-news-carousel"', false)
            ->assertSee('Harvest Report')
            ->assertSee(route('latest-news.show', $newsItem), false);

        $galleryImage = $newsItem->galleryImages->first();
        $this->get(route('latest-news.show', $newsItem))
            ->assertOk()
            ->assertSee('North Field Harvest')
            ->assertSee('Tiruppur')
            ->assertSee('The north field harvest has been completed.')
            ->assertSee(asset('storage/'.$galleryImage->image_path), false);
    }

    public function test_admin_can_remove_a_news_item(): void
    {
        Storage::fake('public');
        $admin = User::factory()->create(['role' => 'admin']);
        $path = UploadedFile::fake()->image('news.jpg')->storePublicly('latest-news', 'public');
        $galleryPath = UploadedFile::fake()->image('news-gallery.jpg')->storePublicly('latest-news/gallery', 'public');
        $newsItem = LatestNews::query()->create([
            'title' => 'Test news',
            'description' => 'Test description.',
            'image_path' => $path,
            'published_at' => '2026-11-13',
            'project_name' => 'Test project',
            'project_location' => 'Test location',
            'project_about' => 'Test project details.',
            'position' => 4,
        ]);
        $newsItem->galleryImages()->create(['image_path' => $galleryPath, 'position' => 1]);

        $this->actingAs($admin)
            ->delete(route('admin.latest-news.destroy', $newsItem))
            ->assertRedirect(route('admin.latest-news.index'));

        $this->assertDatabaseMissing('latest_news', ['id' => $newsItem->id]);
        Storage::disk('public')->assertMissing($path);
        Storage::disk('public')->assertMissing($galleryPath);
    }
}
