<?php

namespace Tests\Browser;

use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class PublicPagesFlowTest extends DuskTestCase
{
    public function test_public_ticket_list_accessible_without_auth(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/public')
                ->pause(1000)
                ->assertSee('Aktuelle Vorgaenge');
        });
    }

    public function test_public_news_list_accessible(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/public/news')
                ->pause(1000)
                ->assertSee('Neuigkeiten');
        });
    }

    public function test_public_news_search_works(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/public/news')
                ->pause(500)
                ->type('search', 'Sommerfest')
                ->keys('input[name="search"]', '{enter}')
                ->pause(1000)
                ->assertQueryStringHas('search', 'Sommerfest');
        });
    }

    public function test_public_imageview_invalid_code(): void
    {
        $this->browse(function (Browser $browser) {
            // Invalid code returns 404 abort
            $browser->visit('/public/imageview/ungueltigercode12345')
                ->pause(1000)
                ->assertSee('404');
        });
    }
}
