<?php

namespace Tests\Browser;

use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class AuthFlowTest extends DuskTestCase
{
    public function test_unauthenticated_user_sees_error_page(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/')
                ->pause(1000)
                ->assertPathIs('/error')
                ->assertSee('Zugangslink');
        });
    }

    public function test_valid_master_code_redirects_to_username_form(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/?master_code=test_master_2025')
                ->pause(1000)
                ->assertPathIs('/username')
                ->assertSee('Willkommen');
        });
    }

    public function test_full_login_flow(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/?master_code=test_master_2025')
                ->pause(1000)
                ->assertPathIs('/username')
                ->type('username', 'Tester')
                ->press('Weiter')
                ->pause(1500)
                ->assertPathIs('/')
                ->assertSee('Tester');
        });
    }

    public function test_logout_clears_session(): void
    {
        $this->browse(function (Browser $browser) {
            // Login first (may already be logged in from previous test)
            $browser->visit('/?master_code=test_master_2025')
                ->pause(1000);

            if ($browser->element('input[name="username"]')) {
                $browser->type('username', 'LogoutTest')
                    ->press('Weiter')
                    ->pause(1500);
            }

            $browser->assertPathIs('/');

            // Logout
            $browser->visit('/logout')
                ->pause(500)
                ->assertSee('Wiedersehen');

            // Verify session cleared
            $browser->visit('/')
                ->pause(1000)
                ->assertPathIs('/error');
        });
    }

    public function test_invalid_master_code_shows_error(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/?master_code=invalid_code_12345')
                ->pause(1000)
                ->assertPathIs('/error')
                ->assertSee('Zugangslink');
        });
    }
}
