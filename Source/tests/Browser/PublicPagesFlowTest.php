<?php

namespace Tests\Browser;

use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class PublicPagesFlowTest extends DuskTestCase
{
    protected function publicPrefix(): string
    {
        return '/' . config('saus.public_route_prefix', 'public-information');
    }

    public function test_public_ticket_list_accessible_without_auth(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit($this->publicPrefix())
                ->pause(1000)
                ->assertSee('Aktuelle Vorgänge');
        });
    }

    public function test_public_news_list_accessible(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit($this->publicPrefix() . '/news')
                ->pause(1000)
                ->assertSee('Neuigkeiten');
        });
    }

    public function test_public_news_search_works(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit($this->publicPrefix() . '/news')
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
            $browser->visit($this->publicPrefix() . '/imageview/ungueltigercode12345')
                ->pause(1000)
                ->assertSee('404');
        });
    }

    public function test_public_pages_have_no_admin_links(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit($this->publicPrefix())
                ->pause(1000);

            // The public page must NOT contain any link to the admin area
            $pageSource = $browser->driver->getPageSource();
            $this->assertStringNotContainsString('SAUS-i', $pageSource, 'Public page should not mention SAUS-i admin tool');
            $this->assertStringNotContainsString('/tickets/', $pageSource, 'Public page should not link to admin ticket views');
            $this->assertStringNotContainsString('master_code', $pageSource, 'Public page should not expose master_code');
        });
    }

    public function test_robots_txt_blocks_admin(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/robots.txt')
                ->pause(500)
                ->assertSee('Disallow: /');
        });
    }
}
