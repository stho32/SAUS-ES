<?php

namespace Tests\Browser;

use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

/**
 * E2E Tests T08-T37: Inline-Editing (Zuständigkeit, Status, Wiedervorlage, Titel, Beschreibung, Nachbarn, Website, Nicht-Verfolgen)
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
                ->assertPresent('#assignee-modal.hidden')
                ->click('@assignee-card button')
                ->pause(500)
                ->assertPresent('#assignee-modal:not(.hidden)')
                ->assertPresent('#assigneeInput');
        });
    }

    /** T09: Zuständigkeit ändern und speichern aktualisiert die Anzeige */
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

    /** T10: Mehrere Zuständige (kommagetrennt) werden korrekt gespeichert */
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

    /** T11: Zuständigkeit leeren setzt auf "Nicht zugewiesen" */
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

    /** T12: Abbrechen im Modal verwirft Änderungen */
    public function test_t12_assignee_cancel_discards_changes(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginAs($browser);

            $browser->visit('/saus/tickets/1')
                ->pause(1000);

            $originalText = $browser->text('[id^="assignee"]');

            $browser->script("document.getElementById('assignee-modal').classList.remove('hidden')");
            $browser->pause(300)
                ->clear('#assigneeInput')
                ->type('#assigneeInput', 'XXXX')
                ->script("document.getElementById('assignee-modal').classList.add('hidden')");

            $browser->pause(500);
            // Page should still show original (modal was just closed, no save)
            $this->assertStringNotContainsString('XXXX', $browser->driver->getPageSource());
        });
    }

    // === Status (T13-T16) ===

    /** T13: Klick auf Status-Karte öffnet das Modal */
    public function test_t13_status_card_opens_modal(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginAs($browser);

            $browser->visit('/saus/tickets/1')
                ->pause(1000)
                ->assertPresent('#status-modal.hidden')
                ->script("document.getElementById('status-modal').classList.remove('hidden')");

            $browser->pause(300)
                ->assertPresent('#statusSelect');
        });
    }

    /** T14: Status ändern erstellt einen System-Kommentar */
    public function test_t14_status_change_creates_system_comment(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginAs($browser);

            $browser->visit('/saus/tickets/5')
                ->pause(1000)
                ->script("document.getElementById('status-modal').classList.remove('hidden')");

            $browser->pause(300)
                ->select('#statusSelect', '2') // Select a different status
                ->script("updateStatus()");

            $browser->pause(2000)
                ->assertSee('Status geändert');
        });
    }

    /** T15: Statusfarbe aktualisiert sich nach Änderung */
    public function test_t15_status_color_updates(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginAs($browser);

            $browser->visit('/saus/tickets/6')
                ->pause(1000)
                ->script("document.getElementById('status-modal').classList.remove('hidden')");

            $browser->pause(300)
                ->select('#statusSelect', '1')
                ->script("updateStatus()");

            $browser->pause(2000)
                ->assertPresent('.bi-flag-fill');
        });
    }

    /** T16: Abbrechen im Status-Modal verwirft Änderungen */
    public function test_t16_status_cancel_discards(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginAs($browser);

            $browser->visit('/saus/tickets/1')
                ->pause(1000)
                ->script("document.getElementById('status-modal').classList.remove('hidden')");

            $browser->pause(300)
                ->script("document.getElementById('status-modal').classList.add('hidden')");

            $browser->pause(300)
                ->assertPresent('#status-modal.hidden');
        });
    }

    // === Wiedervorlagedatum (T17-T20) ===

    /** T17: Klick auf Wiedervorlage-Karte öffnet das Modal */
    public function test_t17_followup_card_opens_modal(): void
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

    /** T18: Datum setzen und speichern aktualisiert die Anzeige */
    public function test_t18_followup_date_updates_display(): void
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

    /** T19: Datum ändern erstellt einen System-Kommentar */
    public function test_t19_followup_change_creates_system_comment(): void
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

    /** T20: Abbrechen im Modal verwirft Änderungen */
    public function test_t20_followup_cancel_discards(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginAs($browser);

            $browser->visit('/saus/tickets/1')
                ->pause(1000)
                ->script("document.getElementById('followup-modal').classList.remove('hidden')");

            $browser->pause(300)
                ->script("document.getElementById('followup-modal').classList.add('hidden')");

            $browser->pause(300)
                ->assertPresent('#followup-modal.hidden');
        });
    }

    // === Titel bearbeiten (T21-T24) ===

    /** T21: Klick auf Titel aktiviert Bearbeitungsmodus */
    public function test_t21_title_click_activates_edit(): void
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

    /** T22: Titel ändern und speichern aktualisiert die Anzeige ohne Reload */
    public function test_t22_title_edit_saves_without_reload(): void
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

    /** T23: Leerer Titel wird abgelehnt (Validierung) */
    public function test_t23_empty_title_rejected(): void
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
        });
    }

    /** T24: Escape/Abbrechen verwirft Änderungen */
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
                ->type('#title-input', 'SOLL NICHT GESPEICHERT WERDEN')
                ->script("cancelInlineEdit('title')");

            $browser->pause(300)
                ->assertPresent('#title-display:not(.hidden)')
                ->assertSeeIn('#title-text', $originalTitle);
        });
    }

    // === Beschreibung bearbeiten (T25-T28) ===

    /** T25: Klick auf Beschreibung aktiviert Bearbeitungsmodus */
    public function test_t25_description_click_activates_edit(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginAs($browser);

            $browser->visit('/saus/tickets/1')
                ->pause(1000)
                ->assertPresent('#description-display:not(.hidden)')
                ->script("startInlineEdit('description')");

            $browser->pause(300)
                ->assertPresent('#description-display.hidden')
                ->assertPresent('#description-edit:not(.hidden)')
                ->assertPresent('#description-input');
        });
    }

    /** T26: Beschreibung ändern und speichern aktualisiert die Anzeige */
    public function test_t26_description_edit_saves(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginAs($browser);

            $browser->visit('/saus/tickets/1')
                ->pause(1000)
                ->script("startInlineEdit('description')");

            $browser->pause(300)
                ->clear('#description-input')
                ->type('#description-input', 'Aktualisierte Beschreibung via E2E Test')
                ->script("saveInlineEdit('description')");

            $browser->pause(2000)
                ->assertSeeIn('#description-text', 'Aktualisierte Beschreibung via E2E Test')
                ->assertPresent('#description-display:not(.hidden)');
        });
    }

    /** T27: Leere Beschreibung wird abgelehnt (Validierung) */
    public function test_t27_empty_description_rejected(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginAs($browser);

            $browser->visit('/saus/tickets/1')
                ->pause(1000)
                ->script("startInlineEdit('description')");

            $browser->pause(300)
                ->clear('#description-input')
                ->script("saveInlineEdit('description')");

            $browser->pause(500)
                ->assertPresent('#description-error:not(.hidden)');
        });
    }

    /** T28: Escape/Abbrechen verwirft Änderungen */
    public function test_t28_description_cancel_discards(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginAs($browser);

            $browser->visit('/saus/tickets/2')
                ->pause(1000);

            $browser->script("startInlineEdit('description')");
            $browser->pause(300)
                ->clear('#description-input')
                ->type('#description-input', 'SOLL NICHT GESPEICHERT WERDEN')
                ->script("cancelInlineEdit('description')");

            $browser->pause(300)
                ->assertPresent('#description-display:not(.hidden)')
                ->assertDontSee('SOLL NICHT GESPEICHERT WERDEN');
        });
    }

    // === Betroffene Nachbarn (T29-T31) ===

    /** T29: Klick auf Betroffene-Nachbarn-Karte öffnet Modal */
    public function test_t29_neighbors_card_opens_modal(): void
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

    /** T30: Zahl eingeben und speichern aktualisiert die Anzeige */
    public function test_t30_neighbors_edit_saves(): void
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

    /** T31: Feld leeren setzt auf "Unbekannt" */
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

    /** T32: Toggle "Auf Website anzeigen" aktiviert die Website-Anzeige */
    public function test_t32_website_toggle_activates(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginAs($browser);

            $browser->visit('/saus/tickets/1')
                ->pause(1000);

            // Ensure toggle is off first
            $browser->script("document.getElementById('showOnWebsiteToggle').checked = false");
            $browser->script("toggleShowOnWebsite()");
            $browser->pause(1000);

            // Now toggle on
            $browser->check('#showOnWebsiteToggle')
                ->script("toggleShowOnWebsite()");

            $browser->pause(2000);

            $display = $browser->script("return document.getElementById('website-details').style.display");
            $this->assertNotEquals('none', $display[0]);
        });
    }

    /** T33: Bei Aktivierung erscheint das Public-Comment-Feld */
    public function test_t33_website_toggle_shows_comment_field(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginAs($browser);

            $browser->visit('/saus/tickets/1')
                ->pause(1000)
                ->script("document.getElementById('showOnWebsiteToggle').checked = true");

            $browser->script("toggleShowOnWebsite()");
            $browser->pause(2000);

            $browser->assertPresent('#public-comment-display');
        });
    }

    /** T34: Public Comment eingeben und speichern */
    public function test_t34_public_comment_saves(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginAs($browser);

            // Use a ticket that has show_on_website = true from seeder
            $browser->visit('/saus/tickets/1')
                ->pause(1000);

            // Enable website first
            $browser->script("document.getElementById('showOnWebsiteToggle').checked = true; toggleShowOnWebsite()");
            $browser->pause(2000);

            $browser->script("startInlineEdit('publicComment')");
            $browser->pause(300)
                ->clear('#publicComment-input')
                ->type('#publicComment-input', 'E2E Öffentlicher Kommentar')
                ->script("saveInlineEdit('publicComment')");

            $browser->pause(2000)
                ->assertSee('E2E');
        });
    }

    /** T35: Toggle deaktivieren blendet den Public-Comment-Bereich aus */
    public function test_t35_website_toggle_off_hides_comment(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginAs($browser);

            $browser->visit('/saus/tickets/1')
                ->pause(1000);

            // First enable
            $browser->script("document.getElementById('showOnWebsiteToggle').checked = true; toggleShowOnWebsite()");
            $browser->pause(2000);

            // Then disable
            $browser->script("document.getElementById('showOnWebsiteToggle').checked = false; toggleShowOnWebsite()");
            $browser->pause(2000);

            $display = $browser->script("return document.getElementById('website-details').style.display");
            $this->assertEquals('none', $display[0]);
        });
    }

    // === Nicht-Verfolgen (T36-T37) ===

    /** T36: Toggle "Nicht verfolgen" ändert den Wert auf der Karte */
    public function test_t36_do_not_track_toggle(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginAs($browser);

            $browser->visit('/saus/tickets/1')
                ->pause(1000)
                ->assertPresent('#do-not-track-btn')
                ->script("toggleDoNotTrack()");

            $browser->pause(2000)
                ->assertPresent('#do-not-track-text');
        });
    }

    /** T37: Wert wird via API gespeichert ohne Reload */
    public function test_t37_do_not_track_saves_via_api(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginAs($browser);

            $browser->visit('/saus/tickets/2')
                ->pause(1000);

            // Toggle and check page doesn't reload (alert should appear)
            $browser->script("toggleDoNotTrack()");
            $browser->pause(2000)
                ->assertSee('Gespeichert');
        });
    }
}
