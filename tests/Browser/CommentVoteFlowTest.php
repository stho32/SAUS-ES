<?php

namespace Tests\Browser;

use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class CommentVoteFlowTest extends DuskTestCase
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

    public function test_ticket_detail_shows_comment_form(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginAs($browser);

            $browser->visit('/tickets/1')
                ->pause(1000)
                ->assertPresent('#commentContent')
                ->assertSee('Neuer Kommentar');
        });
    }

    public function test_ticket_detail_shows_vote_buttons(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginAs($browser);

            $browser->visit('/tickets/1')
                ->pause(1000)
                ->assertPresent('#ticket-voting');
        });
    }

    public function test_ticket_detail_has_description(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginAs($browser);

            $browser->visit('/tickets/1')
                ->pause(1000)
                ->assertSee('Beschreibung');
        });
    }
}
