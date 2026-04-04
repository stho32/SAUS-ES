<?php

use App\Models\ContactPerson;
use App\Models\Ticket;
use App\Models\TicketStatus;

beforeEach(function () {
    authenticateWithMasterLink();
    seedStatuses();
});

test('contact persons index page loads', function () {
    $this->get(route('contact-persons.index'))->assertOk();
});

test('contact persons index shows existing contacts', function () {
    ContactPerson::create([
        'name' => 'Hans Mueller',
        'email' => 'hans@example.com',
        'phone' => '0123456789',
    ]);

    $this->get(route('contact-persons.index'))
        ->assertOk()
        ->assertSee('Hans Mueller');
});

test('contact person can be created', function () {
    $this->postJson(route('contact-persons.store'), [
        'name' => 'Maria Schmidt',
        'email' => 'maria@example.com',
        'phone' => '0987654321',
        'contact_notes' => 'Erreichbar Mo-Fr',
        'responsibility_notes' => 'Hausverwaltung',
    ])->assertOk()->assertJson([
        'success' => true,
        'data' => ['name' => 'Maria Schmidt'],
    ]);

    $this->assertDatabaseHas('contact_persons', [
        'name' => 'Maria Schmidt',
        'email' => 'maria@example.com',
    ]);
});

test('contact person requires name', function () {
    $this->postJson(route('contact-persons.store'), [
        'email' => 'test@example.com',
    ])->assertStatus(422);
});

test('contact person validates email format', function () {
    $this->postJson(route('contact-persons.store'), [
        'name' => 'Test',
        'email' => 'not-an-email',
    ])->assertStatus(422);
});

test('contact person can be updated', function () {
    $contact = ContactPerson::create([
        'name' => 'Alt',
        'email' => 'alt@example.com',
    ]);

    $this->putJson(route('contact-persons.update', $contact), [
        'name' => 'Neu',
        'email' => 'neu@example.com',
    ])->assertOk()->assertJson(['success' => true]);

    $fresh = $contact->fresh();
    expect($fresh->name)->toBe('Neu');
    expect($fresh->email)->toBe('neu@example.com');
});

test('contact person active status can be toggled', function () {
    $contact = ContactPerson::create([
        'name' => 'Toggle Test',
        'is_active' => true,
    ]);

    expect($contact->is_active)->toBeTrue();

    $this->postJson(route('contact-persons.toggle', $contact))
        ->assertOk()
        ->assertJson([
            'success' => true,
            'data' => ['is_active' => false],
        ]);

    expect($contact->fresh()->is_active)->toBeFalse();
});

test('contact person can be toggled back to active', function () {
    $contact = ContactPerson::create([
        'name' => 'Toggle Back Test',
        'is_active' => false,
    ]);

    $this->postJson(route('contact-persons.toggle', $contact))
        ->assertOk()
        ->assertJson([
            'success' => true,
            'data' => ['is_active' => true],
        ]);

    expect($contact->fresh()->is_active)->toBeTrue();
});

test('contact person can be linked to ticket', function () {
    $ticket = Ticket::create([
        'ticket_number' => '20250113-0001',
        'title' => 'Link Test',
        'description' => 'D',
        'status_id' => TicketStatus::first()->id,
    ]);

    $contact = ContactPerson::create([
        'name' => 'Verknuepft',
    ]);

    $this->postJson(route('api.contact-persons.link', $ticket), [
        'contactPersonId' => $contact->id,
    ])->assertOk()->assertJson([
        'success' => true,
        'data' => ['name' => 'Verknuepft'],
    ]);

    expect($ticket->contactPersons()->count())->toBe(1);
});

test('linking contact person creates system comment', function () {
    $ticket = Ticket::create([
        'ticket_number' => '20250113-0001',
        'title' => 'System Comment Test',
        'description' => 'D',
        'status_id' => TicketStatus::first()->id,
    ]);

    $contact = ContactPerson::create([
        'name' => 'Herr Mueller',
    ]);

    $this->postJson(route('api.contact-persons.link', $ticket), [
        'contactPersonId' => $contact->id,
    ]);

    $this->assertDatabaseHas('comments', [
        'ticket_id' => $ticket->id,
        'username' => 'System',
    ]);
});

test('duplicate linking is rejected', function () {
    $ticket = Ticket::create([
        'ticket_number' => '20250113-0001',
        'title' => 'Duplicate Test',
        'description' => 'D',
        'status_id' => TicketStatus::first()->id,
    ]);

    $contact = ContactPerson::create([
        'name' => 'Doppelt',
    ]);

    $ticket->contactPersons()->attach($contact->id);

    $this->postJson(route('api.contact-persons.link', $ticket), [
        'contactPersonId' => $contact->id,
    ])->assertStatus(422)->assertJson(['success' => false]);
});

test('contact person can be unlinked from ticket', function () {
    $ticket = Ticket::create([
        'ticket_number' => '20250113-0001',
        'title' => 'Unlink Test',
        'description' => 'D',
        'status_id' => TicketStatus::first()->id,
    ]);

    $contact = ContactPerson::create([
        'name' => 'Entfernt',
    ]);

    $ticket->contactPersons()->attach($contact->id);

    $this->deleteJson(route('api.contact-persons.unlink', [
        'ticket' => $ticket,
        'contactPerson' => $contact,
    ]))->assertOk()->assertJson(['success' => true]);

    expect($ticket->contactPersons()->count())->toBe(0);
});

test('unlinking contact person creates system comment', function () {
    $ticket = Ticket::create([
        'ticket_number' => '20250113-0001',
        'title' => 'Unlink Comment Test',
        'description' => 'D',
        'status_id' => TicketStatus::first()->id,
    ]);

    $contact = ContactPerson::create([
        'name' => 'Frau Schmidt',
    ]);

    $ticket->contactPersons()->attach($contact->id);

    $this->deleteJson(route('api.contact-persons.unlink', [
        'ticket' => $ticket,
        'contactPerson' => $contact,
    ]));

    $this->assertDatabaseHas('comments', [
        'ticket_id' => $ticket->id,
        'username' => 'System',
    ]);
});

test('contact person can be created with minimal data', function () {
    $this->postJson(route('contact-persons.store'), [
        'name' => 'Minimal',
    ])->assertOk()->assertJson(['success' => true]);

    $this->assertDatabaseHas('contact_persons', [
        'name' => 'Minimal',
        'email' => null,
        'phone' => null,
    ]);
});

test('contact person partial update only changes provided fields', function () {
    $contact = ContactPerson::create([
        'name' => 'Original',
        'email' => 'original@example.com',
        'phone' => '111',
    ]);

    $this->putJson(route('contact-persons.update', $contact), [
        'phone' => '222',
    ])->assertOk();

    $fresh = $contact->fresh();
    expect($fresh->name)->toBe('Original');
    expect($fresh->email)->toBe('original@example.com');
    expect($fresh->phone)->toBe('222');
});
