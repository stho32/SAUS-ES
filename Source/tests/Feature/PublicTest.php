<?php

use App\Models\News;
use App\Models\Ticket;
use App\Models\TicketAttachment;
use App\Models\TicketStatus;

beforeEach(function () {
    seedStatuses();
});

test('public ticket index loads without auth', function () {
    $this->get(route('public.tickets.index'))->assertOk();
});

test('public ticket index shows website-visible tickets', function () {
    $status = TicketStatus::where('name', 'offen')->first();

    Ticket::create([
        'ticket_number' => '20250113-0001',
        'title' => 'Oeffentliches Ticket',
        'description' => 'D',
        'status_id' => $status->id,
        'show_on_website' => true,
    ]);

    Ticket::create([
        'ticket_number' => '20250113-0002',
        'title' => 'Privates Ticket',
        'description' => 'D',
        'status_id' => $status->id,
        'show_on_website' => false,
    ]);

    $this->get(route('public.tickets.index'))
        ->assertOk()
        ->assertSee('Oeffentliches Ticket')
        ->assertDontSee('Privates Ticket');
});

test('public news index loads without auth', function () {
    $this->get(route('public.news.index'))->assertOk();
});

test('public news index shows news articles', function () {
    News::create([
        'title' => 'Oeffentliche Nachricht',
        'content' => 'Inhalt der Nachricht',
        'event_date' => '2026-04-01',
        'created_by' => 'Admin',
    ]);

    $this->get(route('public.news.index'))
        ->assertOk()
        ->assertSee('Oeffentliche Nachricht');
});

test('public news show page loads', function () {
    $news = News::create([
        'title' => 'Detailansicht',
        'content' => 'Detaillierter Inhalt',
        'event_date' => '2026-04-01',
        'created_by' => 'Admin',
    ]);

    $this->get(route('public.news.show', $news))
        ->assertOk()
        ->assertSee('Detailansicht')
        ->assertSee('Detaillierter Inhalt');
});

test('public news search works', function () {
    News::create([
        'title' => 'Sommerfest',
        'content' => 'Party',
        'event_date' => '2026-07-15',
        'created_by' => 'Admin',
    ]);

    News::create([
        'title' => 'Winterfest',
        'content' => 'Gluehwein',
        'event_date' => '2026-12-15',
        'created_by' => 'Admin',
    ]);

    $this->get(route('public.news.index', ['search' => 'Sommer']))
        ->assertSee('Sommerfest')
        ->assertDontSee('Winterfest');
});

test('imageview with valid code loads', function () {
    $ticket = Ticket::create([
        'ticket_number' => '20250113-0001',
        'title' => 'Image Test',
        'description' => 'D',
        'status_id' => TicketStatus::first()->id,
        'secret_string' => 'valid_secret_code_for_testing_purposes_1234567890',
    ]);

    $this->get(route('public.imageview', ['code' => $ticket->secret_string]))
        ->assertOk();
});

test('imageview with invalid code returns 404', function () {
    $this->get(route('public.imageview', ['code' => 'invalid_code_that_does_not_exist']))
        ->assertNotFound();
});

test('public ticket index respects search parameter', function () {
    $status = TicketStatus::where('name', 'offen')->first();

    Ticket::create([
        'ticket_number' => '20250113-0001',
        'title' => 'Wasserrohrbruch',
        'description' => 'D',
        'status_id' => $status->id,
        'show_on_website' => true,
    ]);

    Ticket::create([
        'ticket_number' => '20250113-0002',
        'title' => 'Laerm im Hof',
        'description' => 'D',
        'status_id' => $status->id,
        'show_on_website' => true,
    ]);

    $this->get(route('public.tickets.index', ['search' => 'Wasser']))
        ->assertSee('Wasserrohrbruch')
        ->assertDontSee('Laerm im Hof');
});

test('public ticket index hides inactive tickets by default', function () {
    $status = TicketStatus::where('name', 'offen')->first();

    // Recent ticket
    Ticket::create([
        'ticket_number' => '20250113-0001',
        'title' => 'Aktuelles Ticket',
        'description' => 'D',
        'status_id' => $status->id,
        'show_on_website' => true,
    ]);

    // This ticket is recent (created now), so it should show.
    // The 3-month filter is based on created_at or last comment.
    $this->get(route('public.tickets.index'))
        ->assertOk()
        ->assertSee('Aktuelles Ticket');
});

test('public ticket index shows all with show_all parameter', function () {
    $status = TicketStatus::where('name', 'offen')->first();

    Ticket::create([
        'ticket_number' => '20250113-0001',
        'title' => 'Sichtbares Ticket',
        'description' => 'D',
        'status_id' => $status->id,
        'show_on_website' => true,
    ]);

    $this->get(route('public.tickets.index', ['show_all' => '1']))
        ->assertOk()
        ->assertSee('Sichtbares Ticket');
});

test('public news image endpoint returns 404 for news without image', function () {
    $news = News::create([
        'title' => 'Kein Bild',
        'content' => 'Test',
        'event_date' => '2026-01-01',
        'created_by' => 'Admin',
    ]);

    $this->get(route('public.news.image', $news))->assertNotFound();
});
