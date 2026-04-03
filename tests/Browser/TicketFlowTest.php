<?php

namespace Tests\Browser;

use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class TicketFlowTest extends DuskTestCase
{
    protected function loginAs(Browser $browser, string $username = 'Tester'): void
    {
        $browser->visit('/?master_code=test_master_2025')
            ->pause(1000);

        if ($browser->element('input[name="username"]')) {
            $browser->type('username', $username)
                ->press('Weiter')
                ->pause(1500);
        }
    }

    public function test_ticket_index_shows_tickets(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginAs($browser);

            $browser->assertPathIs('/')
                ->assertSee('Ticket-Uebersicht')
                ->assertPresent('table');
        });
    }

    public function test_ticket_index_sort_works(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginAs($browser);

            // Test the sort dropdown which is always present
            $browser->visit('/')
                ->pause(500)
                ->select('sort', 'title')
                ->pause(1500)
                ->assertQueryStringHas('sort', 'title');
        });
    }

    public function test_ticket_index_search_works(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginAs($browser);

            $browser->visit('/')
                ->pause(500)
                ->type('search', 'Feuchtigkeitsschaden')
                ->keys('input[name="search"]', '{enter}')
                ->pause(1000)
                ->assertQueryStringHas('search', 'Feuchtigkeitsschaden');
        });
    }

    public function test_create_ticket_flow(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginAs($browser);

            $browser->visit('/tickets/create')
                ->pause(500)
                ->assertSee('Neues Ticket erstellen')
                ->type('title', 'E2E Test Ticket Dachschaden')
                ->type('description', 'Das Dach in Haus 7 hat einen Riss. Wasser tritt bei starkem Regen ein.')
                ->select('status_id', '1')
                ->press('Ticket erstellen')
                ->pause(2000)
                ->assertSee('E2E Test Ticket Dachschaden');
        });
    }

    public function test_ticket_detail_page_loads(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginAs($browser);

            // Visit first ticket directly
            $browser->visit('/tickets/1')
                ->pause(1000)
                ->assertPresent('h1');
        });
    }

    public function test_ticket_edit_page_loads(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginAs($browser);

            $browser->visit('/tickets/1/edit')
                ->pause(1000)
                ->assertSee('Ticket bearbeiten')
                ->assertPresent('#title');
        });
    }

    public function test_ticket_email_view_loads(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginAs($browser);

            $browser->visit('/tickets/1/email')
                ->pause(1000)
                ->assertSee('Betreff');
        });
    }
}
