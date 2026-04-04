<?php

use App\Models\Ticket;
use App\Models\TicketStatus;
use App\Services\TicketNumberGenerator;

beforeEach(function () {
    seedStatuses();
    $this->generator = new TicketNumberGenerator();
});

test('generates ticket number in correct format', function () {
    $number = $this->generator->generate();

    expect($number)->toMatch('/^\d{8}-\d{4}$/');
});

test('generates ticket number with todays date', function () {
    $number = $this->generator->generate();
    $today = date('Ymd');

    expect($number)->toStartWith($today . '-');
});

test('first ticket of the day gets 0001', function () {
    $number = $this->generator->generate();

    expect($number)->toEndWith('-0001');
});

test('increments within same day', function () {
    $status = TicketStatus::first();

    Ticket::create([
        'ticket_number' => date('Ymd') . '-0001',
        'title' => 'Erstes Ticket',
        'description' => 'D',
        'status_id' => $status->id,
    ]);

    $number = $this->generator->generate();

    expect($number)->toEndWith('-0002');
});

test('increments correctly after multiple tickets', function () {
    $status = TicketStatus::first();
    $today = date('Ymd');

    Ticket::create([
        'ticket_number' => $today . '-0001',
        'title' => 'Ticket 1',
        'description' => 'D',
        'status_id' => $status->id,
    ]);

    Ticket::create([
        'ticket_number' => $today . '-0002',
        'title' => 'Ticket 2',
        'description' => 'D',
        'status_id' => $status->id,
    ]);

    Ticket::create([
        'ticket_number' => $today . '-0003',
        'title' => 'Ticket 3',
        'description' => 'D',
        'status_id' => $status->id,
    ]);

    $number = $this->generator->generate();

    expect($number)->toBe($today . '-0004');
});

test('does not conflict with other days tickets', function () {
    $status = TicketStatus::first();

    // Ticket from yesterday
    Ticket::create([
        'ticket_number' => '20260401-0005',
        'title' => 'Gestern',
        'description' => 'D',
        'status_id' => $status->id,
    ]);

    // Today's first ticket should still be 0001
    $number = $this->generator->generate();

    expect($number)->toEndWith('-0001');
});

test('pads number to 4 digits', function () {
    $number = $this->generator->generate();

    $parts = explode('-', $number);
    $sequentialPart = $parts[1];

    expect(strlen($sequentialPart))->toBe(4);
});

test('handles double-digit increments', function () {
    $status = TicketStatus::first();
    $today = date('Ymd');

    // Create ticket with number 0010
    Ticket::create([
        'ticket_number' => $today . '-0010',
        'title' => 'Ticket 10',
        'description' => 'D',
        'status_id' => $status->id,
    ]);

    $number = $this->generator->generate();

    expect($number)->toBe($today . '-0011');
});
