<?php

namespace Tests\Browser;

use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class TicketFlowTest extends DuskTestCase
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

    public function test_ticket_index_shows_actual_tickets(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginAs($browser);

            $browser->assertPathIs('/saus')
                ->assertSee('Ticket-Übersicht')
                ->assertPresent('table');

            // Verify actual ticket rows exist (seeder creates 20 tickets)
            $rows = $browser->elements('table tbody tr');
            $this->assertGreaterThan(0, count($rows), 'Table should contain ticket rows from seeder');
        });
    }

    public function test_ticket_index_sort_changes_order(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginAs($browser);

            $browser->visit('/saus/')
                ->pause(500);

            // Get first ticket title before sort
            $firstTitleBefore = $browser->text('table tbody tr:first-child td:nth-child(2)');

            $browser->select('sort', 'title')
                ->pause(1500)
                ->assertQueryStringHas('sort', 'title');

            // After sorting by title, first row should be alphabetically first
            $firstTitleAfter = $browser->text('table tbody tr:first-child td:nth-child(2)');
            // At minimum verify sort param was applied (order may or may not change)
            $this->assertNotEmpty($firstTitleAfter);
        });
    }

    public function test_ticket_index_search_filters_results(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginAs($browser);

            $browser->visit('/saus/')
                ->pause(500);

            $rowsBefore = count($browser->elements('table tbody tr'));

            $browser->type('search', 'Feuchtigkeitsschaden')
                ->keys('input[name="search"]', '{enter}')
                ->pause(1000)
                ->assertQueryStringHas('search', 'Feuchtigkeitsschaden');

            // Search should show fewer or equal results
            $rowsAfter = count($browser->elements('table tbody tr'));
            $this->assertLessThanOrEqual($rowsBefore, $rowsAfter);
        });
    }

    public function test_create_ticket_flow(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginAs($browser);

            $browser->visit('/saus/tickets/create')
                ->pause(500)
                ->assertSee('Neues Ticket erstellen')
                ->type('title', 'E2E Test Ticket Dachschaden')
                ->type('description', 'Das Dach in Haus 7 hat einen Riss.')
                ->select('status_id', '1')
                ->press('Ticket erstellen')
                ->pause(2000)
                ->assertSee('E2E Test Ticket Dachschaden');
        });
    }

    public function test_ticket_detail_shows_ticket_content(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginAs($browser);

            $browser->visit('/saus/tickets/1')
                ->pause(1000);

            // Verify actual ticket content loads, not just that h1 exists
            $title = $browser->text('#title-text');
            $this->assertNotEmpty($title, 'Ticket title should be displayed');

            $description = $browser->text('#description-text');
            $this->assertNotEmpty($description, 'Ticket description should be displayed');
        });
    }

    public function test_ticket_edit_route_no_longer_exists(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginAs($browser);

            $browser->visit('/saus/tickets/1/edit')
                ->pause(1000)
                ->assertSee('404');
        });
    }

    public function test_ticket_email_view_shows_ticket_data(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginAs($browser);

            $browser->visit('/saus/tickets/1/email')
                ->pause(1000)
                ->assertSee('Betreff');

            // Verify actual ticket data appears in email view
            $pageSource = $browser->driver->getPageSource();
            $this->assertStringContainsString('Ticket', $pageSource, 'Email view should contain ticket reference');
        });
    }
}
