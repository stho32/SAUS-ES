<?php

use App\Models\ContactPerson;
use App\Models\Ticket;
use App\Models\TicketStatus;

beforeEach(function () {
    authenticateWithMasterLink();
    seedStatuses();

    $this->ticket = Ticket::create([
        'ticket_number' => '20250113-0001',
        'title' => 'Inline Edit Test',
        'description' => 'Originale Beschreibung',
        'status_id' => TicketStatus::where('name', 'offen')->first()->id,
        'assignee' => 'SH',
        'affected_neighbors' => 5,
        'do_not_track' => false,
        'show_on_website' => false,
        'public_comment' => null,
    ]);
});

// === Title Inline Edit ===

test('title can be updated via API', function () {
    $this->putJson(route('api.tickets.update', $this->ticket), [
        'title' => 'Neuer Titel',
    ])->assertOk()->assertJson(['success' => true]);

    expect($this->ticket->fresh()->title)->toBe('Neuer Titel');
});

test('empty title is rejected', function () {
    $this->putJson(route('api.tickets.update', $this->ticket), [
        'title' => '',
    ])->assertStatus(422);
});

// === Description Inline Edit ===

test('description can be updated via API', function () {
    $this->putJson(route('api.tickets.update', $this->ticket), [
        'description' => 'Neue Beschreibung mit Details',
    ])->assertOk()->assertJson(['success' => true]);

    expect($this->ticket->fresh()->description)->toBe('Neue Beschreibung mit Details');
});

test('empty description is rejected', function () {
    $this->putJson(route('api.tickets.update', $this->ticket), [
        'description' => '',
    ])->assertStatus(422);
});

// === Affected Neighbors ===

test('affected neighbors can be updated', function () {
    $this->putJson(route('api.tickets.update', $this->ticket), [
        'affectedNeighbors' => 12,
    ])->assertOk()->assertJson(['success' => true]);

    expect($this->ticket->fresh()->affected_neighbors)->toBe(12);
});

test('affected neighbors can be set to null', function () {
    $this->putJson(route('api.tickets.update', $this->ticket), [
        'affectedNeighbors' => null,
    ])->assertOk()->assertJson(['success' => true]);

    expect($this->ticket->fresh()->affected_neighbors)->toBeNull();
});

// === Do Not Track Toggle ===

test('do not track can be toggled on', function () {
    $this->putJson(route('api.tickets.update', $this->ticket), [
        'doNotTrack' => true,
    ])->assertOk()->assertJson(['success' => true]);

    expect($this->ticket->fresh()->do_not_track)->toBeTrue();
});

test('do not track can be toggled off', function () {
    $this->ticket->update(['do_not_track' => true]);

    $this->putJson(route('api.tickets.update', $this->ticket), [
        'doNotTrack' => false,
    ])->assertOk()->assertJson(['success' => true]);

    expect($this->ticket->fresh()->do_not_track)->toBeFalse();
});

// === Show on Website Toggle ===

test('show on website can be toggled on', function () {
    $this->putJson(route('api.tickets.update', $this->ticket), [
        'showOnWebsite' => true,
    ])->assertOk()->assertJson(['success' => true]);

    expect($this->ticket->fresh()->show_on_website)->toBeTrue();
});

test('public comment can be set when website is enabled', function () {
    $this->putJson(route('api.tickets.update', $this->ticket), [
        'showOnWebsite' => true,
        'publicComment' => 'Öffentlicher Kommentar',
    ])->assertOk()->assertJson(['success' => true]);

    $fresh = $this->ticket->fresh();
    expect($fresh->show_on_website)->toBeTrue();
    expect($fresh->public_comment)->toBe('Öffentlicher Kommentar');
});

// === Closed Ticket Protection (Bug 4) ===

test('closed ticket cannot have status updated via quick action', function () {
    $closedStatus = TicketStatus::where('name', 'gescheitert')->first();
    $this->ticket->update(['status_id' => $closedStatus->id]);

    $openStatus = TicketStatus::where('name', 'offen')->first();

    $this->postJson(route('api.tickets.assignee', $this->ticket), [
        'assignee' => 'MK',
    ])->assertStatus(403);
});

