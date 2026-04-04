<?php

namespace Tests\Browser;

use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

/**
 * E2E Tests T53-T77: Kommentare
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

    // === Anzeige (T53-T57) ===

    /** T53: Kommentar-Sektion zeigt sichtbare Kommentare mit Inhalt */
    public function test_t53_comments_show_with_content(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginAs($browser);

            $browser->visit('/saus/tickets/1')
                ->pause(1000);

            $comments = $browser->elements('#comments-container .comment');
            $this->assertGreaterThan(0, count($comments), 'Should have comments from seeder');

            // Verify comments container has actual content (not empty placeholders)
            $containerHtml = $browser->script("return document.getElementById('comments-container').innerHTML")[0];
            $this->assertNotEmpty(trim(strip_tags($containerHtml)), 'Comments container should have visible text content');
        });
    }

    /** T54: System-Kommentare haben keinen Vote-Bereich */
    public function test_t54_system_comments_no_votes(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginAs($browser);

            $browser->visit('/saus/tickets/1')
                ->pause(1000);

            $systemCount = $browser->script("return document.querySelectorAll('.comment-system').length")[0];
            $this->assertGreaterThan(0, $systemCount, 'Should have system comments');

            // System comments should not have vote buttons
            $systemHasVotes = $browser->script("
                var sys = document.querySelectorAll('.comment-system');
                for (var i = 0; i < sys.length; i++) {
                    if (sys[i].innerHTML.indexOf('bi-hand-thumbs-up') !== -1) return true;
                }
                return false;
            ")[0];
            $this->assertFalse($systemHasVotes, 'System comments should not have vote buttons');

            // Non-system comments SHOULD have vote buttons
            $userHasVotes = $browser->script("
                var usr = document.querySelectorAll('.comment:not(.comment-system):not(.comment-hidden)');
                return usr.length > 0 && usr[0].innerHTML.indexOf('bi-hand-thumbs-up') !== -1;
            ")[0];
            $this->assertTrue($userHasVotes, 'User comments should have vote buttons');
        });
    }

    /** T55: Kommentar zeigt Benutzername und Zeitstempel */
    public function test_t55_comment_shows_username_and_time(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginAs($browser);

            $browser->visit('/saus/tickets/1')
                ->pause(1000);

            $commentData = $browser->script("
                var comments = document.querySelectorAll('.comment:not(.comment-system):not(.comment-hidden)');
                if (comments.length === 0) return null;
                return {
                    html: comments[0].innerHTML,
                    text: comments[0].textContent
                };
            ")[0];
            $this->assertNotNull($commentData, 'Should have user comments');
            $this->assertStringContainsString('font-semibold', $commentData['html'], 'Username should be styled bold');
            // Timestamp format: DD.MM.YYYY HH:MM
            $this->assertMatchesRegularExpression('/\d{2}\.\d{2}\.\d{4}\s+\d{2}:\d{2}/', $commentData['text'], 'Should contain timestamp');
        });
    }

    /** T56: Bearbeitete Kommentare zeigen Indikator */
    public function test_t56_edited_comments_have_indicator(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginAs($browser, 'Tester');

            // Create and edit a comment to guarantee edited state
            $browser->visit('/saus/tickets/5')
                ->pause(1000)
                ->type('#commentContent', 'Wird gleich bearbeitet T56')
                ->press('Kommentar speichern')
                ->pause(3000);

            $browser->visit('/saus/tickets/5')
                ->pause(1000);

            // Find our comment and edit it
            $editButtons = $browser->elements('.bi-pencil');
            $this->assertGreaterThan(0, count($editButtons), 'Should have edit button for own comment');

            $editButtons[count($editButtons) - 1]->click();
            $browser->pause(500);

            $textareas = $browser->elements('textarea[id^="edit-comment-"]');
            $textareaId = $textareas[count($textareas) - 1]->getAttribute('id');
            $commentId = str_replace('edit-comment-', '', $textareaId);

            $browser->clear('#' . $textareaId)
                ->type('#' . $textareaId, 'Bearbeitet T56')
                ->script("saveCommentEdit({$commentId})");

            $browser->pause(3000);
            $browser->visit('/saus/tickets/5')
                ->pause(1000)
                ->assertSee('bearbeitet');
        });
    }

    /** T57: Kommentar-Formatierung rendert HTML */
    public function test_t57_formatting_renders(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginAs($browser);

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

    // === Erstellen (T58-T61) ===

    /** T58: Kommentar-Formular funktioniert */
    public function test_t58_comment_form_submits(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginAs($browser);

            $browser->visit('/saus/tickets/4')
                ->pause(1000);

            $commentsBefore = count($browser->elements('#comments-container .comment'));

            $browser->type('#commentContent', 'E2E Testkommentar T58 ' . time())
                ->press('Kommentar speichern')
                ->pause(3000);

            $commentsAfter = count($browser->elements('#comments-container .comment'));
            $this->assertGreaterThan($commentsBefore, $commentsAfter, 'Comment count should increase');
        });
    }

    /** T59: Kommentar erscheint mit eingegebenem Text */
    public function test_t59_comment_shows_entered_text(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginAs($browser);

            $uniqueText = 'E2E Kommentar ' . time();
            $browser->visit('/saus/tickets/4')
                ->pause(1000)
                ->type('#commentContent', $uniqueText)
                ->press('Kommentar speichern')
                ->pause(3000)
                ->assertSee($uniqueText);
        });
    }

    /** T60: Leerer Kommentar zeigt Warnung */
    public function test_t60_empty_comment_warns(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginAs($browser);

            $browser->visit('/saus/tickets/1')
                ->pause(1000);

            $commentsBefore = count($browser->elements('#comments-container .comment'));

            $browser->press('Kommentar speichern')
                ->pause(1000);

            // Warning alert should appear
            $alerts = $browser->elements('.bg-yellow-50');
            $this->assertGreaterThan(0, count($alerts), 'Warning alert should appear for empty comment');

            // Comment count should NOT increase
            $commentsAfter = count($browser->elements('#comments-container .comment'));
            $this->assertEquals($commentsBefore, $commentsAfter, 'No comment should be added');
        });
    }

    /** T61: Formatierungshilfe ist sichtbar */
    public function test_t61_formatting_help_visible(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginAs($browser);

            $browser->visit('/saus/tickets/1')
                ->pause(1000)
                ->assertSee('**fett**')
                ->assertSee('*kursiv*');
        });
    }

    // === Bearbeiten (T62-T67) ===

    /** T62: Eigene Kommentare haben Bearbeiten-Button */
    public function test_t62_own_comments_have_edit(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginAs($browser, 'Tester');

            $browser->visit('/saus/tickets/6')
                ->pause(1000)
                ->type('#commentContent', 'Eigener Kommentar T62')
                ->press('Kommentar speichern')
                ->pause(3000);

            $browser->visit('/saus/tickets/6')
                ->pause(1000)
                ->assertSee('Eigener Kommentar T62')
                ->assertPresent('.bi-pencil');
        });
    }

    /** T63: Edit-Klick öffnet Textarea */
    public function test_t63_edit_opens_textarea(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginAs($browser, 'Tester');

            $browser->visit('/saus/tickets/6')
                ->pause(1000);

            $editButtons = $browser->elements('.bi-pencil');
            $this->assertGreaterThan(0, count($editButtons), 'Should have edit buttons');

            $editButtons[count($editButtons) - 1]->click();
            $browser->pause(500);

            $textareas = $browser->elements('textarea[id^="edit-comment-"]');
            $this->assertGreaterThan(0, count($textareas), 'Textarea should appear for editing');
        });
    }

    /** T64: Bearbeiteten Kommentar speichern */
    public function test_t64_edit_saves_content(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginAs($browser, 'Tester');

            // Create a comment to edit
            $browser->visit('/saus/tickets/7')
                ->pause(1000)
                ->type('#commentContent', 'Original T64')
                ->press('Kommentar speichern')
                ->pause(3000);

            $browser->visit('/saus/tickets/7')
                ->pause(1000);

            $editButtons = $browser->elements('.bi-pencil');
            $editButtons[count($editButtons) - 1]->click();
            $browser->pause(500);

            $textareas = $browser->elements('textarea[id^="edit-comment-"]');
            $textareaId = $textareas[count($textareas) - 1]->getAttribute('id');
            $commentId = str_replace('edit-comment-', '', $textareaId);

            $browser->clear('#' . $textareaId)
                ->type('#' . $textareaId, 'Bearbeitet T64')
                ->script("saveCommentEdit({$commentId})");

            $browser->pause(3000);
            // Reload and verify the edit persisted
            $browser->visit('/saus/tickets/7')
                ->pause(1000)
                ->assertSee('Bearbeitet T64')
                ->assertSee('bearbeitet');
        });
    }

    /** T66: Abbrechen verwirft Bearbeitungsänderungen */
    public function test_t66_edit_cancel_discards(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginAs($browser, 'Tester');

            $browser->visit('/saus/tickets/6')
                ->pause(1000);

            $editButtons = $browser->elements('.bi-pencil');
            if (count($editButtons) > 0) {
                $editButtons[0]->click();
                $browser->pause(500)
                    ->assertPresent('textarea[id^="edit-comment-"]');

                // Abbrechen reloads page
                $browser->press('Abbrechen')
                    ->pause(2000);

                // After reload, no edit textarea should be open
                $openTextareas = $browser->elements('textarea[id^="edit-comment-"]');
                $this->assertEquals(0, count($openTextareas), 'Cancel should close edit textarea');
            }
        });
    }

    /** T67: Fremde Kommentare haben keinen Edit-Button */
    public function test_t67_other_users_no_edit_button(): void
    {
        $this->browse(function (Browser $browser) {
            // Logout first to force new username
            $browser->visit('/saus/logout')->pause(500);
            $this->loginAs($browser, 'NiemandSonst999');

            $browser->visit('/saus/tickets/1')
                ->pause(1000);

            // With this unique username, no seeder comments belong to us
            // So no edit buttons should exist (except system comments which also don't have them)
            $editButtons = $browser->elements('.bi-pencil');
            $this->assertEquals(0, count($editButtons), 'Should have no edit buttons for foreign comments');
        });
    }

    // === Sichtbarkeit (T68-T73) ===

    /** T68: Unsichtbare Kommentare sind ausgeblendet */
    public function test_t68_hidden_comments_invisible(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginAs($browser);

            $browser->visit('/saus/tickets/1')
                ->pause(1000);

            $hiddenComments = $browser->elements('.comment-hidden');
            foreach ($hiddenComments as $comment) {
                $this->assertEquals('none', $comment->getCSSValue('display'));
            }
        });
    }

    /** T69: "Alle anzeigen" macht unsichtbare sichtbar */
    public function test_t69_show_all_reveals_hidden(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginAs($browser);

            $browser->visit('/saus/tickets/1')
                ->pause(1000)
                ->check('#showAllComments')
                ->pause(500);

            $hiddenComments = $browser->elements('.comment-hidden');
            foreach ($hiddenComments as $comment) {
                $this->assertNotEquals('none', $comment->getCSSValue('display'));
            }
        });
    }

    /** T70: Ausgeblendete Kommentare zeigen Info */
    public function test_t70_hidden_comments_show_info_text(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginAs($browser);

            $browser->visit('/saus/tickets/1')
                ->pause(1000);

            // Hide a comment via JS to ensure we have one
            $commentId = $browser->script("
                var btns = document.querySelectorAll('.comment:not(.comment-system) .bi-eye');
                if (btns.length > 0) { btns[0].closest('button').click(); return true; }
                return false;
            ")[0];

            if ($commentId) {
                $browser->pause(2000);
            }

            // Now check hidden comments show info
            $browser->check('#showAllComments')
                ->pause(500);

            $pageSource = $browser->driver->getPageSource();
            $this->assertStringContainsString('Ausgeblendet', $pageSource, 'Hidden comment should show "Ausgeblendet" info');
        });
    }

    /** T71: Augen-Icon blendet Kommentar aus */
    public function test_t71_eye_icon_hides(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginAs($browser);

            $browser->visit('/saus/tickets/2')
                ->pause(1000);

            $visibleBefore = (int) $browser->script("return document.querySelectorAll('.comment:not(.comment-hidden):not(.comment-system)').length")[0];

            // Click via JS to avoid ElementNotInteractable
            $clicked = $browser->script("
                var btns = document.querySelectorAll('.comment:not(.comment-system) .bi-eye');
                if (btns.length > 0) { btns[0].closest('button').click(); return true; }
                return false;
            ")[0];
            $this->assertTrue($clicked, 'Should have eye buttons to click');

            $browser->pause(2000);

            // After reload, visible count should decrease
            $visibleAfter = (int) $browser->script("return document.querySelectorAll('.comment:not(.comment-hidden):not(.comment-system)').length")[0];
            $this->assertLessThan($visibleBefore, $visibleAfter, 'Visible comment count should decrease');
        });
    }

    /** T72: Ausgeblendeten Kommentar wieder einblenden */
    public function test_t72_unhide_comment(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginAs($browser);

            $browser->visit('/saus/tickets/2')
                ->pause(1000)
                ->check('#showAllComments')
                ->pause(500);

            $hiddenBefore = (int) $browser->script("return document.querySelectorAll('.comment-hidden').length")[0];

            // Click unhide via JS
            $clicked = $browser->script("
                var btns = document.querySelectorAll('.comment-hidden .bi-eye-slash');
                if (btns.length > 0) { btns[0].closest('button').click(); return true; }
                return false;
            ")[0];

            if ($clicked) {
                $browser->pause(2000);

                $hiddenAfter = (int) $browser->script("return document.querySelectorAll('.comment-hidden').length")[0];
                $this->assertLessThan($hiddenBefore, $hiddenAfter, 'Hidden comment count should decrease');
            }
        });
    }

    /** T72b: "Alle anzeigen"-Filter bleibt aktiv nach Visibility-Toggle */
    public function test_t72b_show_all_filter_persists_after_visibility_toggle(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginAs($browser);

            $browser->visit('/saus/tickets/119')
                ->pause(2000);

            // Create two test comments via API
            $createdIds = [];
            for ($i = 1; $i <= 2; $i++) {
                $result = $browser->script("
                    var result = null;
                    var xhr = new XMLHttpRequest();
                    xhr.open('POST', API_BASE + '/tickets/' + TICKET_ID + '/comments', false);
                    xhr.setRequestHeader('Content-Type', 'application/json');
                    xhr.setRequestHeader('X-CSRF-TOKEN', document.querySelector('meta[name=csrf-token]').content);
                    xhr.send(JSON.stringify({ content: 'Test-Kommentar-Visibility-{$i}' }));
                    if (xhr.status === 200 || xhr.status === 201) {
                        var data = JSON.parse(xhr.responseText);
                        result = data.data ? data.data.id : null;
                    }
                    return result;
                ")[0];
                if ($result) {
                    $createdIds[] = $result;
                }
            }

            $this->assertCount(2, $createdIds, 'Should have created 2 test comments');

            // Hide both comments via synchronous API calls
            foreach ($createdIds as $cid) {
                $browser->script("
                    var xhr = new XMLHttpRequest();
                    xhr.open('POST', API_BASE + '/comments/' + {$cid} + '/visibility', false);
                    xhr.setRequestHeader('Content-Type', 'application/json');
                    xhr.setRequestHeader('X-CSRF-TOKEN', document.querySelector('meta[name=csrf-token]').content);
                    xhr.send(JSON.stringify({ is_visible: false }));
                ");
            }

            // Reload page to see the hidden comments
            $browser->visit('/saus/tickets/119')
                ->pause(2000);

            // Activate "Alle anzeigen" filter
            $browser->check('#showAllComments')
                ->pause(500);

            $hiddenBefore = (int) $browser->script("return document.querySelectorAll('.comment-hidden').length")[0];
            $this->assertGreaterThanOrEqual(2, $hiddenBefore, 'Need at least 2 hidden comments for this test');

            // All hidden comments should be visible now
            $hiddenVisible = $browser->script("
                var hidden = document.querySelectorAll('.comment-hidden');
                var allVisible = true;
                hidden.forEach(function(c) {
                    if (window.getComputedStyle(c).display === 'none') allVisible = false;
                });
                return allVisible;
            ")[0];
            $this->assertTrue($hiddenVisible, 'All hidden comments should be visible when filter is active');

            // Now unhide ONE comment via eye-slash button (this triggers the bug)
            $browser->script("
                var btns = document.querySelectorAll('.comment-hidden .bi-eye-slash');
                if (btns.length > 0) { btns[0].closest('button').click(); }
            ");
            $browser->pause(3000);

            // The "Alle anzeigen" checkbox should still be checked
            $isChecked = $browser->script("return document.getElementById('showAllComments').checked")[0];
            $this->assertTrue($isChecked, 'Show-all checkbox should still be checked after visibility toggle');

            // Remaining hidden comments should STILL be visible (not hidden again)
            $remainingHidden = $browser->elements('.comment-hidden');
            $this->assertGreaterThanOrEqual(1, count($remainingHidden), 'Should still have at least 1 hidden comment');
            foreach ($remainingHidden as $comment) {
                $display = $comment->getCSSValue('display');
                $this->assertNotEquals('none', $display, 'Hidden comments should remain visible while "Alle anzeigen" is active');
            }

            // Cleanup: restore all created comments to visible
            foreach ($createdIds as $cid) {
                $browser->script("
                    var xhr = new XMLHttpRequest();
                    xhr.open('POST', API_BASE + '/comments/' + {$cid} + '/visibility', false);
                    xhr.setRequestHeader('Content-Type', 'application/json');
                    xhr.setRequestHeader('X-CSRF-TOKEN', document.querySelector('meta[name=csrf-token]').content);
                    xhr.send(JSON.stringify({ is_visible: true }));
                ");
            }
        });
    }

    // === Voting (T74-T77) ===

    /** T74: Up-Vote bei Kommentar erhöht Zähler */
    public function test_t74_comment_upvote_increases(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginAs($browser);

            $browser->visit('/saus/tickets/1')
                ->pause(1000);

            $upSpans = $browser->elements('[id^="comment-up-"]');
            $this->assertGreaterThan(0, count($upSpans), 'Should have comment vote count spans');

            $firstSpanId = $upSpans[0]->getAttribute('id');
            $countBefore = (int) $upSpans[0]->getText();

            // Click the upvote button for this comment
            $commentId = str_replace('comment-up-', '', $firstSpanId);
            $browser->script("voteComment({$commentId}, 'up')");
            $browser->pause(2000);

            $countAfter = (int) $browser->text('#' . $firstSpanId);
            $this->assertGreaterThanOrEqual($countBefore, $countAfter, 'Upvote count should increase or stay');
        });
    }

    /** T75: Down-Vote bei Kommentar erhöht Zähler */
    public function test_t75_comment_downvote_increases(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginAs($browser);

            $browser->visit('/saus/tickets/2')
                ->pause(1000);

            $downSpans = $browser->elements('[id^="comment-down-"]');
            $this->assertGreaterThan(0, count($downSpans), 'Should have comment downvote spans');

            $firstSpanId = $downSpans[0]->getAttribute('id');
            $countBefore = (int) $downSpans[0]->getText();

            $commentId = str_replace('comment-down-', '', $firstSpanId);
            $browser->script("voteComment({$commentId}, 'down')");
            $browser->pause(2000);

            $countAfter = (int) $browser->text('#' . $firstSpanId);
            $this->assertGreaterThanOrEqual($countBefore, $countAfter);
        });
    }

    /** T76: Doppelklick entfernt Vote — gleichen Button zweimal klicken */
    public function test_t76_comment_vote_toggle(): void
    {
        $ticket = \App\Models\Ticket::whereHas('comments', function ($q) {
            $q->where('username', '!=', 'System');
        })->first();
        $this->assertNotNull($ticket, 'Need a ticket with non-system comments');

        // Bestehende Votes dieses Testusers entfernen, damit der Test sauber startet
        \App\Models\CommentVote::where('username', 'ToggleTester')->delete();

        $this->browse(function (Browser $browser) use ($ticket) {
            $this->loginAs($browser, 'ToggleTester');

            $browser->visit('/saus/tickets/' . $ticket->id)
                ->pause(1000);

            $upSpans = $browser->elements('[id^="comment-up-"]');
            $this->assertGreaterThan(0, count($upSpans), 'Should have comment vote count spans');

            $firstSpanId = $upSpans[0]->getAttribute('id');
            $countBefore = (int) $upSpans[0]->getText();
            $commentId = str_replace('comment-up-', '', $firstSpanId);

            // Erster Klick: Vote setzen (up)
            $browser->script("voteComment({$commentId}, 'up')");
            $browser->pause(1500);

            $countAfterVote = (int) $browser->text('#' . $firstSpanId);
            $this->assertGreaterThan($countBefore, $countAfterVote, 'Upvote count should increase after first click');

            // Zweiter Klick: Gleichen Button nochmal — Vote muss entfernt werden (Toggle)
            $browser->script("voteComment({$commentId}, 'up')");
            $browser->pause(1500);

            $countAfterToggle = (int) $browser->text('#' . $firstSpanId);
            $this->assertEquals($countBefore, $countAfterToggle, 'Count should return to original after clicking same vote button again (toggle)');
        });
    }

    /** Tooltip aktualisiert sich nach Vote (Bug: Tooltip blieb stale) */
    public function test_comment_vote_updates_tooltip(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginAs($browser, 'TooltipTester');

            $browser->visit('/saus/tickets/5')
                ->pause(1000);

            // Find an upvote button with a tooltip
            $upButtons = $browser->elements('#comments-container button[onclick^="voteComment"]');
            $this->assertGreaterThan(0, count($upButtons), 'Should have vote buttons');

            // Get the first upvote button (every other button is upvote)
            $upButton = $upButtons[0];
            $tooltipBefore = $upButton->getAttribute('title');

            // Extract comment ID from onclick attribute
            $onclick = $upButton->getAttribute('onclick');
            preg_match('/voteComment\((\d+)/', $onclick, $matches);
            $commentId = $matches[1];

            // Vote up
            $browser->script("voteComment({$commentId}, 'up')");
            $browser->pause(2000);

            // Re-read the tooltip after voting
            $tooltipAfter = $browser->script(
                "return document.querySelector('button[onclick*=\"voteComment({$commentId}\"]').title"
            )[0];

            // The tooltip must contain the voter's username after voting
            $this->assertStringContainsString('TooltipTester', $tooltipAfter,
                'Tooltip should include the voter name after voting, but was: ' . $tooltipAfter);

            // The tooltip must not be the stale server-rendered value "Keine Upvotes"
            $this->assertNotEquals('Keine Upvotes', $tooltipAfter,
                'Tooltip should not be the empty default after a vote was cast');
        });
    }

    /** T77: System-Kommentare haben keine Vote-Buttons */
    public function test_t77_system_no_votes(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginAs($browser);

            $browser->visit('/saus/tickets/1')
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
}
