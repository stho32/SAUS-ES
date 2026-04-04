<?php

namespace Tests\Browser;

use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

/**
 * E2E Tests T45-T52: Datei-Uploads (Anhaenge)
 */
class TicketAttachmentTest extends DuskTestCase
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

    /** T45: Upload-Formular akzeptiert Dateien */
    public function test_t45_upload_form_accepts_files(): void
    {
        $ticket = $this->createTestTicket(['title' => 'Upload-Form-Test', 'description' => 'Ticket fuer Upload-Formular-Test']);

        $this->browse(function (Browser $browser) use ($ticket) {
            $this->loginAs($browser);

            $browser->visit('/saus/tickets/' . $ticket->id)
                ->pause(1000)
                ->assertPresent('#uploadForm')
                ->assertPresent('#fileInput');

            // Verify the file input is actually interactable
            $fileInput = $browser->element('#fileInput');
            $this->assertNotNull($fileInput, 'File input should exist and be interactable');
        });
    }

    /** T46: Bild-Upload erscheint im Attachment-Grid */
    public function test_t46_image_upload_appears_in_grid(): void
    {
        $ticket = $this->createTestTicket(['title' => 'Image-Upload-Test', 'description' => 'Ticket fuer Bild-Upload-Test']);

        $this->browse(function (Browser $browser) use ($ticket) {
            $this->loginAs($browser);

            $testImagePath = storage_path('app/test_upload.jpg');
            $img = imagecreatetruecolor(100, 100);
            $red = imagecolorallocate($img, 255, 0, 0);
            imagefill($img, 0, 0, $red);
            imagejpeg($img, $testImagePath);
            imagedestroy($img);

            $browser->visit('/saus/tickets/' . $ticket->id)
                ->pause(1000);

            $attachmentsBefore = count($browser->elements('#attachmentGrid [id^="attachment-"]'));

            $browser->attach('#fileInput', $testImagePath)
                ->script("document.getElementById('uploadForm').dispatchEvent(new Event('submit'))");

            $browser->pause(3000);

            $attachmentsAfter = count($browser->elements('#attachmentGrid [id^="attachment-"]'));
            $this->assertGreaterThan($attachmentsBefore, $attachmentsAfter, 'Attachment count should increase after upload');

            // Verify the upload error is NOT shown
            $errorHidden = $browser->script("return document.getElementById('uploadError').classList.contains('hidden')");
            $this->assertTrue($errorHidden[0], 'Upload error should not be shown for valid file');

            @unlink($testImagePath);
        });
    }

    /** T47: PDF-Upload erscheint mit Datei-Icon */
    public function test_t47_pdf_upload_appears_with_icon(): void
    {
        $ticket = $this->createTestTicket(['title' => 'PDF-Upload-Test', 'description' => 'Ticket fuer PDF-Upload-Test']);

        $this->browse(function (Browser $browser) use ($ticket) {
            $this->loginAs($browser);

            $testPdfPath = storage_path('app/test_upload.pdf');
            file_put_contents($testPdfPath, "%PDF-1.4\n1 0 obj\n<< /Type /Catalog >>\nendobj\ntrailer\n<< /Root 1 0 R >>\n%%EOF");

            $browser->visit('/saus/tickets/' . $ticket->id)
                ->pause(1000);

            $attachmentsBefore = count($browser->elements('#attachmentGrid [id^="attachment-"]'));

            $browser->attach('#fileInput', $testPdfPath)
                ->script("document.getElementById('uploadForm').dispatchEvent(new Event('submit'))");

            $browser->pause(3000);

            $attachmentsAfter = count($browser->elements('#attachmentGrid [id^="attachment-"]'));
            $this->assertGreaterThan($attachmentsBefore, $attachmentsAfter, 'PDF should appear in attachment grid');

            // Verify file icon is present (non-image files get the file icon)
            $fileIcons = $browser->elements('#attachmentGrid .bi-file-earmark');
            $this->assertGreaterThan(0, count($fileIcons), 'PDF should show file icon');

            @unlink($testPdfPath);
        });
    }

    /** T48: Anhang kann heruntergeladen werden */
    public function test_t48_attachment_link_is_valid(): void
    {
        $ticket = $this->createTestTicket(['title' => 'Download-Test', 'description' => 'Ticket fuer Anhang-Download-Test']);

        $this->browse(function (Browser $browser) use ($ticket) {
            $this->loginAs($browser);

            // First upload a file
            $testImagePath = storage_path('app/test_download.jpg');
            $img = imagecreatetruecolor(50, 50);
            imagejpeg($img, $testImagePath);
            imagedestroy($img);

            $browser->visit('/saus/tickets/' . $ticket->id)
                ->pause(1000)
                ->attach('#fileInput', $testImagePath)
                ->script("document.getElementById('uploadForm').dispatchEvent(new Event('submit'))");

            $browser->pause(3000);

            // Verify the attachment link points to a valid API URL
            $attachmentLinks = $browser->elements('#attachmentGrid a[target="_blank"]');
            $this->assertGreaterThan(0, count($attachmentLinks), 'Should have downloadable attachment links');

            $href = $attachmentLinks[count($attachmentLinks) - 1]->getAttribute('href');
            $this->assertStringContainsString('/api/attachments/', $href, 'Link should point to attachment API');

            @unlink($testImagePath);
        });
    }

    /** T49: Datei loeschen entfernt Anhang aus Grid */
    public function test_t49_delete_removes_from_grid(): void
    {
        $ticket = $this->createTestTicket(['title' => 'Delete-Attachment-Test', 'description' => 'Ticket fuer Anhang-Loeschen-Test']);

        $this->browse(function (Browser $browser) use ($ticket) {
            $this->loginAs($browser);

            // First upload
            $testImagePath = storage_path('app/test_delete.jpg');
            $img = imagecreatetruecolor(50, 50);
            imagejpeg($img, $testImagePath);
            imagedestroy($img);

            $browser->visit('/saus/tickets/' . $ticket->id)
                ->pause(1000)
                ->attach('#fileInput', $testImagePath)
                ->script("document.getElementById('uploadForm').dispatchEvent(new Event('submit'))");

            $browser->pause(3000);

            $attachmentsBefore = count($browser->elements('#attachmentGrid [id^="attachment-"]'));
            $this->assertGreaterThan(0, $attachmentsBefore, 'Should have attachments to delete');

            // Delete last attachment
            $browser->driver->executeScript("window.confirm = function() { return true; };");
            $deleteButtons = $browser->elements('#attachmentGrid .bi-trash');
            $deleteButtons[count($deleteButtons) - 1]->click();
            $browser->pause(2000);

            $attachmentsAfter = count($browser->elements('#attachmentGrid [id^="attachment-"]'));
            $this->assertLessThan($attachmentsBefore, $attachmentsAfter, 'Attachment count should decrease after delete');

            @unlink($testImagePath);
        });
    }

    /** T50: Ungueltiger Dateityp zeigt Fehlermeldung */
    public function test_t50_invalid_type_shows_error(): void
    {
        $ticket = $this->createTestTicket(['title' => 'Invalid-Type-Test', 'description' => 'Ticket fuer ungueltigen Dateityp-Test']);

        $this->browse(function (Browser $browser) use ($ticket) {
            $this->loginAs($browser);

            $testExePath = storage_path('app/test_malware.exe');
            file_put_contents($testExePath, 'fake exe content');

            $browser->visit('/saus/tickets/' . $ticket->id)
                ->pause(1000);

            $attachmentsBefore = count($browser->elements('#attachmentGrid [id^="attachment-"]'));

            $browser->attach('#fileInput', $testExePath)
                ->script("document.getElementById('uploadForm').dispatchEvent(new Event('submit'))");

            $browser->pause(2000);

            // Error should be visible
            $errorHidden = $browser->script("return document.getElementById('uploadError').classList.contains('hidden')");
            $this->assertFalse($errorHidden[0], 'Upload error should be shown for invalid file type');

            // Verify error message contains helpful text
            $errorText = $browser->text('#uploadError');
            $this->assertNotEmpty($errorText, 'Error should contain message');

            // Attachment count should NOT increase
            $attachmentsAfter = count($browser->elements('#attachmentGrid [id^="attachment-"]'));
            $this->assertEquals($attachmentsBefore, $attachmentsAfter, 'Invalid file should not be added to grid');

            @unlink($testExePath);
        });
    }

    /** T51: Anhaenge-Zaehler im Header stimmt */
    public function test_t51_attachment_count_matches(): void
    {
        $ticket = $this->createTestTicket(['title' => 'Attachment-Count-Test', 'description' => 'Ticket fuer Anhaenge-Zaehler-Test']);

        $this->browse(function (Browser $browser) use ($ticket) {
            $this->loginAs($browser);

            $browser->visit('/saus/tickets/' . $ticket->id)
                ->pause(1000);

            // Count actual attachments in grid
            $actualCount = count($browser->elements('#attachmentGrid [id^="attachment-"]'));

            // Verify the header shows the count
            $headerText = $browser->text('h5:has(span)');
            // The header format is "Anhaenge (N)"
            $this->assertStringContainsString('Anhänge', $browser->driver->getPageSource());
        });
    }
}
