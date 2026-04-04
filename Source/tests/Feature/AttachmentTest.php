<?php

use App\Models\Ticket;
use App\Models\TicketAttachment;
use App\Models\TicketStatus;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    authenticateWithMasterLink();
    seedStatuses();

    $this->ticket = Ticket::create([
        'ticket_number' => '20250113-0001',
        'title' => 'Upload Test',
        'description' => 'Desc',
        'status_id' => TicketStatus::first()->id,
    ]);

    Storage::fake('local');
});

test('jpg file can be uploaded', function () {
    $file = UploadedFile::fake()->image('foto.jpg', 100, 100);

    $this->postJson(route('api.attachments.store', $this->ticket), [
        'file' => $file,
    ])->assertOk()->assertJson(['success' => true]);

    expect(TicketAttachment::where('ticket_id', $this->ticket->id)->count())->toBe(1);
    $attachment = TicketAttachment::where('ticket_id', $this->ticket->id)->first();
    expect($attachment->original_filename)->toBe('foto.jpg');
    expect($attachment->file_type)->toBe('image/jpeg');
});

test('png file can be uploaded', function () {
    $file = UploadedFile::fake()->image('bild.png', 100, 100);

    $this->postJson(route('api.attachments.store', $this->ticket), [
        'file' => $file,
    ])->assertOk()->assertJson(['success' => true]);

    expect(TicketAttachment::where('ticket_id', $this->ticket->id)->count())->toBe(1);
});

test('pdf file can be uploaded', function () {
    $file = UploadedFile::fake()->create('dokument.pdf', 500, 'application/pdf');

    $this->postJson(route('api.attachments.store', $this->ticket), [
        'file' => $file,
    ])->assertOk()->assertJson(['success' => true]);

    $attachment = TicketAttachment::where('ticket_id', $this->ticket->id)->first();
    expect($attachment->original_filename)->toBe('dokument.pdf');
});

test('txt file can be uploaded', function () {
    $file = UploadedFile::fake()->create('notiz.txt', 10, 'text/plain');

    $this->postJson(route('api.attachments.store', $this->ticket), [
        'file' => $file,
    ])->assertOk()->assertJson(['success' => true]);
});

test('exe file is rejected', function () {
    $file = UploadedFile::fake()->create('malware.exe', 100, 'application/x-msdownload');

    $this->postJson(route('api.attachments.store', $this->ticket), [
        'file' => $file,
    ])->assertStatus(422)->assertJson(['success' => false]);

    expect(TicketAttachment::where('ticket_id', $this->ticket->id)->count())->toBe(0);
});

test('php file is rejected', function () {
    $file = UploadedFile::fake()->create('shell.php', 10, 'text/plain');

    $this->postJson(route('api.attachments.store', $this->ticket), [
        'file' => $file,
    ])->assertStatus(422)->assertJson(['success' => false]);
});

test('upload without file is rejected', function () {
    $this->postJson(route('api.attachments.store', $this->ticket), [])
        ->assertStatus(422);
});

test('uploaded file is stored on disk', function () {
    $file = UploadedFile::fake()->image('test.jpg', 50, 50);

    $this->postJson(route('api.attachments.store', $this->ticket), [
        'file' => $file,
    ])->assertOk();

    $attachment = TicketAttachment::where('ticket_id', $this->ticket->id)->first();
    $path = config('saus.upload_path', 'uploads/tickets') . '/' . $this->ticket->id . '/' . $attachment->filename;
    Storage::disk('local')->assertExists($path);
});

test('attachment records upload username', function () {
    $file = UploadedFile::fake()->image('test.jpg', 50, 50);

    $this->postJson(route('api.attachments.store', $this->ticket), [
        'file' => $file,
    ])->assertOk();

    $attachment = TicketAttachment::where('ticket_id', $this->ticket->id)->first();
    expect($attachment->uploaded_by)->toBe('TestUser');
});

test('attachment can be deleted', function () {
    $file = UploadedFile::fake()->image('delete-me.jpg', 50, 50);

    $this->postJson(route('api.attachments.store', $this->ticket), [
        'file' => $file,
    ])->assertOk();

    $attachment = TicketAttachment::where('ticket_id', $this->ticket->id)->first();

    $this->deleteJson(route('api.attachments.destroy', $attachment))
        ->assertOk()->assertJson(['success' => true]);

    expect(TicketAttachment::find($attachment->id))->toBeNull();
});

test('error message lists allowed extensions', function () {
    $file = UploadedFile::fake()->create('bad.exe', 100, 'application/x-msdownload');

    $response = $this->postJson(route('api.attachments.store', $this->ticket), [
        'file' => $file,
    ])->assertStatus(422);

    expect($response->json('message'))->toContain('jpg');
    expect($response->json('message'))->toContain('pdf');
});
