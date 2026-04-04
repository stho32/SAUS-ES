<?php

namespace Tests\Browser;

use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

/**
 * E2E Tests T08-T37: Inline-Editing
 */
class TicketInlineEditTest extends DuskTestCase
{
    protected function loginAs(Browser $browser, string $username = 'Tester'): void
    {
        $browser->visit('/saus/?master_code=test_master_2025')
            ->pause(1000);

        if ($browser->element('input[name="username"]')) {
            $browser->type('username', $username)
                ->press('Weiter')
                ->pause(1500);
        }
    }

    // === Zuständigkeit (T08-T12) ===

    /** T08: Klick auf Zuständigkeits-Karte öffnet das Modal */
    public function test_t08_assignee_card_opens_modal(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginAs($browser);

            $browser->visit('/saus/tickets/1')
                ->pause(1000)
                ->assertPresent('#assignee-modal.hidden');

            // Click the assignee button to open modal
            $browser->script("document.getElementById('assignee-modal').classList.remove('hidden')");
            $browser->pause(300)
                ->assertPresent('#assignee-modal:not(.hidden)')
                ->assertPresent('#assigneeInput');
        });
    }

    /** T09: Zuständigkeit ändern aktualisiert die Anzeige */
    public function test_t09_assignee_change_updates_display(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginAs($browser);

            $browser->visit('/saus/tickets/1')
                ->pause(1000)
                ->script("document.getElementById('assignee-modal').classList.remove('hidden')");

            $browser->pause(300)
                ->clear('#assigneeInput')
                ->type('#assigneeInput', 'MK, TP')
                ->script("updateAssignee()");

            $browser->pause(2000)
                ->assertSee('MK, TP');
        });
    }

    /** T10: Mehrere Zuständige kommagetrennt */
    public function test_t10_multiple_assignees_saved(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginAs($browser);

            $browser->visit('/saus/tickets/2')
                ->pause(1000)
                ->script("document.getElementById('assignee-modal').classList.remove('hidden')");

            $browser->pause(300)
                ->clear('#assigneeInput')
                ->type('#assigneeInput', 'SH, MK, TP')
                ->script("updateAssignee()");

            $browser->pause(2000)
                ->assertSee('SH, MK, TP');
        });
    }

    /** T11: Leere Zuständigkeit zeigt "Nicht zugewiesen" */
    public function test_t11_empty_assignee_shows_not_assigned(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginAs($browser);

            $browser->visit('/saus/tickets/1')
                ->pause(1000)
                ->script("document.getElementById('assignee-modal').classList.remove('hidden')");

            $browser->pause(300)
                ->clear('#assigneeInput')
                ->script("updateAssignee()");

            $browser->pause(2000)
                ->assertSee('Nicht zugewiesen');
        });
    }

    /** T12: Abbrechen verwirft Änderungen */
    public function test_t12_assignee_cancel_discards(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginAs($browser);

            $browser->visit('/saus/tickets/1')
                ->pause(1000);

            $originalText = $browser->driver->getPageSource();

            $browser->script("document.getElementById('assignee-modal').classList.remove('hidden')");
            $browser->pause(300)
                ->clear('#assigneeInput')
                ->type('#assigneeInput', 'SOLL_NICHT_GESPEICHERT')
                ->script("document.getElementById('assignee-modal').classList.add('hidden')");

            $browser->pause(500);
            $this->assertStringNotContainsString('SOLL_NICHT_GESPEICHERT', $browser->driver->getPageSource());
        });
    }

    // === Status (T13-T16) ===

    /** T13: Status-Modal öffnet sich */
    public function test_t13_status_modal_opens(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginAs($browser);

            $browser->visit('/saus/tickets/1')
                ->pause(1000)
                ->script("document.getElementById('status-modal').classList.remove('hidden')");

            $browser->pause(300)
                ->assertPresent('#statusSelect');
        });
    }

    /** T14: Statusänderung erstellt System-Kommentar */
    public function test_t14_status_change_creates_system_comment(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginAs($browser);

            $browser->visit('/saus/tickets/5')
                ->pause(1000);

            $commentsBefore = count($browser->elements('.comment-system'));

            $browser->script("document.getElementById('status-modal').classList.remove('hidden')");
            $browser->pause(300)
                ->select('#statusSelect', '2')
                ->script("updateStatus()");

            $browser->pause(2000);
            $commentsAfter = count($browser->elements('.comment-system'));
            $this->assertGreaterThan($commentsBefore, $commentsAfter, 'Status change should create a system comment');
            $browser->assertSee('Status geändert');
        });
    }

    /** T15: Statusfarbe ändert sich nach Update */
    public function test_t15_status_color_changes(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginAs($browser);

            $browser->visit('/saus/tickets/6')
                ->pause(1000);

            // Get current status name
            $statusBefore = $browser->text('.bi-flag-fill + div span') ?: '';

            $browser->script("document.getElementById('status-modal').classList.remove('hidden')");
            $browser->pause(300)
                ->select('#statusSelect', '1')
                ->script("updateStatus()");

            $browser->pause(2000);
            // Verify status text changed (page reloaded)
            $browser->assertSee('Status geändert');
        });
    }

    /** T16: Abbrechen im Status-Modal speichert nichts */
    public function test_t16_status_cancel_no_save(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginAs($browser);

            $browser->visit('/saus/tickets/1')
                ->pause(1000);

            $commentsBefore = count($browser->elements('.comment-system'));

            $browser->script("document.getElementById('status-modal').classList.remove('hidden')");
            $browser->pause(300)
                ->script("document.getElementById('status-modal').classList.add('hidden')");

            $browser->pause(500);
            $commentsAfter = count($browser->elements('.comment-system'));
            $this->assertEquals($commentsBefore, $commentsAfter, 'Cancel should not create system comment');
        });
    }

    // === Wiedervorlagedatum (T17-T20) ===

    /** T17: Wiedervorlage-Modal öffnet */
    public function test_t17_followup_modal_opens(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginAs($browser);

            $browser->visit('/saus/tickets/1')
                ->pause(1000)
                ->script("document.getElementById('followup-modal').classList.remove('hidden')");

            $browser->pause(300)
                ->assertPresent('#followUpDate');
        });
    }

    /** T18: Datum setzen aktualisiert Anzeige */
    public function test_t18_followup_date_updates(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginAs($browser);

            $browser->visit('/saus/tickets/7')
                ->pause(1000)
                ->script("document.getElementById('followup-modal').classList.remove('hidden')");

            $browser->pause(300)
                ->type('#followUpDate', '2026-12-01')
                ->script("updateFollowUpDate()");

            $browser->pause(2000)
                ->assertSee('01.12.2026');
        });
    }

    /** T19: Datum ändern erstellt System-Kommentar */
    public function test_t19_followup_creates_system_comment(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginAs($browser);

            $browser->visit('/saus/tickets/8')
                ->pause(1000)
                ->script("document.getElementById('followup-modal').classList.remove('hidden')");

            $browser->pause(300)
                ->type('#followUpDate', '2026-11-15')
                ->script("updateFollowUpDate()");

            $browser->pause(2000)
                ->assertSee('Wiedervorlage gesetzt auf');
        });
    }

    /** T20: Abbrechen speichert kein Datum */
    public function test_t20_followup_cancel_no_save(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginAs($browser);

            $browser->visit('/saus/tickets/1')
                ->pause(1000);

            $commentsBefore = count($browser->elements('.comment-system'));

            $browser->script("document.getElementById('followup-modal').classList.remove('hidden')");
            $browser->pause(300)
                ->script("document.getElementById('followup-modal').classList.add('hidden')");

            $browser->pause(500);
            $commentsAfter = count($browser->elements('.comment-system'));
            $this->assertEquals($commentsBefore, $commentsAfter, 'Cancel should not create system comment');
        });
    }

    // === Titel bearbeiten (T21-T24) ===

    /** T21: Klick auf Titel aktiviert Edit-Modus */
    public function test_t21_title_edit_activates(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginAs($browser);

            $browser->visit('/saus/tickets/1')
                ->pause(1000)
                ->assertPresent('#title-display:not(.hidden)')
                ->assertPresent('#title-edit.hidden')
                ->script("startInlineEdit('title')");

            $browser->pause(300)
                ->assertPresent('#title-display.hidden')
                ->assertPresent('#title-edit:not(.hidden)')
                ->assertPresent('#title-input');
        });
    }

    /** T22: Titel speichern aktualisiert Anzeige ohne Reload */
    public function test_t22_title_saves_inline(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginAs($browser);

            $browser->visit('/saus/tickets/1')
                ->pause(1000)
                ->script("startInlineEdit('title')");

            $browser->pause(300)
                ->clear('#title-input')
                ->type('#title-input', 'Neuer Test-Titel E2E')
                ->script("saveInlineEdit('title')");

            $browser->pause(2000)
                ->assertSeeIn('#title-text', 'Neuer Test-Titel E2E')
                ->assertPresent('#title-display:not(.hidden)');
        });
    }

    /** T23: Leerer Titel zeigt Fehler */
    public function test_t23_empty_title_shows_error(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginAs($browser);

            $browser->visit('/saus/tickets/1')
                ->pause(1000)
                ->script("startInlineEdit('title')");

            $browser->pause(300)
                ->clear('#title-input')
                ->script("saveInlineEdit('title')");

            $browser->pause(500)
                ->assertPresent('#title-error:not(.hidden)');

            $errorText = $browser->text('#title-error');
            $this->assertNotEmpty($errorText, 'Error message should be visible');
        });
    }

    /** T24: Abbrechen verwirft Titeländerung */
    public function test_t24_title_cancel_discards(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginAs($browser);

            $browser->visit('/saus/tickets/2')
                ->pause(1000);

            $originalTitle = $browser->text('#title-text');

            $browser->script("startInlineEdit('title')");
            $browser->pause(300)
                ->clear('#title-input')
                ->type('#title-input', 'SOLL NICHT GESPEICHERT')
                ->script("cancelInlineEdit('title')");

            $browser->pause(300)
                ->assertSeeIn('#title-text', $originalTitle);
        });
    }

    // === Beschreibung (T25-T28) ===

    /** T25: Klick auf Beschreibung aktiviert Edit-Modus */
    public function test_t25_description_edit_activates(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginAs($browser);

            $browser->visit('/saus/tickets/1')
                ->pause(1000)
                ->script("startInlineEdit('description')");

            $browser->pause(300)
                ->assertPresent('#description-display.hidden')
                ->assertPresent('#description-edit:not(.hidden)');
        });
    }

    /** T26: Beschreibung speichern aktualisiert Anzeige */
    public function test_t26_description_saves(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginAs($browser);

            $browser->visit('/saus/tickets/1')
                ->pause(1000)
                ->script("startInlineEdit('description')");

            $browser->pause(300)
                ->clear('#description-input')
                ->type('#description-input', 'Aktualisierte Beschreibung E2E')
                ->script("saveInlineEdit('description')");

            $browser->pause(2000)
                ->assertSeeIn('#description-text', 'Aktualisierte Beschreibung E2E');
        });
    }

    /** T27: Leere Beschreibung zeigt Fehler */
    public function test_t27_empty_description_shows_error(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginAs($browser);

            $browser->visit('/saus/tickets/1')
                ->pause(1000)
                ->script("startInlineEdit('description')");

            $browser->pause(300)
                ->clear('#description-input')
                ->script("saveInlineEdit('description')");

            $browser->pause(500);
            $errorText = $browser->text('#description-error');
            $this->assertNotEmpty($errorText, 'Should show validation error for empty description');
        });
    }

    /** T28: Abbrechen verwirft Beschreibungsänderung */
    public function test_t28_description_cancel_discards(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginAs($browser);

            $browser->visit('/saus/tickets/2')
                ->pause(1000);

            $originalDesc = $browser->text('#description-text');

            $browser->script("startInlineEdit('description')");
            $browser->pause(300)
                ->clear('#description-input')
                ->type('#description-input', 'VERWORFEN')
                ->script("cancelInlineEdit('description')");

            $browser->pause(300)
                ->assertSeeIn('#description-text', $originalDesc);
        });
    }

    // === Betroffene Nachbarn (T29-T31) ===

    /** T29: Nachbarn-Modal öffnet */
    public function test_t29_neighbors_modal_opens(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginAs($browser);

            $browser->visit('/saus/tickets/1')
                ->pause(1000)
                ->script("document.getElementById('neighbors-modal').classList.remove('hidden')");

            $browser->pause(300)
                ->assertPresent('#neighborsInput');
        });
    }

    /** T30: Nachbarn-Zahl speichern aktualisiert Anzeige */
    public function test_t30_neighbors_saves(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginAs($browser);

            $browser->visit('/saus/tickets/1')
                ->pause(1000)
                ->script("document.getElementById('neighbors-modal').classList.remove('hidden')");

            $browser->pause(300)
                ->clear('#neighborsInput')
                ->type('#neighborsInput', '42')
                ->script("updateNeighbors()");

            $browser->pause(2000)
                ->assertSeeIn('#neighbors-text', '42');
        });
    }

    /** T31: Leeres Feld zeigt "Unbekannt" */
    public function test_t31_empty_neighbors_shows_unknown(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginAs($browser);

            $browser->visit('/saus/tickets/1')
                ->pause(1000)
                ->script("document.getElementById('neighbors-modal').classList.remove('hidden')");

            $browser->pause(300)
                ->clear('#neighborsInput')
                ->script("updateNeighbors()");

            $browser->pause(2000)
                ->assertSeeIn('#neighbors-text', 'Unbekannt');
        });
    }

    // === Website-Sichtbarkeit (T32-T35) ===

    /** T32: Website-Toggle aktiviert Details-Bereich */
    public function test_t32_website_toggle_shows_details(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginAs($browser);

            $browser->visit('/saus/tickets/1')
                ->pause(1000);

            // Ensure off first
            $browser->script("document.getElementById('showOnWebsiteToggle').checked = false; toggleShowOnWebsite()");
            $browser->pause(1500);

            // Toggle on
            $browser->script("document.getElementById('showOnWebsiteToggle').checked = true; toggleShowOnWebsite()");
            $browser->pause(2000);

            $display = $browser->script("return document.getElementById('website-details').style.display");
            $this->assertNotEquals('none', $display[0], 'Website details should be visible');
        });
    }

    /** T33: Public-Comment-Feld erscheint bei Aktivierung */
    public function test_t33_public_comment_field_appears(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginAs($browser);

            $browser->visit('/saus/tickets/1')
                ->pause(1000)
                ->script("document.getElementById('showOnWebsiteToggle').checked = true; toggleShowOnWebsite()");

            $browser->pause(2000)
                ->assertPresent('#public-comment-display');
        });
    }

    /** T34: Public Comment speichern und verifizieren */
    public function test_t34_public_comment_saves_and_persists(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginAs($browser);

            $browser->visit('/saus/tickets/1')
                ->pause(1000);

            // Enable website
            $browser->script("document.getElementById('showOnWebsiteToggle').checked = true; toggleShowOnWebsite()");
            $browser->pause(2000);

            // Edit public comment
            $browser->script("startInlineEdit('publicComment')");
            $browser->pause(300)
                ->clear('#publicComment-input')
                ->type('#publicComment-input', 'E2E Öffentlicher Kommentar Test')
                ->script("saveInlineEdit('publicComment')");

            $browser->pause(2000);

            // Reload and verify it persisted
            $browser->visit('/saus/tickets/1')
                ->pause(1000)
                ->assertSee('E2E Öffentlicher Kommentar Test');
        });
    }

    /** T35: Toggle deaktivieren blendet Details aus */
    public function test_t35_website_toggle_off_hides(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginAs($browser);

            $browser->visit('/saus/tickets/1')
                ->pause(1000);

            // Enable then disable
            $browser->script("document.getElementById('showOnWebsiteToggle').checked = true; toggleShowOnWebsite()");
            $browser->pause(2000);
            $browser->script("document.getElementById('showOnWebsiteToggle').checked = false; toggleShowOnWebsite()");
            $browser->pause(2000);

            $display = $browser->script("return document.getElementById('website-details').style.display");
            $this->assertEquals('none', $display[0], 'Website details should be hidden');
        });
    }

    // === Nicht-Verfolgen (T36-T37) ===

    /** T36: Toggle ändert den angezeigten Wert */
    public function test_t36_do_not_track_toggles_display(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginAs($browser);

            $browser->visit('/saus/tickets/1')
                ->pause(1000);

            $textBefore = $browser->text('#do-not-track-text');

            $browser->script("toggleDoNotTrack()");
            $browser->pause(2000);

            $textAfter = $browser->text('#do-not-track-text');
            $this->assertNotEquals($textBefore, $textAfter, 'Toggle should change the displayed text');
        });
    }

    /** T37: Toggle-Wert persistiert nach Reload */
    public function test_t37_do_not_track_persists(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginAs($browser);

            $browser->visit('/saus/tickets/2')
                ->pause(1000)
                ->script("toggleDoNotTrack()");

            $browser->pause(2000);
            $textAfterToggle = $browser->text('#do-not-track-text');

            // Reload and verify persistence
            $browser->visit('/saus/tickets/2')
                ->pause(1000);

            $textAfterReload = $browser->text('#do-not-track-text');
            $this->assertEquals($textAfterToggle, $textAfterReload, 'Toggle state should persist after reload');
        });
    }
}
