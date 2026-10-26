<?php

namespace Tests\Feature;

use App\Models\PropertyDocument;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PropertyDocumentTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_and_owner_can_upload_both_document_types_and_download_each_others_files(): void
    {
        Storage::fake('local');
        $admin = User::factory()->create(['role' => 'admin']);
        $owner = User::factory()->create(['role' => 'owner']);

        foreach (['ec', 'patta_chitta'] as $type) {
            $this->actingAs($admin)->post(route('documents.store'), [
                'title' => 'Plot 12 '.$type,
                'type' => $type,
                'document' => UploadedFile::fake()->create($type.'.pdf', 20, 'application/pdf'),
            ])->assertRedirect(route('documents.index'))->assertSessionHas('success');

            $document = PropertyDocument::latest('id')->firstOrFail();
            $this->assertSame($admin->id, $document->uploaded_by);
            Storage::disk('local')->assertExists($document->path);
            $this->actingAs($owner)->get(route('documents.download', $document))
                ->assertOk()->assertDownload($type.'.pdf');
            [$admin, $owner] = [$owner, $admin];
        }

        $this->assertDatabaseCount('property_documents', 2);
        $this->get(route('documents.index'))->assertOk()->assertSee('Plot 12 ec')->assertSee('Plot 12 patta_chitta');
        $this->get(route('documents.index', ['type' => 'ec']))->assertOk()
            ->assertSee('Plot 12 ec')->assertDontSee('Plot 12 patta_chitta');
    }

    public function test_employee_cannot_list_upload_or_download_documents(): void
    {
        Storage::fake('local');
        $this->actingAs(User::factory()->create(['role' => 'admin']))->post(route('documents.store'), [
            'title' => 'Private EC', 'type' => 'ec',
            'document' => UploadedFile::fake()->create('ec.pdf', 20, 'application/pdf'),
        ])->assertRedirect();
        $document = PropertyDocument::firstOrFail();

        $this->actingAs(User::factory()->create(['role' => 'employee']));
        $this->get(route('documents.index'))->assertForbidden();
        $this->post(route('documents.store'), [
            'title' => 'Blocked', 'type' => 'ec',
            'document' => UploadedFile::fake()->create('ec.pdf', 20, 'application/pdf'),
        ])->assertForbidden();
        $this->get(route('documents.download', $document))->assertForbidden();
        $this->assertDatabaseCount('property_documents', 1);
    }

    public function test_guests_must_log_in_for_every_document_action(): void
    {
        $this->get(route('documents.index'))->assertRedirect(route('login'));
        $this->post(route('documents.store'))->assertRedirect(route('login'));
        $this->get(route('documents.download', 1))->assertRedirect(route('login'));
    }

    public function test_upload_rejects_invalid_types_oversized_files_and_missing_fields(): void
    {
        Storage::fake('local');
        $this->actingAs(User::factory()->create(['role' => 'admin']));
        $this->post(route('documents.store'), [])->assertSessionHasErrors(['title', 'type', 'document']);
        $this->post(route('documents.store'), [
            'title' => 'Invalid', 'type' => 'unknown',
            'document' => UploadedFile::fake()->create('script.php', 1, 'text/plain'),
        ])->assertSessionHasErrors(['type', 'document']);
        $this->post(route('documents.store'), [
            'title' => 'Oversized', 'type' => 'ec',
            'document' => UploadedFile::fake()->create('large.pdf', 10241, 'application/pdf'),
        ])->assertSessionHasErrors('document');
        $this->assertDatabaseCount('property_documents', 0);
        $this->assertSame([], Storage::disk('local')->allFiles());
    }

    public function test_missing_document_and_missing_stored_file_return_not_found(): void
    {
        Storage::fake('local');
        $this->actingAs(User::factory()->create(['role' => 'owner']));
        $this->get(route('documents.download', 999))->assertNotFound();
        $this->post(route('documents.store'), [
            'title' => 'Missing file', 'type' => 'ec',
            'document' => UploadedFile::fake()->create('ec.pdf', 20, 'application/pdf'),
        ])->assertRedirect();
        $document = PropertyDocument::firstOrFail();
        Storage::disk('local')->delete($document->path);
        $this->get(route('documents.download', $document))->assertNotFound();
    }
}
