<?php

namespace Tests\Feature;

use App\Models\ClientTestimonial;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ClientTestimonialManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_add_and_update_feedback_displayed_on_the_homepage(): void
    {
        Storage::fake('public');
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->post(route('admin.client-feedback.store'), [
            'name' => 'Ravi Kumar',
            'role' => 'Customer',
            'feedback' => 'Excellent service from the team.',
            'image' => UploadedFile::fake()->image('ravi.jpg'),
        ])->assertRedirect(route('admin.client-feedback.index'));

        $testimonial = ClientTestimonial::query()->where('name', 'Ravi Kumar')->firstOrFail();
        Storage::disk('public')->assertExists($testimonial->image_path);
        $this->get(route('admin.client-feedback.index'))
            ->assertOk()
            ->assertSee('Ravi Kumar')
            ->assertSee('Excellent service from the team.');

        $this->put(route('admin.client-feedback.update', $testimonial), [
            'name' => 'Ravi K.',
            'role' => 'Homeowner',
            'feedback' => 'Very happy with the completed work.',
        ])->assertRedirect(route('admin.client-feedback.index'));

        $testimonial->refresh();
        $this->assertSame('Ravi K.', $testimonial->name);
        $this->assertSame('Homeowner', $testimonial->role);

        Auth::logout();
        $this->get(route('home'))
            ->assertOk()
            ->assertDontSee('Whay choose us')
            ->assertSee('Ravi K.')
            ->assertSee('Very happy with the completed work.')
            ->assertSee(asset('storage/'.$testimonial->image_path), false);
    }

    public function test_admin_can_remove_client_feedback(): void
    {
        Storage::fake('public');
        $admin = User::factory()->create(['role' => 'admin']);
        $path = UploadedFile::fake()->image('client.jpg')->storePublicly('client-feedback', 'public');
        $testimonial = ClientTestimonial::query()->create([
            'name' => 'Client Name',
            'role' => 'Client',
            'feedback' => 'Feedback text.',
            'image_path' => $path,
            'position' => 3,
        ]);

        $this->actingAs($admin)
            ->delete(route('admin.client-feedback.destroy', $testimonial))
            ->assertRedirect(route('admin.client-feedback.index'));

        $this->assertDatabaseMissing('client_testimonials', ['id' => $testimonial->id]);
        Storage::disk('public')->assertMissing($path);
    }
}
