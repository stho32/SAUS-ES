<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\TicketStatus;
use Illuminate\Database\Seeder;

class TicketStatusSeeder extends Seeder
{
    /**
     * Seed the ticket_status table with all standard statuses.
     * Uses firstOrCreate to be idempotent.
     */
    public function run(): void
    {
        $statuses = [
            [
                'name' => 'offen',
                'description' => 'Ticket wurde erstellt und wartet auf Bearbeitung',
                'sort_order' => 10,
                'is_active' => true,
                'is_archived' => false,
                'is_closed' => false,
                'background_color' => '#90EE90',
                'filter_category' => 'in_bearbeitung',
            ],
            [
                'name' => 'in_bearbeitung',
                'description' => 'Ticket wird aktiv bearbeitet',
                'sort_order' => 20,
                'is_active' => true,
                'is_archived' => false,
                'is_closed' => false,
                'background_color' => '#FFFFE0',
                'filter_category' => 'in_bearbeitung',
            ],
            [
                'name' => 'zur_ueberpruefung',
                'description' => 'Ticket ist abgeschlossen, wartet auf Pruefung/Abnahme',
                'sort_order' => 30,
                'is_active' => true,
                'is_archived' => false,
                'is_closed' => false,
                'background_color' => '#FFD700',
                'filter_category' => 'in_bearbeitung',
            ],
            [
                'name' => 'warten_auf_feedback',
                'description' => 'Ticket wartet auf Rueckmeldung von anderen Personen',
                'sort_order' => 40,
                'is_active' => true,
                'is_archived' => false,
                'is_closed' => false,
                'background_color' => '#87CEEB',
                'filter_category' => 'in_bearbeitung',
            ],
            [
                'name' => 'wartet_auf_1892',
                'description' => 'Ticket wartet auf die Fertigstellung von Ticket 1892',
                'sort_order' => 45,
                'is_active' => true,
                'is_archived' => false,
                'is_closed' => false,
                'background_color' => '#DDA0DD',
                'filter_category' => 'in_bearbeitung',
            ],
            [
                'name' => 'zurueckgestellt',
                'description' => 'Ticket ist aktuell pausiert oder nicht priorisiert',
                'sort_order' => 50,
                'is_active' => true,
                'is_archived' => false,
                'is_closed' => false,
                'background_color' => '#F5DEB3',
                'filter_category' => 'zurueckgestellt',
            ],
            [
                'name' => 'verschoben',
                'description' => 'Ticket wurde auf einen spaeteren Zeitpunkt verschoben',
                'sort_order' => 60,
                'is_active' => true,
                'is_archived' => false,
                'is_closed' => false,
                'background_color' => '#FFEFD5',
                'filter_category' => 'in_bearbeitung',
            ],
            [
                'name' => 'gescheitert',
                'description' => 'Ticket wurde abgebrochen oder wird nicht weiterverfolgt',
                'sort_order' => 70,
                'is_active' => true,
                'is_archived' => false,
                'is_closed' => true,
                'background_color' => '#FFB6C1',
                'filter_category' => 'geschlossen',
            ],
            [
                'name' => 'abgelehnt',
                'description' => 'Ticket wurde abgelehnt und wird nicht umgesetzt',
                'sort_order' => 80,
                'is_active' => true,
                'is_archived' => false,
                'is_closed' => true,
                'background_color' => '#FA8072',
                'filter_category' => 'geschlossen',
            ],
            [
                'name' => 'archiviert',
                'description' => 'Ticket wurde archiviert',
                'sort_order' => 90,
                'is_active' => true,
                'is_archived' => true,
                'is_closed' => true,
                'background_color' => '#D3D3D3',
                'filter_category' => 'archiviert',
            ],
            // Inactive statuses for backwards compatibility
            [
                'name' => 'in_diskussion',
                'description' => 'Ticket wird diskutiert (veraltet)',
                'sort_order' => 100,
                'is_active' => false,
                'is_archived' => false,
                'is_closed' => false,
                'background_color' => '#B0C4DE',
                'filter_category' => 'in_bearbeitung',
            ],
            [
                'name' => 'abstimmung',
                'description' => 'Ticket ist in Abstimmung (veraltet)',
                'sort_order' => 110,
                'is_active' => false,
                'is_archived' => false,
                'is_closed' => false,
                'background_color' => '#E6E6FA',
                'filter_category' => 'in_bearbeitung',
            ],
        ];

        foreach ($statuses as $status) {
            TicketStatus::firstOrCreate(
                ['name' => $status['name']],
                $status
            );
        }
    }
}
