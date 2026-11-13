<?php

namespace Tests\Feature;

use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ServiceManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_add_and_update_services_shown_on_the_homepage(): void
    {
        Storage::fake('public');
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin)->post(route('admin.services.store'), [
            'title' => 'Garden Planning',
            'description' => 'Planning and support for home gardens.',
            'image' => UploadedFile::fake()->image('garden.jpg'),
        ])->assertRedirect(route('admin.services.index'));

        $service = Service::query()->where('title', 'Garden Planning')->firstOrFail();
        Storage::disk('public')->assertExists($service->image_path);

        $this->put(route('admin.services.update', $service), [
            'title' => 'Garden Design',
            'description' => 'Custom garden design and support.',
        ])->assertRedirect(route('admin.services.index'));

        $service->refresh();
        $this->assertSame('Garden Design', $service->title);
        $this->assertSame('Custom garden design and support.', $service->description);

        Auth::logout();
        $this->get(route('home'))
            ->assertOk()
            ->assertSee('Garden Design')
            ->assertSee('Custom garden design and support.');
    }
}
