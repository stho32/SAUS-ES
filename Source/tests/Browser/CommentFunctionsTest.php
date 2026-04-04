<?php

namespace Tests\Browser;

use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class CommentFunctionsTest extends DuskTestCase
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

    public function test_can_add_comment_and_it_appears(): void
    {
        $ticket = $this->createTestTicket(['title' => 'Kommentar-Test Ticket', 'description' => 'Ticket fuer Kommentar-Tests']);

        $this->browse(function (Browser $browser) use ($ticket) {
            $this->loginAs($browser);

            $browser->visit('/saus/tickets/' . $ticket->id)
                ->pause(1000);

            $commentsBefore = count($browser->elements('#comments-container .comment'));

            $browser->type('#commentContent', 'E2E Kommentar: Dieses Problem wurde geprueft.')
                ->press('Kommentar speichern')
                ->pause(3000)
                ->assertSee('E2E Kommentar: Dieses Problem wurde geprueft.');

            $commentsAfter = count($browser->elements('#comments-container .comment'));
            $this->assertGreaterThan($commentsBefore, $commentsAfter, 'Comment count should increase');
        });
    }

    public function test_hidden_comments_are_not_visible_by_default(): void
    {
        $ticket = $this->createTestTicket(['title' => 'Hidden-Comment-Test', 'description' => 'Ticket mit versteckten Kommentaren']);
        $this->addTestComment($ticket, ['content' => 'Sichtbarer Kommentar', 'is_visible' => true]);
        $this->addTestComment($ticket, ['content' => 'Versteckter Kommentar', 'is_visible' => false, 'hidden_by' => 'Admin', 'hidden_at' => now()]);

        $this->browse(function (Browser $browser) use ($ticket) {
            $this->loginAs($browser);

            $browser->visit('/saus/tickets/' . $ticket->id)
                ->pause(1000);

            $hiddenComments = $browser->elements('.comment-hidden');
            foreach ($hiddenComments as $comment) {
                $display = $comment->getCSSValue('display');
                $this->assertEquals('none', $display, 'Hidden comment should not be visible');
            }
        });
    }

    public function test_show_all_checkbox_reveals_hidden_comments(): void
    {
        $ticket = $this->createTestTicket(['title' => 'Show-All-Test', 'description' => 'Ticket fuer Alle-anzeigen-Filter']);
        $this->addTestComment($ticket, ['content' => 'Sichtbarer Kommentar', 'is_visible' => true]);
        $this->addTestComment($ticket, ['content' => 'Versteckter Kommentar', 'is_visible' => false, 'hidden_by' => 'Admin', 'hidden_at' => now()]);

        $this->browse(function (Browser $browser) use ($ticket) {
            $this->loginAs($browser);

            $browser->visit('/saus/tickets/' . $ticket->id)
                ->pause(1000)
                ->check('#showAllComments')
                ->pause(500);

            $hiddenComments = $browser->elements('.comment-hidden');
            foreach ($hiddenComments as $comment) {
                $display = $comment->getCSSValue('display');
                $this->assertNotEquals('none', $display, 'Hidden comment should be visible after checking "Alle anzeigen"');
            }
        });
    }

    public function test_system_comments_have_no_vote_buttons(): void
    {
        $ticket = $this->createTestTicket(['title' => 'System-Comment-Test', 'description' => 'Ticket mit Systemkommentar']);
        $this->addTestComment($ticket, ['content' => 'Statusaenderung: offen -> in_bearbeitung', 'username' => 'System', 'is_visible' => true]);

        $this->browse(function (Browser $browser) use ($ticket) {
            $this->loginAs($browser);

            $browser->visit('/saus/tickets/' . $ticket->id)
                ->pause(1000);

            $systemHasVotes = $browser->script("
                var sys = document.querySelectorAll('.comment-system');
                if (sys.length === 0) return null;
                for (var i = 0; i < sys.length; i++) {
                    if (sys[i].innerHTML.indexOf('bi-hand-thumbs-up') !== -1) return true;
                }
                return false;
            ")[0];
            $this->assertNotNull($systemHasVotes, 'Should have system comments');
            $this->assertFalse($systemHasVotes, 'System comments should not have vote buttons');
        });
    }

    public function test_own_comments_have_edit_button(): void
    {
        $ticket = $this->createTestTicket(['title' => 'Edit-Button-Test', 'description' => 'Ticket fuer Edit-Button-Test']);

        $this->browse(function (Browser $browser) use ($ticket) {
            $this->loginAs($browser, 'Tester');

            $browser->visit('/saus/tickets/' . $ticket->id)
                ->pause(1000)
                ->type('#commentContent', 'Mein bearbeitbarer Kommentar')
                ->press('Kommentar speichern')
                ->pause(3000);

            $browser->visit('/saus/tickets/' . $ticket->id)
                ->pause(1000)
                ->assertSee('Mein bearbeitbarer Kommentar')
                ->assertPresent('.bi-pencil');
        });
    }

    public function test_comment_formatting_renders_html(): void
    {
        $ticket = $this->createTestTicket(['title' => 'Formatting-Test', 'description' => 'Ticket fuer Kommentar-Formatierung']);

        $this->browse(function (Browser $browser) use ($ticket) {
            $this->loginAs($browser);

            $browser->visit('/saus/tickets/' . $ticket->id)
                ->pause(1000)
                ->type('#commentContent', '**Fett** und *kursiv* und https://1892.de')
                ->press('Kommentar speichern')
                ->pause(3000);

            $browser->visit('/saus/tickets/' . $ticket->id)
                ->pause(1000)
                ->assertPresent('.comment-content strong')
                ->assertPresent('.comment-content em')
                ->assertPresent('.comment-content a');
        });
    }

    public function test_brand_colors_are_applied(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginAs($browser);

            $browser->visit('/saus/')
                ->pause(1000);

            $nav = $browser->element('nav');
            $this->assertNotNull($nav, 'Navigation should exist');
            $bgColor = $nav->getCSSValue('background-color');
            $this->assertStringContainsString('7, 134, 192', $bgColor, 'Navigation should use brand blue color');
        });
    }
}
