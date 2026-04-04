<?php

namespace Tests;

use App\Models\Comment;
use App\Models\ContactPerson;
use App\Models\Ticket;
use App\Models\TicketAttachment;
use App\Models\TicketStatus;
use App\Services\TicketNumberGenerator;
use Facebook\WebDriver\Chrome\ChromeOptions;
use Facebook\WebDriver\Remote\DesiredCapabilities;
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Str;
use Laravel\Dusk\TestCase as BaseTestCase;
use PHPUnit\Framework\Attributes\BeforeClass;

abstract class DuskTestCase extends BaseTestCase
{
    protected static bool $databaseSeeded = false;

    /**
     * Prepare for Dusk test execution.
     */
    #[BeforeClass]
    public static function prepare(): void
    {
        if (! static::runningInSail()) {
            static::startChromeDriver(['--port=9515']);
        }
    }

    protected function setUp(): void
    {
        parent::setUp();

        if (! static::$databaseSeeded) {
            Artisan::call('migrate', ['--force' => true]);
            Artisan::call('db:seed', ['--class' => 'Database\\Seeders\\TicketStatusSeeder', '--force' => true]);
            Artisan::call('db:seed', ['--class' => 'Database\\Seeders\\TestDataSeeder', '--force' => true]);
            static::$databaseSeeded = true;
        }
    }

    /**
     * Create a test ticket with optional data overrides.
     * Returns the Ticket model so tests can use $ticket->id.
     */
    protected function createTestTicket(array $overrides = []): Ticket
    {
        $statusId = $overrides['status_id']
            ?? TicketStatus::where('name', $overrides['status'] ?? 'offen')->value('id')
            ?? TicketStatus::active()->first()->id;
        unset($overrides['status']);

        $defaults = [
            'ticket_number' => (new TicketNumberGenerator())->generate(),
            'title' => 'Testticket ' . Str::random(8),
            'description' => 'Automatisch erstelltes Testticket',
            'status_id' => $statusId,
            'secret_string' => Str::random(50),
        ];

        return Ticket::create(array_merge($defaults, $overrides));
    }

    /**
     * Add a user comment to a ticket.
     */
    protected function addTestComment(Ticket $ticket, array $overrides = []): Comment
    {
        return Comment::create(array_merge([
            'ticket_id' => $ticket->id,
            'username' => 'Tester',
            'content' => 'Testkommentar ' . Str::random(8),
            'is_visible' => true,
        ], $overrides));
    }

    /**
     * Add an attachment record to a ticket (no actual file needed for DOM tests).
     */
    protected function addTestAttachment(Ticket $ticket, array $overrides = []): TicketAttachment
    {
        return TicketAttachment::create(array_merge([
            'ticket_id' => $ticket->id,
            'filename' => Str::random(16) . '.pdf',
            'original_filename' => 'testdatei.pdf',
            'file_type' => 'application/pdf',
            'file_size' => 1024,
            'uploaded_by' => 'Tester',
            'upload_date' => now(),
        ], $overrides));
    }

    /**
     * Create and link a contact person to a ticket.
     */
    protected function addTestContactPerson(Ticket $ticket, array $overrides = []): ContactPerson
    {
        $person = ContactPerson::create(array_merge([
            'name' => 'Testperson ' . Str::random(5),
            'email' => 'test' . Str::random(5) . '@example.com',
            'phone' => '0170-1234567',
            'is_active' => true,
        ], $overrides));
        $ticket->contactPersons()->attach($person->id);
        return $person;
    }

    /**
     * Create the RemoteWebDriver instance.
     */
    protected function driver(): RemoteWebDriver
    {
        $options = (new ChromeOptions)->addArguments(collect([
            $this->shouldStartMaximized() ? '--start-maximized' : '--window-size=1920,1080',
            '--disable-search-engine-choice-screen',
            '--disable-smooth-scrolling',
        ])->unless($this->hasHeadlessDisabled(), function (Collection $items) {
            return $items->merge([
                '--disable-gpu',
                '--headless=new',
            ]);
        })->all());

        return RemoteWebDriver::create(
            $_ENV['DUSK_DRIVER_URL'] ?? env('DUSK_DRIVER_URL') ?? 'http://localhost:9515',
            DesiredCapabilities::chrome()->setCapability(
                ChromeOptions::CAPABILITY, $options
            )
        );
    }
}
