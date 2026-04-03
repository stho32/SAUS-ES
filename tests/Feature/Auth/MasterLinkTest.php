<?php

use App\Models\MasterLink;

test('unauthenticated user is redirected to error page', function () {
    $this->get('/')->assertRedirect(route('error', ['type' => 'unauthorized']));
});

test('valid master code grants access via middleware', function () {
    MasterLink::create(['link_code' => 'valid_code', 'is_active' => true]);

    // When accessing a protected route with a valid master_code query param,
    // the middleware stores the code in the session and continues.
    // Without a username, the EnsureUsername middleware redirects to username form.
    $this->get('/?master_code=valid_code')
        ->assertRedirect(route('username.form'));
});

test('invalid master code is rejected', function () {
    $this->get('/?master_code=invalid_code')
        ->assertRedirect(route('error', ['type' => 'unauthorized']));
});

test('inactive master code is rejected', function () {
    MasterLink::create(['link_code' => 'inactive', 'is_active' => false]);

    $this->get('/?master_code=inactive')
        ->assertRedirect(route('error', ['type' => 'unauthorized']));
});

test('username form is shown for authenticated user without username', function () {
    MasterLink::create(['link_code' => 'test', 'is_active' => true]);

    $this->withSession(['master_code' => 'test'])
        ->get(route('username.form'))
        ->assertOk();
});

test('username can be set', function () {
    MasterLink::create(['link_code' => 'test', 'is_active' => true]);

    $this->withSession(['master_code' => 'test'])
        ->post(route('username.store'), ['username' => 'TestUser'])
        ->assertRedirect(route('tickets.index'));
});

test('short username is rejected', function () {
    MasterLink::create(['link_code' => 'test', 'is_active' => true]);

    $this->withSession(['master_code' => 'test'])
        ->post(route('username.store'), ['username' => 'A'])
        ->assertSessionHasErrors('username');
});

test('username with special characters is rejected', function () {
    MasterLink::create(['link_code' => 'test', 'is_active' => true]);

    $this->withSession(['master_code' => 'test'])
        ->post(route('username.store'), ['username' => '<script>'])
        ->assertSessionHasErrors('username');
});

test('empty username is rejected', function () {
    MasterLink::create(['link_code' => 'test', 'is_active' => true]);

    $this->withSession(['master_code' => 'test'])
        ->post(route('username.store'), ['username' => ''])
        ->assertSessionHasErrors('username');
});

test('logout clears session', function () {
    authenticateWithMasterLink();

    $this->get(route('logout'))
        ->assertOk();
});

test('error page shows unauthorized message', function () {
    $this->get(route('error', ['type' => 'unauthorized']))
        ->assertOk()
        ->assertSee('Zugangslink');
});

test('error page shows partner link message', function () {
    $this->get(route('error', ['type' => 'invalid_partner']))
        ->assertOk()
        ->assertSee('Partner-Link');
});

test('error page handles unknown type', function () {
    $this->get(route('error', ['type' => 'unknown']))
        ->assertOk()
        ->assertSee('unbekannter Fehler');
});

test('api endpoint returns 401 without auth', function () {
    seedStatuses();
    $ticket = \App\Models\Ticket::create([
        'ticket_number' => '20250113-0001',
        'title' => 'Test',
        'description' => 'Test',
        'status_id' => \App\Models\TicketStatus::first()->id,
    ]);

    $this->postJson(route('api.comments.store', $ticket))
        ->assertStatus(401);
});

test('master link last_used_at is updated on access', function () {
    $link = MasterLink::create(['link_code' => 'track_usage', 'is_active' => true]);

    expect($link->last_used_at)->toBeNull();

    $this->get('/?master_code=track_usage');

    expect($link->fresh()->last_used_at)->not->toBeNull();
});
