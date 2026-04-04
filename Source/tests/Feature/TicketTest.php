<?php

use App\Models\Ticket;
use App\Models\TicketStatus;

beforeEach(function () {
    authenticateWithMasterLink();
    seedStatuses();
});

test('ticket index page loads', function () {
    $this->get(route('tickets.index'))->assertOk();
});

test('ticket index shows tickets', function () {
    $status = TicketStatus::where('name', 'offen')->first();
    Ticket::create([
        'ticket_number' => '20250113-0001',
        'title' => 'Wasserrohrbruch Keller',
        'description' => 'Beschreibung',
        'status_id' => $status->id,
    ]);

    $this->get(route('tickets.index'))
        ->assertOk()
        ->assertSee('Wasserrohrbruch Keller');
});

test('ticket can be created', function () {
    $status = TicketStatus::where('name', 'offen')->first();

    $this->post(route('tickets.store'), [
        'title' => 'Neues Ticket',
        'description' => 'Beschreibung des Problems',
        'status_id' => $status->id,
    ])->assertRedirect();

    $this->assertDatabaseHas('tickets', ['title' => 'Neues Ticket']);
});

test('ticket requires title', function () {
    $status = TicketStatus::first();

    $this->post(route('tickets.store'), [
        'description' => 'Beschreibung',
        'status_id' => $status->id,
    ])->assertSessionHasErrors('title');
});

test('ticket requires description', function () {
    $status = TicketStatus::first();

    $this->post(route('tickets.store'), [
        'title' => 'Ein Titel',
        'status_id' => $status->id,
    ])->assertSessionHasErrors('description');
});

test('ticket requires valid status', function () {
    $this->post(route('tickets.store'), [
        'title' => 'Ein Titel',
        'description' => 'Beschreibung',
        'status_id' => 9999,
    ])->assertSessionHasErrors('status_id');
});

test('ticket show page loads', function () {
    $ticket = Ticket::create([
        'ticket_number' => '20250113-0001',
        'title' => 'Test Ticket',
        'description' => 'Beschreibung',
        'status_id' => TicketStatus::first()->id,
    ]);

    $this->get(route('tickets.show', $ticket))->assertOk()->assertSee('Test Ticket');
});

test('ticket edit route no longer exists', function () {
    $ticket = Ticket::create([
        'ticket_number' => '20250113-0001',
        'title' => 'Test',
        'description' => 'Desc',
        'status_id' => TicketStatus::first()->id,
    ]);

    $this->get('/saus/tickets/' . $ticket->id . '/edit')->assertNotFound();
});

test('ticket can be updated via API', function () {
    $ticket = Ticket::create([
        'ticket_number' => '20250113-0001',
        'title' => 'Alt',
        'description' => 'Alt',
        'status_id' => TicketStatus::first()->id,
    ]);

    $this->putJson(route('api.tickets.update', $ticket), [
        'title' => 'Neu',
        'description' => 'Neue Beschreibung',
        'status_id' => TicketStatus::first()->id,
    ])->assertOk()->assertJson(['success' => true]);

    expect($ticket->fresh()->title)->toBe('Neu');
});

test('ticket creates with auto-generated number', function () {
    $status = TicketStatus::first();

    $this->post(route('tickets.store'), [
        'title' => 'Auto Number Test',
        'description' => 'Test',
        'status_id' => $status->id,
    ]);

    $ticket = Ticket::where('title', 'Auto Number Test')->first();
    expect($ticket->ticket_number)->toMatch('/^\d{8}-\d{4}$/');
});

test('ticket creates with secret string', function () {
    $status = TicketStatus::first();

    $this->post(route('tickets.store'), [
        'title' => 'Secret Test',
        'description' => 'Test',
        'status_id' => $status->id,
    ]);

    $ticket = Ticket::where('title', 'Secret Test')->first();
    expect($ticket->secret_string)->toHaveLength(50);
});

test('ticket index filters by status category', function () {
    $open = TicketStatus::where('name', 'offen')->first();
    $archived = TicketStatus::where('name', 'archiviert')->first();

    Ticket::create(['ticket_number' => '20250113-0001', 'title' => 'Offenes Ticket', 'description' => 'D', 'status_id' => $open->id]);
    Ticket::create(['ticket_number' => '20250113-0002', 'title' => 'Archiviertes Ticket', 'description' => 'D', 'status_id' => $archived->id]);

    $this->get(route('tickets.index', ['filter' => 'in_bearbeitung']))
        ->assertSee('Offenes Ticket')
        ->assertDontSee('Archiviertes Ticket');
});

test('ticket index searches by title', function () {
    $status = TicketStatus::first();
    Ticket::create(['ticket_number' => '20250113-0001', 'title' => 'Wasserrohrbruch', 'description' => 'D', 'status_id' => $status->id]);
    Ticket::create(['ticket_number' => '20250113-0002', 'title' => 'Laermbelaestigung', 'description' => 'D', 'status_id' => $status->id]);

    $this->get(route('tickets.index', ['search' => 'Wasser']))
        ->assertSee('Wasserrohrbruch')
        ->assertDontSee('Laermbelaestigung');
});

