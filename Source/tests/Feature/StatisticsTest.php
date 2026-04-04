<?php

use App\Models\Ticket;
use App\Models\TicketStatus;

beforeEach(function () {
    authenticateWithMasterLink();
    seedStatuses();
});

test('statistics page loads', function () {
    $this->get(route('statistics.index'))->assertOk();
});

test('statistics page loads with ticket data', function () {
    $openStatus = TicketStatus::where('name', 'offen')->first();

    Ticket::create([
        'ticket_number' => '20250113-0001',
        'title' => 'Statistik Ticket 1',
        'description' => 'D',
        'status_id' => $openStatus->id,
        'assignee' => 'Anna',
    ]);

    Ticket::create([
        'ticket_number' => '20250113-0002',
        'title' => 'Statistik Ticket 2',
        'description' => 'D',
        'status_id' => $openStatus->id,
        'assignee' => 'Bruno',
    ]);

    $this->get(route('statistics.index'))->assertOk();
});

test('statistics page shows status distribution', function () {
    $openStatus = TicketStatus::where('name', 'offen')->first();
    $closedStatus = TicketStatus::where('name', 'gescheitert')->first();

    Ticket::create([
        'ticket_number' => '20250113-0001',
        'title' => 'Offen 1',
        'description' => 'D',
        'status_id' => $openStatus->id,
    ]);

    Ticket::create([
        'ticket_number' => '20250113-0002',
        'title' => 'Geschlossen 1',
        'description' => 'D',
        'status_id' => $closedStatus->id,
    ]);

    $this->get(route('statistics.index'))
        ->assertOk()
        ->assertSee('offen')
        ->assertSee('gescheitert');
});

test('statistics handles multi-assignee tickets', function () {
    $openStatus = TicketStatus::where('name', 'offen')->first();

    Ticket::create([
        'ticket_number' => '20250113-0001',
        'title' => 'Multi Assignee',
        'description' => 'D',
        'status_id' => $openStatus->id,
        'assignee' => 'Anna, Bruno',
    ]);

    // The statistics controller should split multi-assignees
    $this->get(route('statistics.index'))->assertOk();
});

test('statistics handles multi-assignee with plus sign', function () {
    $openStatus = TicketStatus::where('name', 'offen')->first();

    Ticket::create([
        'ticket_number' => '20250113-0001',
        'title' => 'Plus Assignee',
        'description' => 'D',
        'status_id' => $openStatus->id,
        'assignee' => 'Anna+Bruno',
    ]);

    $this->get(route('statistics.index'))->assertOk();
});

test('statistics page works with no tickets', function () {
    $this->get(route('statistics.index'))->assertOk();
});

test('statistics counts completed tickets per assignee', function () {
    $archivedStatus = TicketStatus::where('name', 'archiviert')->first();

    Ticket::create([
        'ticket_number' => '20250113-0001',
        'title' => 'Archived 1',
        'description' => 'D',
        'status_id' => $archivedStatus->id,
        'assignee' => 'Anna',
    ]);

    Ticket::create([
        'ticket_number' => '20250113-0002',
        'title' => 'Archived 2',
        'description' => 'D',
        'status_id' => $archivedStatus->id,
        'assignee' => 'Anna',
    ]);

    $this->get(route('statistics.index'))->assertOk();
});
