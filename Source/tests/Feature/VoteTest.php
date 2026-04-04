<?php

use App\Models\Comment;
use App\Models\CommentVote;
use App\Models\Ticket;
use App\Models\TicketStatus;
use App\Models\TicketVote;

beforeEach(function () {
    authenticateWithMasterLink();
    seedStatuses();

    $this->ticket = Ticket::create([
        'ticket_number' => '20250113-0001',
        'title' => 'Vote Test Ticket',
        'description' => 'Beschreibung',
        'status_id' => TicketStatus::where('name', 'offen')->first()->id,
    ]);
});

test('user can vote up on ticket', function () {
    $this->postJson(route('api.tickets.vote', $this->ticket), [
        'value' => 'up',
    ])->assertOk()->assertJson([
        'success' => true,
        'data' => ['up_votes' => 1, 'down_votes' => 0],
    ]);

    $this->assertDatabaseHas('ticket_votes', [
        'ticket_id' => $this->ticket->id,
        'username' => 'TestUser',
        'value' => 'up',
    ]);
});

test('user can vote down on ticket', function () {
    $this->postJson(route('api.tickets.vote', $this->ticket), [
        'value' => 'down',
    ])->assertOk()->assertJson([
        'success' => true,
        'data' => ['up_votes' => 0, 'down_votes' => 1],
    ]);

    $this->assertDatabaseHas('ticket_votes', [
        'ticket_id' => $this->ticket->id,
        'username' => 'TestUser',
        'value' => 'down',
    ]);
});

test('user can remove vote from ticket', function () {
    TicketVote::create([
        'ticket_id' => $this->ticket->id,
        'username' => 'TestUser',
        'value' => 'up',
    ]);

    $this->postJson(route('api.tickets.vote', $this->ticket), [
        'value' => 'none',
    ])->assertOk()->assertJson([
        'success' => true,
        'data' => ['up_votes' => 0, 'down_votes' => 0],
    ]);

    $this->assertDatabaseMissing('ticket_votes', [
        'ticket_id' => $this->ticket->id,
        'username' => 'TestUser',
    ]);
});

test('user can change vote direction on ticket', function () {
    TicketVote::create([
        'ticket_id' => $this->ticket->id,
        'username' => 'TestUser',
        'value' => 'up',
    ]);

    $this->postJson(route('api.tickets.vote', $this->ticket), [
        'value' => 'down',
    ])->assertOk()->assertJson([
        'success' => true,
        'data' => ['up_votes' => 0, 'down_votes' => 1],
    ]);

    expect(TicketVote::where('ticket_id', $this->ticket->id)
        ->where('username', 'TestUser')->first()->value)->toBe('down');
});

test('user can vote up on comment', function () {
    $comment = Comment::create([
        'ticket_id' => $this->ticket->id,
        'username' => 'OtherUser',
        'content' => 'Ein Kommentar',
    ]);

    $this->postJson(route('api.comments.vote', $comment), [
        'value' => 'up',
    ])->assertOk()->assertJson([
        'success' => true,
        'data' => ['up_votes' => 1, 'down_votes' => 0],
    ]);

    $this->assertDatabaseHas('comment_votes', [
        'comment_id' => $comment->id,
        'username' => 'TestUser',
        'value' => 'up',
    ]);
});

test('user can vote down on comment', function () {
    $comment = Comment::create([
        'ticket_id' => $this->ticket->id,
        'username' => 'OtherUser',
        'content' => 'Ein Kommentar',
    ]);

    $this->postJson(route('api.comments.vote', $comment), [
        'value' => 'down',
    ])->assertOk()->assertJson([
        'success' => true,
        'data' => ['up_votes' => 0, 'down_votes' => 1],
    ]);
});

