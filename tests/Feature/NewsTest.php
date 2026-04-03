<?php

use App\Models\News;

beforeEach(function () {
    authenticateWithMasterLink();
    seedStatuses();
});

test('news index page loads', function () {
    $this->get(route('news.index'))->assertOk();
});

test('news index shows news articles', function () {
    News::create([
        'title' => 'Sommerfest 2026',
        'content' => 'Wir laden ein zum Sommerfest.',
        'event_date' => '2026-07-15',
        'created_by' => 'TestUser',
    ]);

    $this->get(route('news.index'))
        ->assertOk()
        ->assertSee('Sommerfest 2026');
});

test('news create page loads', function () {
    $this->get(route('news.create'))->assertOk();
});

test('news can be created via API', function () {
    $this->postJson(route('api.news.store'), [
        'title' => 'Neue Nachricht',
        'content' => 'Inhalt der Nachricht',
        'event_date' => '2026-08-01',
    ])->assertOk()->assertJson([
        'success' => true,
    ]);

    $this->assertDatabaseHas('news', [
        'title' => 'Neue Nachricht',
        'created_by' => 'TestUser',
    ]);
});

test('news requires title', function () {
    $this->postJson(route('api.news.store'), [
        'content' => 'Inhalt',
        'event_date' => '2026-08-01',
    ])->assertStatus(422);
});

test('news requires content', function () {
    $this->postJson(route('api.news.store'), [
        'title' => 'Titel',
        'event_date' => '2026-08-01',
    ])->assertStatus(422);
});

test('news requires event_date', function () {
    $this->postJson(route('api.news.store'), [
        'title' => 'Titel',
        'content' => 'Inhalt',
    ])->assertStatus(422);
});

test('news can be updated via API', function () {
    $news = News::create([
        'title' => 'Alt',
        'content' => 'Alter Inhalt',
        'event_date' => '2026-01-01',
        'created_by' => 'TestUser',
    ]);

    $this->putJson(route('api.news.update', $news), [
        'title' => 'Neu',
        'content' => 'Neuer Inhalt',
    ])->assertOk()->assertJson(['success' => true]);

    $fresh = $news->fresh();
    expect($fresh->title)->toBe('Neu');
    expect($fresh->content)->toBe('Neuer Inhalt');
});

test('news can be deleted via API', function () {
    $news = News::create([
        'title' => 'Zu loeschen',
        'content' => 'Wird geloescht',
        'event_date' => '2026-01-01',
        'created_by' => 'TestUser',
    ]);

    $this->deleteJson(route('api.news.destroy', $news))
        ->assertOk()
        ->assertJson(['success' => true]);

    $this->assertDatabaseMissing('news', ['id' => $news->id]);
});

test('news edit page loads', function () {
    $news = News::create([
        'title' => 'Edit Test',
        'content' => 'Inhalt',
        'event_date' => '2026-01-01',
        'created_by' => 'TestUser',
    ]);

    $this->get(route('news.edit', $news))->assertOk();
});

test('news image endpoint returns 404 when no image', function () {
    $news = News::create([
        'title' => 'Ohne Bild',
        'content' => 'Kein Bild',
        'event_date' => '2026-01-01',
        'created_by' => 'TestUser',
    ]);

    $this->get(route('api.news.image', $news))->assertNotFound();
});

test('news index searches by title', function () {
    News::create([
        'title' => 'Sommerfest',
        'content' => 'Party',
        'event_date' => '2026-07-15',
        'created_by' => 'TestUser',
    ]);

    News::create([
        'title' => 'Winterfest',
        'content' => 'Gluehwein',
        'event_date' => '2026-12-15',
        'created_by' => 'TestUser',
    ]);

    $this->get(route('news.index', ['search' => 'Sommer']))
        ->assertSee('Sommerfest')
        ->assertDontSee('Winterfest');
});

test('news creation stores created_by from session', function () {
    $this->postJson(route('api.news.store'), [
        'title' => 'Creator Test',
        'content' => 'Inhalt',
        'event_date' => '2026-01-01',
    ])->assertOk();

    $news = News::where('title', 'Creator Test')->first();
    expect($news->created_by)->toBe('TestUser');
});

test('news partial update only changes provided fields', function () {
    $news = News::create([
        'title' => 'Original Titel',
        'content' => 'Original Inhalt',
        'event_date' => '2026-01-01',
        'created_by' => 'TestUser',
    ]);

    $this->putJson(route('api.news.update', $news), [
        'title' => 'Neuer Titel',
    ])->assertOk();

    $fresh = $news->fresh();
    expect($fresh->title)->toBe('Neuer Titel');
    expect($fresh->content)->toBe('Original Inhalt');
});
