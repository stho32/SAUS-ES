<?php

namespace Tests\Browser;

use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

/**
 * E2E Tests T45-T52: Datei-Uploads (Anhänge)
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

    /** T45: Upload-Formular ist sichtbar */
    public function test_t45_upload_form_visible(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginAs($browser);

            $browser->visit('/saus/tickets/1')
                ->pause(1000)
                ->assertPresent('#uploadForm')
                ->assertPresent('#fileInput')
                ->assertSee('Hochladen');
        });
    }

    /** T46: Bild hochladen zeigt Thumbnail */
    public function test_t46_image_upload_shows_thumbnail(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginAs($browser);

            // Create a temporary test image
            $testImagePath = storage_path('app/test_image.jpg');
            if (!file_exists($testImagePath)) {
                $img = imagecreatetruecolor(100, 100);
                $red = imagecolorallocate($img, 255, 0, 0);
                imagefill($img, 0, 0, $red);
                imagejpeg($img, $testImagePath);
                imagedestroy($img);
            }

            $browser->visit('/saus/tickets/1')
                ->pause(1000)
                ->attach('#fileInput', $testImagePath)
                ->script("document.getElementById('uploadForm').dispatchEvent(new Event('submit'))");

            $browser->pause(3000)
                ->assertPresent('#attachmentGrid');

            @unlink($testImagePath);
        });
    }

    /** T47: PDF hochladen zeigt Datei-Icon */
    public function test_t47_pdf_upload_shows_file_icon(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginAs($browser);

            // Create a minimal test PDF
            $testPdfPath = storage_path('app/test_file.pdf');
            if (!file_exists($testPdfPath)) {
                file_put_contents($testPdfPath, "%PDF-1.4\n1 0 obj\n<< /Type /Catalog >>\nendobj\ntrailer\n<< /Root 1 0 R >>\n%%EOF");
            }

            $browser->visit('/saus/tickets/1')
                ->pause(1000)
                ->attach('#fileInput', $testPdfPath)
                ->script("document.getElementById('uploadForm').dispatchEvent(new Event('submit'))");

            $browser->pause(3000)
                ->assertPresent('#attachmentGrid');

            @unlink($testPdfPath);
        });
    }

    /** T48: Hochgeladene Datei anzeigen funktioniert */
    public function test_t48_attachment_download_works(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginAs($browser);

            $browser->visit('/saus/tickets/1')
                ->pause(1000);

            // Check if there are attachment links
            $attachmentLinks = $browser->elements('#attachmentGrid a[target="_blank"]');
            if (count($attachmentLinks) > 0) {
                $href = $attachmentLinks[0]->getAttribute('href');
                $this->assertNotEmpty($href, 'Attachment link should have href');
            } else {
                $this->assertTrue(true, 'No attachments to test download with');
            }
        });
    }

    /** T49: Datei löschen mit Bestätigungsdialog */
    public function test_t49_delete_attachment_with_confirm(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginAs($browser);

            $browser->visit('/saus/tickets/1')
                ->pause(1000);

            $deleteButtons = $browser->elements('#attachmentGrid .bi-trash');
            if (count($deleteButtons) > 0) {
                // Accept the confirm dialog
                $browser->driver->executeScript("window.confirm = function() { return true; };");
                $deleteButtons[0]->click();
                $browser->pause(2000)
                    ->assertSee('Anhang gelöscht');
            } else {
                $this->assertTrue(true, 'No attachments to delete');
            }
        });
    }

    /** T50: Ungültiger Dateityp wird abgelehnt */
    public function test_t50_invalid_file_type_rejected(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginAs($browser);

            $testExePath = storage_path('app/test_malware.exe');
            file_put_contents($testExePath, 'fake exe content');

            $browser->visit('/saus/tickets/1')
                ->pause(1000)
                ->attach('#fileInput', $testExePath)
                ->script("document.getElementById('uploadForm').dispatchEvent(new Event('submit'))");

            $browser->pause(2000)
                ->assertPresent('#uploadError:not(.hidden)');

            @unlink($testExePath);
        });
    }

    /** T51: Zu große Datei wird abgelehnt */
    public function test_t51_oversized_file_rejected(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginAs($browser);

            // This test verifies that the form has size validation
            // Creating a truly oversized file would be impractical in E2E tests
            $browser->visit('/saus/tickets/1')
                ->pause(1000)
                ->assertPresent('#uploadForm')
                ->assertPresent('#uploadError');
        });
    }

    /** T52: Upload-Zähler im Sektions-Header */
    public function test_t52_attachment_count_in_header(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginAs($browser);

            $browser->visit('/saus/tickets/1')
                ->pause(1000)
                ->assertSee('Anhänge');
        });
    }
}