test('user can remove vote from comment', function () {
    $comment = Comment::create([
        'ticket_id' => $this->ticket->id,
        'username' => 'OtherUser',
        'content' => 'Ein Kommentar',
    ]);

    CommentVote::create([
        'comment_id' => $comment->id,
        'username' => 'TestUser',
        'value' => 'up',
    ]);

    $this->postJson(route('api.comments.vote', $comment), [
        'value' => 'none',
    ])->assertOk()->assertJson([
        'success' => true,
        'data' => ['up_votes' => 0, 'down_votes' => 0],
    ]);

    $this->assertDatabaseMissing('comment_votes', [
        'comment_id' => $comment->id,
        'username' => 'TestUser',
    ]);
});

test('invalid vote value is rejected for ticket', function () {
    $this->postJson(route('api.tickets.vote', $this->ticket), [
        'value' => 'invalid',
    ])->assertStatus(422);
});

test('invalid vote value is rejected for comment', function () {
    $comment = Comment::create([
        'ticket_id' => $this->ticket->id,
        'username' => 'OtherUser',
        'content' => 'Ein Kommentar',
    ]);

    $this->postJson(route('api.comments.vote', $comment), [
        'value' => 'invalid',
    ])->assertStatus(422);
});

test('multiple users can vote on same ticket', function () {
    // First user votes
    TicketVote::create([
        'ticket_id' => $this->ticket->id,
        'username' => 'UserA',
        'value' => 'up',
    ]);

    // Authenticated user votes
    $this->postJson(route('api.tickets.vote', $this->ticket), [
        'value' => 'up',
    ])->assertOk()->assertJson([
        'success' => true,
        'data' => ['up_votes' => 2, 'down_votes' => 0],
    ]);
});

test('ticket vote response includes voter names', function () {
    TicketVote::create([
        'ticket_id' => $this->ticket->id,
        'username' => 'UserA',
        'value' => 'up',
    ]);

    $response = $this->postJson(route('api.tickets.vote', $this->ticket), [
        'value' => 'up',
    ])->assertOk();

    $data = $response->json('data');
    expect($data)->toHaveKey('upvoters');
    expect($data)->toHaveKey('downvoters');
    expect($data['upvoters'])->toContain('UserA');
    expect($data['upvoters'])->toContain('TestUser');
});

test('multiple users can vote on same comment', function () {
    $comment = Comment::create([
        'ticket_id' => $this->ticket->id,
        'username' => 'Author',
        'content' => 'Populaerer Kommentar',
    ]);

    CommentVote::create([
        'comment_id' => $comment->id,
        'username' => 'UserA',
        'value' => 'up',
    ]);

    CommentVote::create([
        'comment_id' => $comment->id,
        'username' => 'UserB',
        'value' => 'down',
    ]);

    $this->postJson(route('api.comments.vote', $comment), [
        'value' => 'up',
    ])->assertOk()->assertJson([
        'success' => true,
        'data' => ['up_votes' => 2, 'down_votes' => 1],
    ]);
});

test('ticket model computes up_votes attribute', function () {
    TicketVote::create(['ticket_id' => $this->ticket->id, 'username' => 'A', 'value' => 'up']);
    TicketVote::create(['ticket_id' => $this->ticket->id, 'username' => 'B', 'value' => 'up']);
    TicketVote::create(['ticket_id' => $this->ticket->id, 'username' => 'C', 'value' => 'down']);

    expect($this->ticket->up_votes)->toBe(2);
    expect($this->ticket->down_votes)->toBe(1);
});

test('comment model computes up_votes attribute', function () {
    $comment = Comment::create([
        'ticket_id' => $this->ticket->id,
        'username' => 'Author',
        'content' => 'Test',
    ]);

    CommentVote::create(['comment_id' => $comment->id, 'username' => 'A', 'value' => 'up']);
    CommentVote::create(['comment_id' => $comment->id, 'username' => 'B', 'value' => 'down']);

    expect($comment->up_votes)->toBe(1);
    expect($comment->down_votes)->toBe(1);
});