test('closed ticket cannot have assignee updated', function () {
    $closedStatus = TicketStatus::where('name', 'gescheitert')->first();
    $this->ticket->update(['status_id' => $closedStatus->id]);

    $this->postJson(route('api.tickets.assignee', $this->ticket), [
        'assignee' => 'MK',
    ])->assertStatus(403)->assertJson(['success' => false]);

    expect($this->ticket->fresh()->assignee)->toBe('SH');
});

test('closed ticket cannot have follow-up date updated', function () {
    $closedStatus = TicketStatus::where('name', 'gescheitert')->first();
    $this->ticket->update(['status_id' => $closedStatus->id]);

    $this->postJson(route('api.tickets.follow-up', $this->ticket), [
        'follow_up_date' => '2026-12-31',
    ])->assertStatus(403)->assertJson(['success' => false]);
});

test('archived ticket cannot have status changed', function () {
    $archivedStatus = TicketStatus::where('name', 'archiviert')->first();
    $this->ticket->update(['status_id' => $archivedStatus->id]);

    $openStatus = TicketStatus::where('name', 'offen')->first();

    $this->postJson(route('api.tickets.status', $this->ticket), [
        'status_id' => $openStatus->id,
    ])->assertStatus(403);
});

// === Contact Person Bugfix (Bug 1) ===

test('contact person can be linked with snake_case field name', function () {
    $contactPerson = ContactPerson::create([
        'name' => 'Frau Müller',
        'email' => 'mueller@example.com',
        'is_active' => true,
    ]);

    $this->postJson(route('api.contact-persons.link', $this->ticket), [
        'contact_person_id' => $contactPerson->id,
    ])->assertOk()->assertJson(['success' => true]);

    expect($this->ticket->contactPersons()->count())->toBe(1);
});

test('contact person link creates system comment', function () {
    $contactPerson = ContactPerson::create([
        'name' => 'Herr Weber',
        'email' => 'weber@example.com',
        'is_active' => true,
    ]);

    $this->postJson(route('api.contact-persons.link', $this->ticket), [
        'contact_person_id' => $contactPerson->id,
    ])->assertOk();

    $this->assertDatabaseHas('comments', [
        'ticket_id' => $this->ticket->id,
        'username' => 'System',
        'content' => 'Ansprechpartner hinzugefügt: Herr Weber',
    ]);
});

test('duplicate contact person link is rejected', function () {
    $contactPerson = ContactPerson::create([
        'name' => 'Frau Schmidt',
        'is_active' => true,
    ]);

    $this->ticket->contactPersons()->attach($contactPerson->id);

    $this->postJson(route('api.contact-persons.link', $this->ticket), [
        'contact_person_id' => $contactPerson->id,
    ])->assertStatus(422);
});

test('contact person can be unlinked', function () {
    $contactPerson = ContactPerson::create([
        'name' => 'Herr Braun',
        'is_active' => true,
    ]);

    $this->ticket->contactPersons()->attach($contactPerson->id);

    $this->deleteJson(route('api.contact-persons.unlink', [
        'ticket' => $this->ticket,
        'contactPerson' => $contactPerson,
    ]))->assertOk()->assertJson(['success' => true]);

    expect($this->ticket->contactPersons()->count())->toBe(0);
});

// === Comment Username Validation (Bug 2+3) ===

test('comment creation requires username in session', function () {
    // Remove username from session
    $this->withSession([
        'master_code' => 'test_master_link',
        'username' => null,
    ]);

    $this->postJson(route('api.comments.store', $this->ticket), [
        'content' => 'Test ohne Username',
    ])->assertStatus(401)->assertJson([
        'success' => false,
        'message' => 'Benutzername nicht gesetzt.',
    ]);
});

// === Show Page displays inline edit elements ===

test('show page displays inline edit elements', function () {
    $this->get(route('tickets.show', $this->ticket))
        ->assertOk()
        ->assertSee('title-display')
        ->assertSee('description-display')
        ->assertSee('do-not-track-btn')
        ->assertSee('neighbors-text')
        ->assertSee('showOnWebsiteToggle');
});
