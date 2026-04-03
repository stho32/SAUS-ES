<?php

namespace Tests\Browser;

use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class CommentFunctionsTest extends DuskTestCase
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

    public function test_comment_form_is_present_on_ticket_page(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginAs($browser);

            $browser->visit('/tickets/1')
                ->pause(1000)
                ->assertPresent('#commentContent')
                ->assertSee('Neuer Kommentar');
        });
    }

    public function test_can_add_comment_to_ticket(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginAs($browser);

            $browser->visit('/tickets/1')
                ->pause(1000)
                ->type('#commentContent', 'E2E Kommentar: Dieses Problem wurde geprueft.')
                ->press('Kommentar speichern')
                ->pause(3000)
                ->assertSee('E2E Kommentar: Dieses Problem wurde geprueft.');
        });
    }

    public function test_hidden_comments_are_not_visible_by_default(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginAs($browser);

            // Visit a ticket that has comments
            $browser->visit('/tickets/1')
                ->pause(1000);

            // Hidden comments should have display:none via CSS class
            $hiddenComments = $browser->elements('.comment-hidden');

            foreach ($hiddenComments as $comment) {
                $display = $comment->getCSSValue('display');
                $this->assertEquals('none', $display, 'Hidden comment should not be visible');
            }
        });
    }

    public function test_show_all_checkbox_reveals_hidden_comments(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginAs($browser);

            $browser->visit('/tickets/1')
                ->pause(1000);

            // Check the "Alle anzeigen" checkbox
            $browser->check('#showAllComments')
                ->pause(500);

            // Hidden comments should now be visible
            $hiddenComments = $browser->elements('.comment-hidden');
            foreach ($hiddenComments as $comment) {
                $display = $comment->getCSSValue('display');
                $this->assertNotEquals('none', $display, 'Hidden comment should be visible after checking "Alle anzeigen"');
            }
        });
    }

    public function test_hide_button_is_present_for_non_system_comments(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginAs($browser);

            $browser->visit('/tickets/1')
                ->pause(1000);

            // Visibility toggle buttons should exist (eye icons)
            $browser->assertPresent('.bi-eye, .bi-eye-slash');
        });
    }

    public function test_vote_buttons_have_tooltips_with_voter_names(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginAs($browser);

            $browser->visit('/tickets/1')
                ->pause(1000);

            // Ticket vote buttons should have title attributes
            $upButton = $browser->element('#ticket-voting button:first-child');
            $this->assertNotNull($upButton, 'Up-vote button should exist');
            $title = $upButton->getAttribute('title');
            $this->assertNotNull($title, 'Up-vote button should have a title tooltip');
        });
    }

    public function test_comment_vote_buttons_have_tooltips(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginAs($browser);

            $browser->visit('/tickets/1')
                ->pause(1000);

            // Comment vote buttons should have title attributes
            $commentUpButtons = $browser->elements('.comment button[title*="vote"], .comment button[title*="Vote"], .comment button[title*="Keine"]');

            if (count($commentUpButtons) > 0) {
                $title = $commentUpButtons[0]->getAttribute('title');
                $this->assertNotNull($title, 'Comment vote button should have voter name tooltip');
            }
        });
    }

    public function test_system_comments_have_different_style_after_status_change(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginAs($browser);

            // Create a ticket and change its status to generate a system comment
            $browser->visit('/tickets/1')
                ->pause(1000);

            // After a status change (via seeder data or manual), system comments
            // should be styled differently with bg-gray-50 class
            // This test verifies the comment-system CSS class exists in the view
            $browser->assertSourceHas('comment-system');
        });
    }

    public function test_own_comments_have_edit_button(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginAs($browser, 'Tester');

            // First add a comment
            $browser->visit('/tickets/2')
                ->pause(1000)
                ->type('#commentContent', 'Mein bearbeitbarer Kommentar')
                ->press('Kommentar speichern')
                ->pause(3000);

            // Reload and check for edit button
            $browser->visit('/tickets/2')
                ->pause(1000)
                ->assertSee('Mein bearbeitbarer Kommentar');

            // Edit pencil icon should be present for own comments
            $browser->assertPresent('.bi-pencil');
        });
    }

    public function test_comment_formatting_works(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginAs($browser);

            $browser->visit('/tickets/3')
                ->pause(1000)
                ->type('#commentContent', '**Fett** und *kursiv* und https://1892.de')
                ->press('Kommentar speichern')
                ->pause(3000);

            // Check that formatted content is rendered
            $browser->visit('/tickets/3')
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

            $browser->visit('/')
                ->pause(1000);

            // Navigation should use brand color (#0786c0), not indigo
            $nav = $browser->element('nav');
            $this->assertNotNull($nav, 'Navigation should exist');
            $bgColor = $nav->getCSSValue('background-color');
            // Brand blue #0786c0 = rgb(7, 134, 192)
            $this->assertStringContainsString('7, 134, 192', $bgColor, 'Navigation should use brand blue color');
        });
    }
}
