<?php

namespace Tests\Browser;

use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class NewsFlowTest extends DuskTestCase
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

    public function test_news_index_loads(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginAs($browser);

            $browser->visit('/saus/news')
                ->pause(1000)
                ->assertSee('News verwalten');
        });
    }

    public function test_news_create_page_loads(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginAs($browser);

            $browser->visit('/saus/news/create')
                ->pause(1000)
                ->assertSee('News erstellen')
                ->assertPresent('#title')
                ->assertPresent('#content')
                ->assertPresent('#eventDate');
        });
    }

    public function test_news_index_shows_table_or_empty_state(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginAs($browser);

            $browser->visit('/saus/news')
                ->pause(1000)
                ->assertSee('News-Artikel');
        });
    }
}
