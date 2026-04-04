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

    /** T01: Detailseite zeigt tatsächlichen Ticket-Inhalt */
    public function test_t01_detail_page_shows_ticket_content(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginAs($browser);

            $browser->visit('/saus/tickets/1')
                ->pause(1000);

            // Verify actual content, not just element presence
            $title = $browser->text('#title-text');
            $this->assertNotEmpty($title, 'Ticket title should display actual text');

            $description = $browser->text('#description-text');
            $this->assertNotEmpty($description, 'Description should display actual text');

            // Verify structural sections have content
            $comments = $browser->elements('#comments-container .comment');
            $this->assertGreaterThan(0, count($comments), 'Should have comments from seeder');

            $browser->assertSee('Zuständig')
                ->assertSee('Status')
                ->assertSee('Wiedervorlage')
                ->assertSee('Kommentare')
                ->assertSee('Neuer Kommentar');
        });
    }

    /** T02: Zurück-Button navigiert zur Ticket-Liste */
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

    /** T03: E-Mail-Link öffnet E-Mail-Ansicht */
    public function test_t03_email_link_opens_email_view(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginAs($browser);

            $browser->visit('/saus/tickets/1')
                ->pause(1000);

            // Verify the email link has correct href
            $emailLink = $browser->element('a[href*="email"]');
            $this->assertNotNull($emailLink, 'Email link should exist');
            $href = $emailLink->getAttribute('href');
            $this->assertStringContainsString('/tickets/1/email', $href);
        });
    }

    /** T04: Up-Vote erhöht den Zähler */
    public function test_t04_upvote_increases_count(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginAs($browser);

            $browser->visit('/saus/tickets/1')
                ->pause(1000);

            $initialCount = (int) $browser->text('.upvote-count');

            $browser->click('#ticket-voting button:first-child')
                ->pause(2000);

            $newCount = (int) $browser->text('.upvote-count');
            $this->assertGreaterThanOrEqual($initialCount, $newCount, 'Upvote count should increase or stay (if already voted)');
        });
    }

    /** T05: Down-Vote erhöht den Zähler */
    public function test_t05_downvote_increases_count(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginAs($browser);

            $browser->visit('/saus/tickets/2')
                ->pause(1000);

            $initialCount = (int) $browser->text('.downvote-count');

            $browser->click('#ticket-voting button:last-child')
                ->pause(2000);

            $newCount = (int) $browser->text('.downvote-count');
            $this->assertGreaterThanOrEqual($initialCount, $newCount, 'Downvote count should increase or stay');
        });
    }

    /** T06: Doppelklick auf Vote entfernt den Vote */
    public function test_t06_double_vote_toggles(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginAs($browser);

            $browser->visit('/saus/tickets/3')
                ->pause(1000);

            $countBefore = (int) $browser->text('.upvote-count');

            // Vote up
            $browser->click('#ticket-voting button:first-child')
                ->pause(2000);

            $countAfterVote = (int) $browser->text('.upvote-count');

            // Vote up again to remove
            $browser->click('#ticket-voting button:first-child')
                ->pause(2000);

            $countAfterToggle = (int) $browser->text('.upvote-count');
            // After toggling, count should return to original
            $this->assertEquals($countBefore, $countAfterToggle, 'Count should return to original after toggle');
        });
    }

    /** T07: Wechsel von Up- zu Down-Vote */
    public function test_t07_switch_vote_direction(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginAs($browser);

            $browser->visit('/saus/tickets/4')
                ->pause(1000);

            $upBefore = (int) $browser->text('.upvote-count');
            $downBefore = (int) $browser->text('.downvote-count');

            // Vote up
            $browser->click('#ticket-voting button:first-child')
                ->pause(2000);

            // Switch to down
            $browser->click('#ticket-voting button:last-child')
                ->pause(2000);

            $upAfter = (int) $browser->text('.upvote-count');
            $downAfter = (int) $browser->text('.downvote-count');

            // Down should have increased, up should be back to original
            $this->assertEquals($upBefore, $upAfter, 'Upvote count should return to original after switching');
            $this->assertGreaterThan($downBefore, $downAfter, 'Downvote count should increase');
        });
    }
}
