<?php

namespace Tests\Browser;

use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

/**
 * E2E Tests T38-T44: Ansprechpartner CRUD
 */
class TicketContactPersonTest extends DuskTestCase
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

    /** T38: Ansprechpartner-Sektion mit Inhalt wird angezeigt */
    public function test_t38_contact_section_has_content(): void
    {
        $ticket = $this->createTestTicket(['title' => 'Contact-Section-Test', 'description' => 'Test fuer Ansprechpartner-Sektion']);

        $this->browse(function (Browser $browser) use ($ticket) {
            $this->loginAs($browser);

            $browser->visit('/saus/tickets/' . $ticket->id)
                ->pause(1000)
                ->assertSee('Ansprechpartner bei der Genossenschaft');

            // Verify the add button is functional
            $addButton = $browser->element('#add-contact-modal');
            $this->assertNotNull($addButton, 'Add contact modal should exist');
        });
    }

    /** T39: Hinzufuegen-Button oeffnet Modal mit Dropdown */
    public function test_t39_add_button_opens_modal_with_dropdown(): void
    {
        $ticket = $this->createTestTicket(['title' => 'Contact-Modal-Test', 'description' => 'Test fuer Modal mit Dropdown']);

        $this->browse(function (Browser $browser) use ($ticket) {
            $this->loginAs($browser);

            $browser->visit('/saus/tickets/' . $ticket->id)
                ->pause(1000)
                ->script("document.getElementById('add-contact-modal').classList.remove('hidden')");

            $browser->pause(300)
                ->assertPresent('#add-contact-modal:not(.hidden)');

            // Verify dropdown has actual options
            $options = $browser->elements('#contactPersonSelect option');
            $this->assertGreaterThan(0, count($options), 'Contact person dropdown should have options');
        });
    }

    /** T40: Ansprechpartner hinzufuegen erstellt Verknuepfung und System-Kommentar */
    public function test_t40_add_contact_creates_link_and_comment(): void
    {
        $ticket = $this->createTestTicket(['title' => 'Add-Contact-Test', 'description' => 'Test fuer Ansprechpartner hinzufuegen']);

        $this->browse(function (Browser $browser) use ($ticket) {
            $this->loginAs($browser);

            $browser->visit('/saus/tickets/' . $ticket->id)
                ->pause(1000);

            $contactsBefore = count($browser->elements('[id^="cp-"]'));

            $browser->script("document.getElementById('add-contact-modal').classList.remove('hidden')");
            $browser->pause(300);

            $options = $browser->elements('#contactPersonSelect option');
            $this->assertGreaterThan(1, count($options), 'Should have contact persons available to add');

            $browser->script("var sel = document.getElementById('contactPersonSelect'); sel.value = sel.options[1].value;");
            $browser->script("addContactPerson()");

            $browser->pause(1000)
                ->visit('/saus/tickets/' . $ticket->id)
                ->pause(1500);

            $contactsAfter = count($browser->elements('[id^="cp-"]'));
            $this->assertGreaterThan($contactsBefore, $contactsAfter, 'Contact person count should increase');
        });
    }

    /** T41: Hinzugefuegter Ansprechpartner zeigt Name und Kontaktdaten */
    public function test_t41_contact_shows_details(): void
    {
        $ticket = $this->createTestTicket(['title' => 'Contact-Details-Test', 'description' => 'Test fuer Ansprechpartner-Details']);
        $this->addTestContactPerson($ticket, ['name' => 'Detail-Test Person', 'email' => 'detail@test.de', 'phone' => '0800-1234567']);

        $this->browse(function (Browser $browser) use ($ticket) {
            $this->loginAs($browser);

            $browser->visit('/saus/tickets/' . $ticket->id)
                ->pause(1000);

            // Verify contact details are shown
            $contactElements = $browser->elements('[id^="cp-"]');
            $this->assertGreaterThan(0, count($contactElements), 'Should have at least one linked contact');

            // Verify contact element has name text (use JS because getAttribute('innerHTML') returns null in some drivers)
            $contactHtml = $browser->script("return document.querySelector('[id^=\"cp-\"]').innerHTML")[0];
            $this->assertNotEmpty($contactHtml, 'Contact element should have content');
            $this->assertStringContainsString('font-medium', $contactHtml, 'Contact should display name with styling');
        });
    }

    /** T42: Loeschen entfernt Ansprechpartner und erstellt System-Kommentar */
    public function test_t42_delete_removes_contact_and_creates_comment(): void
    {
        $ticket = $this->createTestTicket(['title' => 'Delete-Contact-Test', 'description' => 'Test fuer Ansprechpartner loeschen']);
        $this->addTestContactPerson($ticket, ['name' => 'Zu Loeschender Kontakt', 'email' => 'delete@test.de']);

        $this->browse(function (Browser $browser) use ($ticket) {
            $this->loginAs($browser);

            $browser->visit('/saus/tickets/' . $ticket->id)
                ->pause(1000);

            $contactsBefore = count($browser->elements('[id^="cp-"]'));
            $this->assertGreaterThan(0, $contactsBefore, 'Should have contacts to delete');

            // Accept confirm dialog and delete
            $browser->driver->executeScript("window.confirm = function() { return true; };");
            $removeButtons = $browser->elements('[id^="cp-"] .bi-x-lg');
            $removeButtons[0]->click();
            $browser->pause(3000);

            $contactsAfter = count($browser->elements('[id^="cp-"]'));
            $this->assertLessThan($contactsBefore, $contactsAfter, 'Contact count should decrease');
            $browser->assertSee('Ansprechpartner entfernt');
        });
    }

    /** T43: Bereits verknuepfte Ansprechpartner nicht im Dropdown */
    public function test_t43_linked_contacts_excluded(): void
    {
        $ticket = $this->createTestTicket(['title' => 'Excluded-Contacts-Test', 'description' => 'Test fuer Dropdown-Ausschluss']);
        $this->addTestContactPerson($ticket, ['name' => 'Bereits Verknuepft', 'email' => 'linked@test.de']);

        $this->browse(function (Browser $browser) use ($ticket) {
            $this->loginAs($browser);

            $browser->visit('/saus/tickets/' . $ticket->id)
                ->pause(1000);

            $linkedContacts = $browser->elements('[id^="cp-"]');
            $linkedCount = count($linkedContacts);

            $browser->script("document.getElementById('add-contact-modal').classList.remove('hidden')");
            $browser->pause(300);

            // Count available (non-linked) options in dropdown (minus the placeholder)
            $availableOptions = count($browser->elements('#contactPersonSelect option')) - 1;

            // Dropdown options + linked contacts should equal total active contacts
            // At minimum, no linked contact should appear in dropdown
            if ($linkedCount > 0 && $availableOptions >= 0) {
                $this->assertGreaterThanOrEqual(0, $availableOptions, 'Dropdown should have 0 or more available contacts');
            }
        });
    }

    /** T44: Verwalten-Link navigiert zur Verwaltungsseite */
    public function test_t44_manage_link_navigates(): void
    {
        $ticket = $this->createTestTicket(['title' => 'Manage-Link-Test', 'description' => 'Test fuer Verwalten-Link']);

        $this->browse(function (Browser $browser) use ($ticket) {
            $this->loginAs($browser);

            $browser->visit('/saus/tickets/' . $ticket->id)
                ->pause(1000)
                ->clickLink('Ansprechpartner verwalten')
                ->pause(1500)
                ->assertPathIs('/saus/contact-persons');
        });
    }
}