test('ticket email view loads', function () {
    $ticket = Ticket::create([
        'ticket_number' => '20250113-0001',
        'title' => 'Email Test',
        'description' => 'D',
        'status_id' => TicketStatus::first()->id,
    ]);

    $this->get(route('tickets.email', $ticket))->assertOk();
});

test('ticket create page loads', function () {
    $this->get(route('tickets.create'))->assertOk();
});

test('ticket creation creates system comment', function () {
    $status = TicketStatus::where('name', 'offen')->first();

    $this->post(route('tickets.store'), [
        'title' => 'System Comment Test',
        'description' => 'Test',
        'status_id' => $status->id,
    ]);

    $ticket = Ticket::where('title', 'System Comment Test')->first();

    $this->assertDatabaseHas('comments', [
        'ticket_id' => $ticket->id,
        'username' => 'System',
    ]);
});

test('ticket status can be updated via API', function () {
    $openStatus = TicketStatus::where('name', 'offen')->first();
    $closedStatus = TicketStatus::where('name', 'gescheitert')->first();

    $ticket = Ticket::create([
        'ticket_number' => '20250113-0001',
        'title' => 'Status Test',
        'description' => 'D',
        'status_id' => $openStatus->id,
    ]);

    $this->postJson(route('api.tickets.status', $ticket), [
        'status_id' => $closedStatus->id,
    ])->assertOk()->assertJson(['success' => true]);

    expect($ticket->fresh()->status_id)->toBe($closedStatus->id);
});

test('ticket status change to closed sets closed_at', function () {
    $openStatus = TicketStatus::where('name', 'offen')->first();
    $closedStatus = TicketStatus::where('name', 'gescheitert')->first();

    $ticket = Ticket::create([
        'ticket_number' => '20250113-0001',
        'title' => 'Closed At Test',
        'description' => 'D',
        'status_id' => $openStatus->id,
    ]);

    expect($ticket->closed_at)->toBeNull();

    $this->postJson(route('api.tickets.status', $ticket), [
        'status_id' => $closedStatus->id,
    ]);

    expect($ticket->fresh()->closed_at)->not->toBeNull();
});

test('ticket assignee can be updated via API', function () {
    $ticket = Ticket::create([
        'ticket_number' => '20250113-0001',
        'title' => 'Assignee Test',
        'description' => 'D',
        'status_id' => TicketStatus::first()->id,
    ]);

    $this->postJson(route('api.tickets.assignee', $ticket), [
        'assignee' => 'Max Mustermann',
    ])->assertOk()->assertJson(['success' => true]);

    expect($ticket->fresh()->assignee)->toBe('Max Mustermann');
});

test('ticket follow-up date can be set via API', function () {
    $ticket = Ticket::create([
        'ticket_number' => '20250113-0001',
        'title' => 'Follow Up Test',
        'description' => 'D',
        'status_id' => TicketStatus::first()->id,
    ]);

    $this->postJson(route('api.tickets.follow-up', $ticket), [
        'follow_up_date' => '2026-06-01',
    ])->assertOk()->assertJson(['success' => true]);

    expect($ticket->fresh()->follow_up_date->toDateString())->toBe('2026-06-01');
});

test('ticket follow-up date can be removed via API', function () {
    $ticket = Ticket::create([
        'ticket_number' => '20250113-0001',
        'title' => 'Remove Follow Up Test',
        'description' => 'D',
        'status_id' => TicketStatus::first()->id,
        'follow_up_date' => '2026-06-01',
    ]);

    $this->postJson(route('api.tickets.follow-up', $ticket), [
        'follow_up_date' => null,
    ])->assertOk();

    expect($ticket->fresh()->follow_up_date)->toBeNull();
});

test('ticket index shows all tickets when filter is alle', function () {
    $open = TicketStatus::where('name', 'offen')->first();
    $archived = TicketStatus::where('name', 'archiviert')->first();

    Ticket::create(['ticket_number' => '20250113-0001', 'title' => 'Offenes Ticket', 'description' => 'D', 'status_id' => $open->id]);
    Ticket::create(['ticket_number' => '20250113-0002', 'title' => 'Archiviertes Ticket', 'description' => 'D', 'status_id' => $archived->id]);

    $this->get(route('tickets.index', ['filter' => 'alle']))
        ->assertSee('Offenes Ticket')
        ->assertSee('Archiviertes Ticket');
});

test('ticket votes can be retrieved via API', function () {
    $ticket = Ticket::create([
        'ticket_number' => '20250113-0001',
        'title' => 'Vote Count Test',
        'description' => 'D',
        'status_id' => TicketStatus::first()->id,
    ]);

    $this->getJson(route('api.tickets.votes', $ticket))
        ->assertOk()
        ->assertJson([
            'success' => true,
            'data' => ['up_votes' => 0, 'down_votes' => 0],
        ]);
});
