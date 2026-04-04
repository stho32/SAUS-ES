<?php

use App\Models\Comment;
use App\Models\Ticket;
use App\Models\TicketStatus;

beforeEach(function () {
    authenticateWithMasterLink();
    seedStatuses();

    $this->ticket = Ticket::create([
        'ticket_number' => '20250113-0001',
        'title' => 'Comment Test Ticket',
        'description' => 'Beschreibung',
        'status_id' => TicketStatus::where('name', 'offen')->first()->id,
    ]);
});

test('comment can be added to ticket', function () {
    $this->postJson(route('api.comments.store', $this->ticket), [
        'content' => 'Dies ist ein Testkommentar',
    ])->assertOk()->assertJson([
        'success' => true,
        'data' => [
            'content' => 'Dies ist ein Testkommentar',
            'username' => 'TestUser',
        ],
    ]);

    $this->assertDatabaseHas('comments', [
        'ticket_id' => $this->ticket->id,
        'content' => 'Dies ist ein Testkommentar',
        'username' => 'TestUser',
    ]);
});

test('comment requires content', function () {
    $this->postJson(route('api.comments.store', $this->ticket), [
        'content' => '',
    ])->assertStatus(422);
});

test('own comment can be edited', function () {
    $comment = Comment::create([
        'ticket_id' => $this->ticket->id,
        'username' => 'TestUser',
        'content' => 'Original',
    ]);

    $this->putJson(route('api.comments.update', $comment), [
        'content' => 'Bearbeitet',
    ])->assertOk()->assertJson([
        'success' => true,
        'data' => [
            'content' => 'Bearbeitet',
            'is_edited' => true,
        ],
    ]);

    expect($comment->fresh()->content)->toBe('Bearbeitet');
    expect($comment->fresh()->is_edited)->toBeTrue();
});

test('cannot edit other users comment', function () {
    $comment = Comment::create([
        'ticket_id' => $this->ticket->id,
        'username' => 'OtherUser',
        'content' => 'Anderer Kommentar',
    ]);

    $this->putJson(route('api.comments.update', $comment), [
        'content' => 'Versuch zu bearbeiten',
    ])->assertStatus(403)->assertJson([
        'success' => false,
    ]);

    expect($comment->fresh()->content)->toBe('Anderer Kommentar');
});

test('comment visibility can be toggled', function () {
    $comment = Comment::create([
        'ticket_id' => $this->ticket->id,
        'username' => 'TestUser',
        'content' => 'Sichtbar',
        'is_visible' => true,
    ]);

    // Hide comment
    $this->postJson(route('api.comments.visibility', $comment), [
        'is_visible' => false,
    ])->assertOk()->assertJson([
        'success' => true,
        'data' => [
            'is_visible' => false,
            'hidden_by' => 'TestUser',
        ],
    ]);

    $fresh = $comment->fresh();
    expect($fresh->is_visible)->toBeFalse();
    expect($fresh->hidden_by)->toBe('TestUser');
    expect($fresh->hidden_at)->not->toBeNull();
});

test('comment visibility can be restored', function () {
    $comment = Comment::create([
        'ticket_id' => $this->ticket->id,
        'username' => 'TestUser',
        'content' => 'Versteckt',
        'is_visible' => false,
        'hidden_by' => 'TestUser',
        'hidden_at' => now(),
    ]);

    // Show comment
    $this->postJson(route('api.comments.visibility', $comment), [
        'is_visible' => true,
    ])->assertOk()->assertJson([
        'success' => true,
        'data' => [
            'is_visible' => true,
        ],
    ]);

    $fresh = $comment->fresh();
    expect($fresh->is_visible)->toBeTrue();
    expect($fresh->hidden_by)->toBeNull();
    expect($fresh->hidden_at)->toBeNull();
});

test('cannot toggle visibility on closed ticket comment', function () {
    $closedStatus = TicketStatus::where('name', 'gescheitert')->first();
    $closedTicket = Ticket::create([
        'ticket_number' => '20250113-0002',
        'title' => 'Closed Ticket',
        'description' => 'D',
        'status_id' => $closedStatus->id,
    ]);

    $comment = Comment::create([
        'ticket_id' => $closedTicket->id,
        'username' => 'TestUser',
        'content' => 'In geschlossenem Ticket',
    ]);

    $this->postJson(route('api.comments.visibility', $comment), [
        'is_visible' => false,
    ])->assertStatus(403);
});

test('cannot toggle visibility on archived ticket comment', function () {
    $archivedStatus = TicketStatus::where('name', 'archiviert')->first();
    $archivedTicket = Ticket::create([
        'ticket_number' => '20250113-0003',
        'title' => 'Archived Ticket',
        'description' => 'D',
        'status_id' => $archivedStatus->id,
    ]);

    $comment = Comment::create([
        'ticket_id' => $archivedTicket->id,
        'username' => 'TestUser',
        'content' => 'In archiviertem Ticket',
    ]);

    $this->postJson(route('api.comments.visibility', $comment), [
        'is_visible' => false,
    ])->assertStatus(403);
});

test('system comment is created on ticket creation', function () {
    $status = TicketStatus::where('name', 'offen')->first();

    $this->post(route('tickets.store'), [
        'title' => 'System Comment Ticket',
        'description' => 'Test',
        'status_id' => $status->id,
    ]);

    $ticket = Ticket::where('title', 'System Comment Ticket')->first();

    $systemComment = Comment::where('ticket_id', $ticket->id)
        ->where('username', 'System')
        ->first();

    expect($systemComment)->not->toBeNull();
    expect($systemComment->content)->toContain('Ticket erstellt');
    expect($systemComment->content)->toContain('offen');
});

test('system comment is created on status change', function () {
    $openStatus = TicketStatus::where('name', 'offen')->first();
    $inProgressStatus = TicketStatus::where('name', 'in_bearbeitung')->first();

    $this->postJson(route('api.tickets.status', $this->ticket), [
        'status_id' => $inProgressStatus->id,
    ]);

    $systemComment = Comment::where('ticket_id', $this->ticket->id)
        ->where('username', 'System')
        ->where('content', 'like', '%Status geändert%')
        ->first();

    expect($systemComment)->not->toBeNull();
    expect($systemComment->content)->toContain('offen');
    expect($systemComment->content)->toContain('in_bearbeitung');
});

test('comment store returns formatted content', function () {
    $this->postJson(route('api.comments.store', $this->ticket), [
        'content' => '**fett** und *kursiv*',
    ])->assertOk()->assertJsonFragment([
        'formatted_content' => '<strong>fett</strong> und <em>kursiv</em>',
    ]);
});

test('multiple comments can be added to same ticket', function () {
    $this->postJson(route('api.comments.store', $this->ticket), [
        'content' => 'Erster Kommentar',
    ])->assertOk();

    $this->postJson(route('api.comments.store', $this->ticket), [
        'content' => 'Zweiter Kommentar',
    ])->assertOk();

    expect(Comment::where('ticket_id', $this->ticket->id)->count())->toBe(2);
});
