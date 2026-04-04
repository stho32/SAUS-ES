<?php

namespace Tests\Browser;

use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

/**
 * E2E Tests T78-T80: Bugfix-Verifikation
 */
class TicketBugfixVerificationTest extends DuskTestCase
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

    /** T78: Ansprechpartner hinzufuegen funktioniert (Bug 1: Feldname-Fix) */
    public function test_t78_contact_person_link_works(): void
    {
        $ticket = $this->createTestTicket(['title' => 'Bugfix T78 Ansprechpartner', 'description' => 'Test fuer Ansprechpartner-Verknuepfung']);

        $this->browse(function (Browser $browser) use ($ticket) {
            $this->loginAs($browser);

            $browser->visit('/saus/tickets/' . $ticket->id)
                ->pause(1000);

            $contactsBefore = count($browser->elements('[id^="cp-"]'));

            $browser->script("document.getElementById('add-contact-modal').classList.remove('hidden')");
            $browser->pause(300);

            $options = $browser->elements('#contactPersonSelect option');
            $this->assertGreaterThan(1, count($options), 'Should have contact persons available');

            $browser->script("var sel = document.getElementById('contactPersonSelect'); sel.value = sel.options[1].value;");
            $browser->script("addContactPerson()");

            $browser->pause(1000)
                ->visit('/saus/tickets/' . $ticket->id)
                ->pause(1500);

            // Verify contact was actually added (not a validation error)
            $contactsAfter = count($browser->elements('[id^="cp-"]'));
            $this->assertGreaterThan($contactsBefore, $contactsAfter, 'Contact person should be linked after fix');
        });
    }

    /** T79: Kommentare haben korrekten Benutzernamen (Bug 2+3) */
    public function test_t79_comments_have_correct_username(): void
    {
        $ticket = $this->createTestTicket(['title' => 'Bugfix T79 Username', 'description' => 'Test fuer korrekten Benutzernamen in Kommentaren']);

        $this->browse(function (Browser $browser) use ($ticket) {
            // Logout and login with specific username
            $browser->visit('/saus/logout')->pause(1000);
            $browser->visit('/saus/?master_code=test_master_2025')
                ->pause(1500)
                ->clear('username')
                ->type('username', 'E2ETestUser')
                ->press('Weiter')
                ->pause(2000);

            $uniqueText = 'Username-Test ' . time();
            $browser->visit('/saus/tickets/' . $ticket->id)
                ->pause(1000)
                ->type('#commentContent', $uniqueText)
                ->press('Kommentar speichern')
                ->pause(3000);

            $browser->visit('/saus/tickets/' . $ticket->id)
                ->pause(1000)
                ->assertSee('E2ETestUser')
                ->assertSee($uniqueText);

            // Verify no comment has "Unbekannt" as username (check only comments section)
            $hasUnbekannt = $browser->script("
                var comments = document.querySelectorAll('#comments-container .comment .font-semibold');
                for (var i = 0; i < comments.length; i++) {
                    if (comments[i].textContent.trim() === 'Unbekannt') return true;
                }
                return false;
            ")[0];
            $this->assertFalse($hasUnbekannt, 'No comment should have "Unbekannt" as username');
        });
    }

}
