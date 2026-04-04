<?php

namespace Tests\Browser;

use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class NavigationFlowTest extends DuskTestCase
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

    public function test_all_navigation_links_work(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginAs($browser);

            // Wiedervorlage
            $browser->visit('/saus/follow-up')
                ->pause(1000)
                ->assertSee('Wiedervorlage');

            // Webseite
            $browser->visit('/saus/website-view')
                ->pause(1000)
                ->assertSee('Webseiten-Ansicht');

            // News
            $browser->visit('/saus/news')
                ->pause(1000)
                ->assertSee('News verwalten');

            // Statistik
            $browser->visit('/saus/statistics')
                ->pause(1000)
                ->assertSee('Statistik');

            // SAUS-News
            $browser->visit('/saus/saus-news')
                ->pause(1000)
                ->assertSee('SAUS-News');

            // Ansprechpartner
            $browser->visit('/saus/contact-persons')
                ->pause(1000)
                ->assertSee('Ansprechpartner');
        });
    }

    public function test_statistics_page_has_charts(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginAs($browser);

            $browser->visit('/saus/statistics')
                ->pause(1000)
                ->assertPresent('canvas');
        });
    }
}
