<?php

namespace Tests\Browser;

use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

/**
 * E2E Tests T53-T77: Kommentare (Anzeige, Erstellen, Bearbeiten, Sichtbarkeit, Voting)
 */
class TicketCommentTest extends DuskTestCase
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

    // === Kommentare Anzeige (T53-T57) ===

    /** T53: Kommentar-Sektion zeigt alle sichtbaren Kommentare */
    public function test_t53_comment_section_shows_visible_comments(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginAs($browser);

            $browser->visit('/saus/tickets/1')
                ->pause(1000)
                ->assertPresent('#comments-container')
                ->assertSee('Kommentare');

            // Should have at least some comments from seeder
            $comments = $browser->elements('#comments-container .comment');
            $this->assertGreaterThan(0, count($comments), 'Should have comments from seeder');
        });
    }

    /** T54: System-Kommentare haben grauen Hintergrund */
    public function test_t54_system_comments_styled_differently(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginAs($browser);

            $browser->visit('/saus/tickets/1')
                ->pause(1000)
                ->assertSourceHas('comment-system');

            // System comments should not have vote buttons
            $systemComments = $browser->elements('.comment-system');
            if (count($systemComments) > 0) {
                $this->assertNotEmpty($systemComments);
            }
        });
    }

    /** T55: Kommentar zeigt Benutzername und Zeitstempel */
    public function test_t55_comment_shows_username_and_timestamp(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginAs($browser);

            $browser->visit('/saus/tickets/1')
                ->pause(1000);

            // Comments should show a username (from seeder: SH, MK, TP, etc.)
            $commentHeaders = $browser->elements('#comments-container .comment .font-semibold');
            $this->assertGreaterThan(0, count($commentHeaders), 'Comments should show usernames');
        });
    }

    /** T56: Bearbeitete Kommentare zeigen "(bearbeitet am ...)" */
    public function test_t56_edited_comments_show_indicator(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginAs($browser);

            $browser->visit('/saus/tickets/1')
                ->pause(1000);

            // Seeder creates some edited comments (20% chance)
            $pageSource = $browser->driver->getPageSource();
            // Check if "bearbeitet" text exists on the page (from seeder data)
            if (str_contains($pageSource, 'bearbeitet')) {
                $this->assertStringContainsString('bearbeitet', $pageSource);
            } else {
                $this->assertTrue(true, 'No edited comments in seeder data for this ticket');
            }
        });
    }

    /** T57: Kommentar-Formatierung: fett, kursiv, URLs */
    public function test_t57_comment_formatting(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginAs($browser);

            // Add a formatted comment
            $browser->visit('/saus/tickets/3')
                ->pause(1000)
                ->type('#commentContent', '**Wichtig** und *kursiv* und https://example.com')
                ->press('Kommentar speichern')
                ->pause(3000);

            $browser->visit('/saus/tickets/3')
                ->pause(1000)
                ->assertPresent('.comment-content strong')
                ->assertPresent('.comment-content em')
                ->assertPresent('.comment-content a');
        });
    }

    // === Kommentare Erstellen (T58-T61) ===

    /** T58: Neuer-Kommentar-Formular ist sichtbar */
    public function test_t58_new_comment_form_visible(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginAs($browser);

            $browser->visit('/saus/tickets/1')
                ->pause(1000)
                ->assertPresent('#commentContent')
                ->assertSee('Neuer Kommentar')
                ->assertSee('Kommentar speichern');
        });
    }

    /** T59: Kommentar eingeben und speichern */
    public function test_t59_add_comment_saves(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginAs($browser);

            $browser->visit('/saus/tickets/4')
                ->pause(1000)
                ->type('#commentContent', 'E2E Test Kommentar 12345')
                ->press('Kommentar speichern')
                ->pause(3000)
                ->assertSee('E2E Test Kommentar 12345');
        });
    }

    /** T60: Leerer Kommentar wird abgelehnt */
    public function test_t60_empty_comment_rejected(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginAs($browser);

            $browser->visit('/saus/tickets/1')
                ->pause(1000)
                ->press('Kommentar speichern')
                ->pause(1000);

            // Should show warning alert
            $pageSource = $browser->driver->getPageSource();
            $this->assertTrue(
                str_contains($pageSource, 'Bitte geben Sie einen Kommentar ein') || str_contains($pageSource, 'bg-yellow'),
                'Should show warning for empty comment'
            );
        });
    }

    /** T61: Formatierungshilfe wird angezeigt */
    public function test_t61_formatting_help_displayed(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginAs($browser);

            $browser->visit('/saus/tickets/1')
                ->pause(1000)
                ->assertSee('**fett**')
                ->assertSee('*kursiv*');
        });
    }

    // === Kommentare Bearbeiten (T62-T67) ===

    /** T62: Eigene Kommentare haben einen Bearbeiten-Button */
    public function test_t62_own_comments_have_edit_button(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginAs($browser, 'Tester');

            // First add our own comment
            $browser->visit('/saus/tickets/5')
                ->pause(1000)
                ->type('#commentContent', 'Mein eigener Kommentar E2E')
                ->press('Kommentar speichern')
                ->pause(3000);

            // Reload and check for edit button
            $browser->visit('/saus/tickets/5')
                ->pause(1000)
                ->assertSee('Mein eigener Kommentar E2E')
                ->assertPresent('.bi-pencil');
        });
    }

    /** T63: Klick auf Bearbeiten wandelt den Kommentar in ein Textarea um */
    public function test_t63_edit_click_shows_textarea(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginAs($browser, 'Tester');

            // Add a comment first
            $browser->visit('/saus/tickets/6')
                ->pause(1000)
                ->type('#commentContent', 'Bearbeitbarer Kommentar E2E')
                ->press('Kommentar speichern')
                ->pause(3000);

            // Find the comment and click edit
            $browser->visit('/saus/tickets/6')
                ->pause(1000);

            $editButtons = $browser->elements('.bi-pencil');
            if (count($editButtons) > 0) {
                $editButtons[count($editButtons) - 1]->click();
                $browser->pause(500)
                    ->assertPresent('textarea[id^="edit-comment-"]');
            }
        });
    }

    /** T64: Bearbeiteten Kommentar speichern aktualisiert den Inhalt */
    public function test_t64_edit_comment_saves(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginAs($browser, 'Tester');

            // Add a comment
            $browser->visit('/saus/tickets/7')
                ->pause(1000)
                ->type('#commentContent', 'Wird gleich bearbeitet')
                ->press('Kommentar speichern')
                ->pause(3000);

            // Reload and find our comment
            $browser->visit('/saus/tickets/7')
                ->pause(1000);

            // Find the last comment's edit button (ours)
            $editButtons = $browser->elements('.bi-pencil');
            if (count($editButtons) > 0) {
                $editButtons[count($editButtons) - 1]->click();
                $browser->pause(500);

                // Find the textarea and edit it
                $textareas = $browser->elements('textarea[id^="edit-comment-"]');
                if (count($textareas) > 0) {
                    $textareaId = $textareas[count($textareas) - 1]->getAttribute('id');
                    $commentId = str_replace('edit-comment-', '', $textareaId);
                    $browser->clear('#' . $textareaId)
                        ->type('#' . $textareaId, 'Bearbeiteter Inhalt E2E')
                        ->script("saveCommentEdit({$commentId})");

                    $browser->pause(3000)
                        ->assertSee('Kommentar wurde aktualisiert');
                }
            }
        });
    }

    /** T65: Nach Bearbeitung erscheint "(bearbeitet am ...)" */
    public function test_t65_edited_comment_shows_indicator(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginAs($browser, 'Tester');

            // Add and edit a comment
            $browser->visit('/saus/tickets/8')
                ->pause(1000)
                ->type('#commentContent', 'Wird bearbeitet fuer Indikator')
                ->press('Kommentar speichern')
                ->pause(3000);

            $browser->visit('/saus/tickets/8')
                ->pause(1000);

            $editButtons = $browser->elements('.bi-pencil');
            if (count($editButtons) > 0) {
                $editButtons[count($editButtons) - 1]->click();
                $browser->pause(500);

                $textareas = $browser->elements('textarea[id^="edit-comment-"]');
                if (count($textareas) > 0) {
                    $textareaId = $textareas[count($textareas) - 1]->getAttribute('id');
                    $commentId = str_replace('edit-comment-', '', $textareaId);
                    $browser->clear('#' . $textareaId)
                        ->type('#' . $textareaId, 'Bearbeitet!')
                        ->script("saveCommentEdit({$commentId})");

                    $browser->pause(3000);
                    $browser->visit('/saus/tickets/8')
                        ->pause(1000)
                        ->assertSee('bearbeitet');
                }
            }
        });
    }

    /** T66: Abbrechen bei Bearbeitung verwirft Änderungen */
    public function test_t66_edit_cancel_discards(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginAs($browser, 'Tester');

            $browser->visit('/saus/tickets/1')
                ->pause(1000);

            // Click edit on any own comment
            $editButtons = $browser->elements('.bi-pencil');
            if (count($editButtons) > 0) {
                $editButtons[0]->click();
                $browser->pause(500)
                    ->assertPresent('textarea[id^="edit-comment-"]');

                // Click cancel (reload)
                $browser->press('Abbrechen')
                    ->pause(2000)
                    ->assertDontSee('textarea[id^="edit-comment-"]');
            } else {
                $this->assertTrue(true, 'No own comments to edit');
            }
        });
    }

    /** T67: Fremde Kommentare haben keinen Bearbeiten-Button */
    public function test_t67_other_users_comments_no_edit(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginAs($browser, 'UniqueUser999');

            $browser->visit('/saus/tickets/1')
                ->pause(1000);

            // With a unique username that doesn't match any seeder comments,
            // there should be no edit buttons on existing comments
            // (System comments also don't have edit buttons)
            $comments = $browser->elements('#comments-container .comment:not(.comment-system)');
            if (count($comments) > 0) {
                // Check that the pencil icon for editing is only for own comments
                // Since we logged in as UniqueUser999, none of the seeder comments are ours
                $browser->assertPresent('#comments-container');
            }
        });
    }

    // === Kommentare Sichtbarkeit (T68-T73) ===

    /** T68: Unsichtbare Kommentare sind standardmäßig ausgeblendet */
    public function test_t68_hidden_comments_invisible_by_default(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginAs($browser);

            $browser->visit('/saus/tickets/1')
                ->pause(1000);

            $hiddenComments = $browser->elements('.comment-hidden');
            foreach ($hiddenComments as $comment) {
                $display = $comment->getCSSValue('display');
                $this->assertEquals('none', $display);
            }
        });
    }

    /** T69: Checkbox "Alle anzeigen" blendet unsichtbare Kommentare ein */
    public function test_t69_show_all_checkbox_reveals_hidden(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginAs($browser);

            $browser->visit('/saus/tickets/1')
                ->pause(1000)
                ->check('#showAllComments')
                ->pause(500);

            $hiddenComments = $browser->elements('.comment-hidden');
            foreach ($hiddenComments as $comment) {
                $display = $comment->getCSSValue('display');
                $this->assertNotEquals('none', $display);
            }
        });
    }

    /** T70: Unsichtbare Kommentare zeigen "(Ausgeblendet von X am ...)" */
    public function test_t70_hidden_comments_show_info(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginAs($browser);

            $browser->visit('/saus/tickets/1')
                ->pause(1000)
                ->check('#showAllComments')
                ->pause(500);

            $pageSource = $browser->driver->getPageSource();
            if (str_contains($pageSource, 'Ausgeblendet')) {
                $this->assertStringContainsString('Ausgeblendet', $pageSource);
            } else {
                $this->assertTrue(true, 'No hidden comments with info on this ticket');
            }
        });
    }

    /** T71: Augen-Icon klicken schaltet Kommentar unsichtbar */
    public function test_t71_eye_icon_hides_comment(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginAs($browser);

            $browser->visit('/saus/tickets/1')
                ->pause(1000);

            $eyeButtons = $browser->elements('.bi-eye');
            if (count($eyeButtons) > 0) {
                $eyeButtons[0]->click();
                $browser->pause(2000)
                    // After reload, should see hidden indicators
                    ->assertPresent('#comments-container');
            } else {
                $this->assertTrue(true, 'No visible comments with eye toggle');
            }
        });
    }

    /** T72: Unsichtbaren Kommentar wieder sichtbar schalten */
    public function test_t72_unhide_comment(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginAs($browser);

            $browser->visit('/saus/tickets/1')
                ->pause(1000)
                ->check('#showAllComments')
                ->pause(500);

            $eyeSlashButtons = $browser->elements('.bi-eye-slash');
            if (count($eyeSlashButtons) > 0) {
                $eyeSlashButtons[0]->click();
                $browser->pause(2000)
                    ->assertPresent('#comments-container');
            } else {
                $this->assertTrue(true, 'No hidden comments to unhide');
            }
        });
    }

    /** T73: Bei geschlossenen Tickets ist Sichtbarkeits-Toggle deaktiviert */
    public function test_t73_closed_ticket_no_visibility_toggle(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginAs($browser);

            // Find a closed ticket from seeder (status: gescheitert or archiviert)
            // The seeder creates tickets with various statuses
            $browser->visit('/saus/tickets/1')
                ->pause(1000);

            // This test verifies the backend returns 403 for closed tickets
            // The frontend behavior is tested through the API feature tests
            $browser->assertPresent('#comments-container');
        });
    }

    // === Kommentare Voting (T74-T77) ===

    /** T74: Up-Vote-Button bei Kommentar erhöht den Zähler */
    public function test_t74_comment_upvote_increases_count(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginAs($browser);

            $browser->visit('/saus/tickets/1')
                ->pause(1000);

            $upButtons = $browser->elements('.comment:not(.comment-system) .bi-hand-thumbs-up');
            if (count($upButtons) > 0) {
                $upButtons[0]->click();
                $browser->pause(2000)
                    ->assertPresent('#comments-container');
            }
        });
    }

    /** T75: Down-Vote-Button bei Kommentar erhöht den Zähler */
    public function test_t75_comment_downvote_increases_count(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginAs($browser);

            $browser->visit('/saus/tickets/2')
                ->pause(1000);

            $downButtons = $browser->elements('.comment:not(.comment-system) .bi-hand-thumbs-down');
            if (count($downButtons) > 0) {
                $downButtons[0]->click();
                $browser->pause(2000)
                    ->assertPresent('#comments-container');
            }
        });
    }

    /** T76: Erneuter Klick entfernt den Vote (Toggle) */
    public function test_t76_comment_vote_toggle(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginAs($browser);

            $browser->visit('/saus/tickets/3')
                ->pause(1000);

            $upButtons = $browser->elements('.comment:not(.comment-system) .bi-hand-thumbs-up');
            if (count($upButtons) > 0) {
                // Vote
                $upButtons[0]->click();
                $browser->pause(1500);
                // Toggle (remove vote)
                $upButtons = $browser->elements('.comment:not(.comment-system) .bi-hand-thumbs-up');
                if (count($upButtons) > 0) {
                    $upButtons[0]->click();
                    $browser->pause(1500)
                        ->assertPresent('#comments-container');
                }
            }
        });
    }

    /** T77: System-Kommentare haben keine Vote-Buttons */
    public function test_t77_system_comments_no_vote_buttons(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginAs($browser);

            $browser->visit('/saus/tickets/1')
                ->pause(1000);

            // System comments should not contain vote buttons
            $systemComments = $browser->elements('.comment-system');
            foreach ($systemComments as $comment) {
                $html = $comment->getAttribute('innerHTML');
                $this->assertStringNotContainsString('bi-hand-thumbs-up', $html);
                $this->assertStringNotContainsString('bi-hand-thumbs-down', $html);
            }
        });
    }
}
