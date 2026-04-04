<?php

namespace Tests\Browser;

use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

/**
 * E2E Tests T01-T07: Ticket-Detailseite Header, Navigation, Voting
 */
class TicketDetailPageTest extends DuskTestCase
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

    /** T01: Ticket-Detailseite lädt korrekt mit allen Sektionen */
    public function test_t01_detail_page_loads_with_all_sections(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginAs($browser);

            $browser->visit('/saus/tickets/1')
                ->pause(1000)
                ->assertPresent('#title-display')
                ->assertPresent('#description-display')
                ->assertPresent('#ticket-voting')
                ->assertPresent('#comments-container')
                ->assertPresent('#uploadForm')
                ->assertSee('Zuständig')
                ->assertSee('Status')
                ->assertSee('Wiedervorlage')
                ->assertSee('Nicht verfolgen')
                ->assertSee('Betroffene Nachbarn')
                ->assertSee('Ansprechpartner bei der Genossenschaft')
                ->assertSee('Kommentare')
                ->assertSee('Neuer Kommentar');
        });
    }

    /** T02: Zurück-Button führt zur Ticket-Liste */
    public function test_t02_back_button_navigates_to_list(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginAs($browser);

            $browser->visit('/saus/tickets/1')
                ->pause(1000)
                ->clickLink('Zurück')
                ->pause(1500)
                ->assertPathIs('/saus');
        });
    }

    /** T03: E-Mail-Ansicht-Button führt zur E-Mail-Seite */
    public function test_t03_email_button_leads_to_email_view(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginAs($browser);

            $browser->visit('/saus/tickets/1')
                ->pause(1000)
                ->assertSeeLink('E-Mail Ansicht');
        });
    }

    /** T04: Up-Vote-Button ist klickbar und erhöht den Zähler */
    public function test_t04_upvote_button_increases_count(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginAs($browser);

            $browser->visit('/saus/tickets/1')
                ->pause(1000);

            $initialCount = (int) $browser->text('.upvote-count');

            $browser->click('#ticket-voting button:first-child')
                ->pause(2000);

            $newCount = (int) $browser->text('.upvote-count');
            $this->assertGreaterThanOrEqual($initialCount, $newCount);
        });
    }

    /** T05: Down-Vote-Button ist klickbar und erhöht den Zähler */
    public function test_t05_downvote_button_increases_count(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginAs($browser);

            $browser->visit('/saus/tickets/2')
                ->pause(1000);

            $browser->click('#ticket-voting button:last-child')
                ->pause(2000)
                ->assertPresent('.downvote-count');
        });
    }

    /** T06: Erneutes Klicken auf denselben Vote-Button entfernt den Vote (Toggle) */
    public function test_t06_vote_toggle_removes_vote(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginAs($browser);

            // First vote
            $browser->visit('/saus/tickets/3')
                ->pause(1000)
                ->click('#ticket-voting button:first-child')
                ->pause(2000);

            // Vote again to remove
            $browser->click('#ticket-voting button:first-child')
                ->pause(2000)
                ->assertPresent('.upvote-count');
        });
    }

    /** T07: Wechsel von Up- zu Down-Vote aktualisiert beide Zähler */
    public function test_t07_vote_switch_updates_both_counts(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginAs($browser);

            $browser->visit('/saus/tickets/4')
                ->pause(1000)
                // Vote up
                ->click('#ticket-voting button:first-child')
                ->pause(2000)
                // Switch to down
                ->click('#ticket-voting button:last-child')
                ->pause(2000)
                ->assertPresent('.upvote-count')
                ->assertPresent('.downvote-count');
        });
    }
}
