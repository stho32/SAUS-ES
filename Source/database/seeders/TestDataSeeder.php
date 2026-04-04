<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Comment;
use App\Models\CommentVote;
use App\Models\ContactPerson;
use App\Models\MasterLink;
use App\Models\News;
use App\Models\Partner;
use App\Models\Ticket;
use App\Models\TicketAttachment;
use App\Models\TicketStatus;
use App\Models\TicketVote;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class TestDataSeeder extends Seeder
{
    /**
     * Seed the database with realistic test data for a German housing cooperative.
     */
    public function run(): void
    {
        $this->seedMasterLink();
        $tickets = $this->seedTickets();
        $this->seedComments($tickets);
        $this->seedTicketVotes($tickets);
        $this->seedAttachments($tickets);
        $this->seedPartners($tickets);
        $this->seedContactPersons($tickets);
        $this->seedNews();
    }

    /**
     * Create the test master link.
     */
    private function seedMasterLink(): void
    {
        MasterLink::firstOrCreate(
            ['link_code' => 'test_master_2025'],
            [
                'description' => 'Test Master-Link',
                'is_active' => true,
            ]
        );
    }

    /**
     * Create realistic test tickets for a housing cooperative.
     *
     * @return \Illuminate\Support\Collection<int, Ticket>
     */
    private function seedTickets(): \Illuminate\Support\Collection
    {
        $statuses = TicketStatus::where('is_active', true)->pluck('id', 'name');
        $assignees = ['SH', 'MK', 'TP', 'SH, MK', 'TP + SH', null];

        $ticketData = [
            [
                'ticket_number' => '20250110-0001',
                'title' => 'Feuchtigkeitsschaden Keller Haus 3',
                'description' => 'Im Kellergeschoss von Haus 3 wurde ein erheblicher Feuchtigkeitsschaden festgestellt. Die Wand im Bereich der Waschkueche zeigt deutliche Feuchtespuren und beginnende Schimmelbildung. Eine Begutachtung durch einen Fachbetrieb ist dringend erforderlich.',
                'status' => 'in_bearbeitung',
                'assignee' => 'SH',
                'show_on_website' => true,
                'public_comment' => 'Fachfirma wurde beauftragt. Termin zur Begutachtung steht aus.',
                'affected_neighbors' => 8,
                'follow_up_date' => '2025-02-15',
            ],
            [
                'ticket_number' => '20250110-0002',
                'title' => 'Laermbelaestigung durch Baustelle Nachbargrundstueck',
                'description' => 'Mehrere Bewohner von Haus 1 und 2 beschweren sich ueber andauernde Laermbelaestigung durch die Baustelle auf dem Nachbargrundstueck. Der Laerm beginnt teilweise vor 7 Uhr morgens und haelt bis in die Abendstunden an.',
                'status' => 'offen',
                'assignee' => 'MK',
                'show_on_website' => true,
                'public_comment' => 'Wir haben die zustaendige Baufirma kontaktiert.',
                'affected_neighbors' => 24,
            ],
            [
                'ticket_number' => '20250112-0001',
                'title' => 'Defekte Aussenbeleuchtung Parkplatz',
                'description' => 'Die Aussenbeleuchtung auf dem Parkplatz hinter Haus 4 ist seit mehreren Tagen defekt. Drei von fuenf Laternen funktionieren nicht mehr. Sicherheitsbedenken wurden geaeussert.',
                'status' => 'zur_ueberpruefung',
                'assignee' => 'TP',
                'show_on_website' => false,
                'affected_neighbors' => 12,
            ],
            [
                'ticket_number' => '20250113-0001',
                'title' => 'Heizungsausfall 2. OG Haus 1',
                'description' => 'In der zweiten Etage von Haus 1 funktioniert die Heizung in drei Wohnungen nicht mehr richtig. Die Heizkoerper werden nur lauwarm. Betroffen sind die Wohnungen 2.1, 2.2 und 2.3.',
                'status' => 'warten_auf_feedback',
                'assignee' => 'SH, MK',
                'show_on_website' => true,
                'public_comment' => 'Der Heizungsmonteur war vor Ort. Wir warten auf die Ersatzteile.',
                'affected_neighbors' => 6,
                'follow_up_date' => '2025-01-25',
            ],
            [
                'ticket_number' => '20250114-0001',
                'title' => 'Muelltonnen werden nicht regelmaessig geleert',
                'description' => 'Seit Anfang Januar werden die Muelltonnen (Restmuell und Gelber Sack) nicht mehr regelmaessig durch die Entsorgungsfirma geleert. Es kommt zu Ueberlaeufen und Verschmutzung des Muellplatzes.',
                'status' => 'in_bearbeitung',
                'assignee' => 'MK',
                'show_on_website' => false,
                'affected_neighbors' => 40,
            ],
            [
                'ticket_number' => '20250114-0002',
                'title' => 'Antrag auf Spielplatzrenovierung',
                'description' => 'Der Spielplatz zwischen Haus 2 und 3 ist in die Jahre gekommen. Mehrere Spielgeraete sind beschaedigt oder veraltet. Es wird beantragt, den Spielplatz grundlegend zu renovieren.',
                'status' => 'wartet_auf_1892',
                'assignee' => 'TP + SH',
                'show_on_website' => true,
                'public_comment' => 'Wird im Rahmen der Gesamtsanierung mitgeplant.',
                'affected_neighbors' => 15,
            ],
            [
                'ticket_number' => '20250115-0001',
                'title' => 'Wasserschaden Dachgeschoss Haus 5',
                'description' => 'Nach dem letzten Starkregen wurde ein Wasserschaden im Dachgeschoss von Haus 5 festgestellt. Die Decke in Wohnung 4.2 zeigt Wasserflecken und es tropft bei starkem Regen.',
                'status' => 'in_bearbeitung',
                'assignee' => 'SH',
                'show_on_website' => false,
                'affected_neighbors' => 2,
                'follow_up_date' => '2025-02-01',
            ],
            [
                'ticket_number' => '20250115-0002',
                'title' => 'Treppenhaus-Renovierung Haus 2',
                'description' => 'Das Treppenhaus in Haus 2 muss dringend renoviert werden. Die Waende sind stark abgenutzt, der Handlauf ist teilweise lose, und die Beleuchtung ist unzureichend.',
                'status' => 'zurueckgestellt',
                'assignee' => null,
                'show_on_website' => false,
                'affected_neighbors' => 16,
            ],
            [
                'ticket_number' => '20250116-0001',
                'title' => 'Falschparker auf Genossenschaftsparkplaetzen',
                'description' => 'Immer wieder parken fremde Fahrzeuge auf den Parkplaetzen der Genossenschaft. Besonders betroffen ist der Parkplatz vor Haus 1. Bewohner koennen teilweise ihre eigenen Stellplaetze nicht nutzen.',
                'status' => 'verschoben',
                'assignee' => 'MK',
                'show_on_website' => false,
            ],
            [
                'ticket_number' => '20250116-0002',
                'title' => 'Schimmel in Wohnung 1.3 Haus 4',
                'description' => 'In der Wohnung 1.3 von Haus 4 wurde Schimmelbefall im Badezimmer und in der Kueche festgestellt. Der Mieter hat den Schaden gemeldet und bittet um umgehende Behebung.',
                'status' => 'in_bearbeitung',
                'assignee' => 'SH',
                'show_on_website' => false,
                'affected_neighbors' => 1,
                'follow_up_date' => '2025-01-30',
            ],
            [
                'ticket_number' => '20250117-0001',
                'title' => 'Erneuerung der Briefkastenanlage Haus 3',
                'description' => 'Die Briefkastenanlage im Eingangsbereich von Haus 3 ist veraltet und teilweise defekt. Mehrere Briefkaesten lassen sich nicht mehr richtig schliessen. Eine Erneuerung wird beantragt.',
                'status' => 'offen',
                'assignee' => null,
                'show_on_website' => false,
            ],
            [
                'ticket_number' => '20250117-0002',
                'title' => 'Glasfaseranschluss fuer alle Haeuser',
                'description' => 'Antrag auf Pruefung der Moeglichkeit eines Glasfaseranschlusses fuer alle Gebaeude der Genossenschaft. Mehrere Bewohner haben Interesse signalisiert.',
                'status' => 'warten_auf_feedback',
                'assignee' => 'TP',
                'show_on_website' => true,
                'public_comment' => 'Anfrage beim Netzbetreiber laeuft.',
                'affected_neighbors' => 80,
            ],
            [
                'ticket_number' => '20250118-0001',
                'title' => 'Reparatur Aufzug Haus 6',
                'description' => 'Der Aufzug in Haus 6 faellt immer wieder aus. In den letzten zwei Wochen gab es drei Ausfaelle. Besonders fuer aeltere Bewohner ist dies ein erhebliches Problem.',
                'status' => 'gescheitert',
                'assignee' => 'SH',
                'show_on_website' => false,
                'affected_neighbors' => 20,
                'do_not_track' => true,
            ],
            [
                'ticket_number' => '20250118-0002',
                'title' => 'Anfrage Baumfaellung Innenhof',
                'description' => 'Ein grosser Baum im Innenhof zwischen Haus 2 und 3 ist laut Gutachten nicht mehr standsicher. Es wird die Faellung und Neupflanzung beantragt.',
                'status' => 'abgelehnt',
                'assignee' => 'MK',
                'show_on_website' => false,
            ],
            [
                'ticket_number' => '20250119-0001',
                'title' => 'Sanierung Kellerabgaenge Haus 1-3',
                'description' => 'Die Kellerabgaenge der Haeuser 1 bis 3 weisen erhebliche Schaeden auf. Stufen sind abgebrochen, Gelaender sind rostig und die Beleuchtung fehlt teilweise.',
                'status' => 'archiviert',
                'assignee' => 'TP + SH',
                'show_on_website' => false,
                'do_not_track' => true,
            ],
            [
                'ticket_number' => '20250120-0001',
                'title' => 'Einrichtung Fahrradabstellplaetze Haus 4',
                'description' => 'Bewohner von Haus 4 wuenschen sich ueberdachte Fahrradabstellplaetze. Aktuell werden Fahrraeder im Treppenhaus oder im Keller abgestellt, was zu Platzproblemen fuehrt.',
                'status' => 'offen',
                'assignee' => null,
                'show_on_website' => true,
                'public_comment' => 'Vorschlag wird in der naechsten Versammlung besprochen.',
                'affected_neighbors' => 12,
            ],
            [
                'ticket_number' => '20250120-0002',
                'title' => 'Undichte Fenster EG Haus 5',
                'description' => 'In mehreren Erdgeschosswohnungen von Haus 5 sind die Fenster undicht. Es zieht deutlich und die Heizkosten steigen. Betroffen sind die Wohnungen 0.1, 0.2 und 0.4.',
                'status' => 'in_bearbeitung',
                'assignee' => 'SH',
                'show_on_website' => false,
                'affected_neighbors' => 6,
                'follow_up_date' => '2025-02-10',
            ],
            [
                'ticket_number' => '20250121-0001',
                'title' => 'Gruenpflege Aussenanlage vernachlaessigt',
                'description' => 'Die Aussenanlagen der Genossenschaft werden seit einigen Monaten nicht mehr ausreichend gepflegt. Hecken sind ueberwachsen, Rasenflaechen ungepflegt und Beete verwildert.',
                'status' => 'zur_ueberpruefung',
                'assignee' => 'MK',
                'show_on_website' => false,
                'affected_neighbors' => 60,
            ],
            [
                'ticket_number' => '20250122-0001',
                'title' => 'Rauchmelder-Wartung faellig in allen Hauesern',
                'description' => 'Die jaehrliche Wartung der Rauchmelder steht an. Laut Gesetz muessen alle Rauchmelder in Schlafzimmern, Kinderzimmern und Fluren geprueft werden. Betrifft saemtliche Wohnungen in Haus 1 bis 6.',
                'status' => 'offen',
                'assignee' => 'TP',
                'show_on_website' => true,
                'public_comment' => 'Termine werden in Kuerze per Aushang bekanntgegeben.',
                'affected_neighbors' => 96,
            ],
            [
                'ticket_number' => '20250122-0002',
                'title' => 'Waschmaschinenanschluss Gemeinschaftskeller Haus 2',
                'description' => 'Der Waschmaschinenanschluss im Gemeinschaftskeller von Haus 2 ist defekt. Wasser tritt an der Anschlussstelle aus. Mehrere Bewohner koennen ihre Waschmaschinen nicht nutzen.',
                'status' => 'in_bearbeitung',
                'assignee' => 'SH',
                'show_on_website' => false,
                'affected_neighbors' => 8,
                'follow_up_date' => '2025-02-05',
            ],
        ];

        $tickets = collect();

        foreach ($ticketData as $data) {
            $statusName = $data['status'];
            unset($data['status']);

            $data['status_id'] = $statuses[$statusName] ?? $statuses['offen'];
            $data['do_not_track'] = $data['do_not_track'] ?? 0;
            $data['secret_string'] = Str::random(50);

            $ticket = Ticket::firstOrCreate(
                ['ticket_number' => $data['ticket_number']],
                $data
            );

            // System-Kommentar wie vom Controller erstellt
            $statusName = $statuses->search($data['status_id']);
            Comment::firstOrCreate([
                'ticket_id' => $ticket->id,
                'username' => 'System',
                'content' => "Ticket erstellt mit Status: {$statusName}",
            ]);

            $tickets->push($ticket);
        }

        return $tickets;
    }

    /**
     * Create realistic comments on tickets.
     *
     * @param \Illuminate\Support\Collection<int, Ticket> $tickets
     */
    private function seedComments(\Illuminate\Support\Collection $tickets): void
    {
        $usernames = ['SH', 'MK', 'TP', 'BW', 'KL', 'RS', 'HM', 'JF'];

        $commentTemplates = [
            'Ich habe den Schaden persoenlich begutachtet. Die Situation ist ernst und erfordert zeitnahes Handeln.',
            'Kostenangebot liegt vor. Ich leite es zur Pruefung weiter.',
            'Der Handwerker war heute vor Ort und hat sich die Sache angesehen.',
            'Rueckmeldung vom Mieter: Das Problem besteht weiterhin.',
            'Habe die Hausverwaltung informiert. Wir warten auf Freigabe.',
            'Termin mit der Fachfirma ist fuer naechste Woche vereinbart.',
            'Die Kosten belaufen sich voraussichtlich auf ca. 2.500 EUR.',
            'Bitte um Stellungnahme der Betroffenen bis Ende der Woche.',
            'Zwischenbericht: Arbeiten laufen planmaessig.',
            'Der Vorstand hat dem Antrag in der letzten Sitzung zugestimmt.',
            'Alternativvorschlag: Wir koennten auch eine guenstigere Loesung in Betracht ziehen.',
            'Die betroffenen Nachbarn wurden informiert und sind einverstanden.',
            'Nachtrag: Es sind weitere Schaeden aufgefallen, die mitbehoben werden sollten.',
            'Bitte Prioritaet erhoehen - es gibt inzwischen mehrere Beschwerden.',
            'Habe Fotos vom aktuellen Zustand gemacht und angehaengt.',
            'Der Sachverstaendige empfiehlt eine umfassende Sanierung statt Einzelmassnahmen.',
            'Abstimmung mit den anderen Bearbeitern steht noch aus.',
            'Fortschritt: 60% der Arbeiten sind abgeschlossen.',
            'Mieter in Wohnung 2.3 hat sich bedankt - Problem ist fuer ihn behoben.',
            'Bitte um Verlaengerung der Frist - der Lieferant hat Lieferengpaesse.',
            'Vergleichsangebote eingeholt: Firma Mueller ist am guenstigsten.',
            'Die Massnahme wurde erfolgreich abgeschlossen.',
            'Achtung: Bei der Besichtigung wurde ein Folgeschaden entdeckt.',
            'Telefonat mit Frau Schmidt von der Hausverwaltung: Sie kuemmert sich.',
            'Update: Material wurde bestellt, Lieferung voraussichtlich in 2 Wochen.',
            'Die Bewohnerversammlung hat das Thema ausfuehrlich diskutiert.',
            'Empfehlung: Wir sollten hier langfristig denken und nicht nur flicken.',
            'Habe den Vorgang an TP uebergeben, da ich naechste Woche im Urlaub bin.',
            'Pruefung abgeschlossen. Alles in Ordnung, kann geschlossen werden.',
            'Feedback der Bewohner ist durchweg positiv. Gute Arbeit!',
            'Die Versicherung hat den Schaden anerkannt und uebernimmt 80% der Kosten.',
            'Neue Entwicklung: Der Vermieter des Nachbargrundstuecks ist gespraechsbereit.',
            'Bitte die Dringlichkeit anpassen - es betrifft inzwischen weitere Wohnungen.',
            'Das Angebot ist angemessen. Ich empfehle die Beauftragung.',
            'Hinweis: Fuer diese Massnahme benoetigen wir eine Genehmigung der Gemeinde.',
        ];

        $commentIndex = 0;

        foreach ($tickets as $ticket) {
            // Each ticket gets 2-4 comments
            $numComments = rand(1, 4);

            for ($i = 0; $i < $numComments; $i++) {
                $username = $usernames[array_rand($usernames)];
                $content = $commentTemplates[$commentIndex % count($commentTemplates)];
                $commentIndex++;

                $daysAfterCreation = $i * rand(1, 3);
                $createdAt = now()->subDays(rand(30, 90))->addDays($daysAfterCreation);

                $comment = Comment::create([
                    'ticket_id' => $ticket->id,
                    'username' => $username,
                    'content' => $content,
                    'is_visible' => rand(1, 10) > 1, // 90% visible
                    'hidden_by' => rand(1, 10) === 1 ? 'SH' : null,
                    'hidden_at' => rand(1, 10) === 1 ? now()->subDays(rand(1, 20)) : null,
                    'is_edited' => rand(1, 5) === 1, // 20% edited
                    'created_at' => $createdAt,
                ]);

                // Add votes to some comments
                $this->seedCommentVotes($comment, $usernames);
            }
        }
    }

    /**
     * Add votes to a comment.
     */
    private function seedCommentVotes(Comment $comment, array $usernames): void
    {
        // 60% of comments get votes
        if (rand(1, 10) > 6) {
            return;
        }

        $numVotes = rand(1, 4);
        $votedUsers = [];

        for ($i = 0; $i < $numVotes; $i++) {
            $username = $usernames[array_rand($usernames)];

            // Skip if user already voted on this comment or is the comment author
            if (in_array($username, $votedUsers) || $username === $comment->username) {
                continue;
            }

            $votedUsers[] = $username;

            CommentVote::firstOrCreate(
                [
                    'comment_id' => $comment->id,
                    'username' => $username,
                ],
                [
                    'value' => rand(1, 3) > 1 ? 'up' : 'down', // 67% up, 33% down
                ]
            );
        }
    }

    /**
     * Add votes to tickets.
     *
     * @param \Illuminate\Support\Collection<int, Ticket> $tickets
     */
    private function seedTicketVotes(\Illuminate\Support\Collection $tickets): void
    {
        $usernames = ['SH', 'MK', 'TP', 'BW', 'KL', 'RS'];

        foreach ($tickets as $ticket) {
            // 50% of tickets get votes
            if (rand(1, 2) === 1) {
                continue;
            }

            $numVotes = rand(1, 4);
            $votedUsers = [];

            for ($i = 0; $i < $numVotes; $i++) {
                $username = $usernames[array_rand($usernames)];

                if (in_array($username, $votedUsers)) {
                    continue;
                }

                $votedUsers[] = $username;

                TicketVote::firstOrCreate(
                    [
                        'ticket_id' => $ticket->id,
                        'username' => $username,
                    ],
                    [
                        'value' => rand(1, 4) > 1 ? 'up' : 'down', // 75% up, 25% down
                    ]
                );
            }
        }
    }

    /**
     * Create attachment records for some tickets (metadata only, no actual files).
     *
     * @param \Illuminate\Support\Collection<int, Ticket> $tickets
     */
    private function seedAttachments(\Illuminate\Support\Collection $tickets): void
    {
        $attachmentData = [
            [
                'ticket_index' => 0,
                'filename' => 'feuchtigkeitsschaden_keller_001.jpg',
                'original_filename' => 'Feuchtigkeitsschaden_Keller_Foto1.jpg',
                'file_type' => 'image/jpeg',
                'file_size' => 2457600,
                'uploaded_by' => 'SH',
            ],
            [
                'ticket_index' => 0,
                'filename' => 'feuchtigkeitsschaden_keller_002.jpg',
                'original_filename' => 'Feuchtigkeitsschaden_Keller_Foto2.jpg',
                'file_type' => 'image/jpeg',
                'file_size' => 1843200,
                'uploaded_by' => 'SH',
            ],
            [
                'ticket_index' => 0,
                'filename' => 'gutachten_feuchtigkeit_haus3.pdf',
                'original_filename' => 'Gutachten_Feuchtigkeit_Haus3.pdf',
                'file_type' => 'application/pdf',
                'file_size' => 524288,
                'uploaded_by' => 'MK',
            ],
            [
                'ticket_index' => 2,
                'filename' => 'parkplatz_beleuchtung_defekt.jpg',
                'original_filename' => 'Parkplatz_Beleuchtung_defekt.jpg',
                'file_type' => 'image/jpeg',
                'file_size' => 3145728,
                'uploaded_by' => 'TP',
            ],
            [
                'ticket_index' => 3,
                'filename' => 'heizung_kostenvoranschlag.pdf',
                'original_filename' => 'Kostenvoranschlag_Heizungsreparatur.pdf',
                'file_type' => 'application/pdf',
                'file_size' => 215040,
                'uploaded_by' => 'SH',
            ],
            [
                'ticket_index' => 5,
                'filename' => 'spielplatz_zustand_2025.jpg',
                'original_filename' => 'Spielplatz_aktueller_Zustand.jpg',
                'file_type' => 'image/jpeg',
                'file_size' => 4194304,
                'uploaded_by' => 'TP',
            ],
            [
                'ticket_index' => 6,
                'filename' => 'wasserschaden_dach_haus5.jpg',
                'original_filename' => 'Wasserschaden_Dachgeschoss_Haus5.jpg',
                'file_type' => 'image/jpeg',
                'file_size' => 2097152,
                'uploaded_by' => 'SH',
            ],
            [
                'ticket_index' => 9,
                'filename' => 'schimmel_wohnung_1_3.jpg',
                'original_filename' => 'Schimmelbefall_Bad_Wohnung1-3.jpg',
                'file_type' => 'image/jpeg',
                'file_size' => 1572864,
                'uploaded_by' => 'SH',
            ],
            [
                'ticket_index' => 9,
                'filename' => 'schimmel_kueche_1_3.jpg',
                'original_filename' => 'Schimmelbefall_Kueche_Wohnung1-3.jpg',
                'file_type' => 'image/jpeg',
                'file_size' => 1835008,
                'uploaded_by' => 'SH',
            ],
            [
                'ticket_index' => 11,
                'filename' => 'glasfaser_angebot_telekom.pdf',
                'original_filename' => 'Glasfaser_Angebot_Telekom_2025.pdf',
                'file_type' => 'application/pdf',
                'file_size' => 348160,
                'uploaded_by' => 'TP',
            ],
            [
                'ticket_index' => 14,
                'filename' => 'kellerabgang_schaeden_dokumentation.pdf',
                'original_filename' => 'Kellerabgang_Schadensdokumentation.pdf',
                'file_type' => 'application/pdf',
                'file_size' => 1048576,
                'uploaded_by' => 'TP',
            ],
            [
                'ticket_index' => 16,
                'filename' => 'fenster_eg_haus5_foto.jpg',
                'original_filename' => 'Undichte_Fenster_EG_Haus5.jpg',
                'file_type' => 'image/jpeg',
                'file_size' => 2621440,
                'uploaded_by' => 'SH',
            ],
        ];

        foreach ($attachmentData as $data) {
            if (isset($tickets[$data['ticket_index']])) {
                TicketAttachment::firstOrCreate(
                    [
                        'ticket_id' => $tickets[$data['ticket_index']]->id,
                        'filename' => $data['filename'],
                    ],
                    [
                        'original_filename' => $data['original_filename'],
                        'file_type' => $data['file_type'],
                        'file_size' => $data['file_size'],
                        'uploaded_by' => $data['uploaded_by'],
                        'upload_date' => now()->subDays(rand(20, 80)),
                    ]
                );
            }
        }
    }

    /**
     * Create partners for some tickets.
     *
     * @param \Illuminate\Support\Collection<int, Ticket> $tickets
     */
    private function seedPartners(\Illuminate\Support\Collection $tickets): void
    {
        $partnerData = [
            [
                'partner_name' => 'Frau Mueller',
                'ticket_index' => 0,
            ],
            [
                'partner_name' => 'Herr Weber',
                'ticket_index' => 0,
            ],
            [
                'partner_name' => 'Herr Schmidt',
                'ticket_index' => 3,
            ],
            [
                'partner_name' => 'Frau Klein',
                'ticket_index' => 5,
            ],
            [
                'partner_name' => 'Herr Bauer',
                'ticket_index' => 5,
            ],
        ];

        foreach ($partnerData as $data) {
            if (isset($tickets[$data['ticket_index']])) {
                Partner::firstOrCreate(
                    ['partner_link' => 'partner_' . Str::slug($data['partner_name']) . '_' . $tickets[$data['ticket_index']]->id],
                    [
                        'ticket_id' => $tickets[$data['ticket_index']]->id,
                        'partner_name' => $data['partner_name'],
                        'is_master' => false,
                    ]
                );
            }
        }
    }

    /**
     * Create contact persons and link some to tickets.
     *
     * @param \Illuminate\Support\Collection<int, Ticket> $tickets
     */
    private function seedContactPersons(\Illuminate\Support\Collection $tickets): void
    {
        $contacts = [
            [
                'name' => 'Thomas Bergmann',
                'email' => 'bergmann@hausverwaltung-muster.de',
                'phone' => '030 12345-100',
                'contact_notes' => 'Hauptansprechpartner der Hausverwaltung',
                'responsibility_notes' => 'Allgemeine Verwaltung, Mietvertraege',
                'is_active' => true,
            ],
            [
                'name' => 'Sabine Koenig',
                'email' => 'koenig@stadtwerke-muster.de',
                'phone' => '030 12345-200',
                'contact_notes' => 'Ansprechpartnerin fuer Heizung und Warmwasser',
                'responsibility_notes' => 'Energieversorgung, Heizungsanlagen, Wartung',
                'is_active' => true,
            ],
            [
                'name' => 'Michael Richter',
                'email' => 'richter@handwerk-richter.de',
                'phone' => '0170 9876543',
                'contact_notes' => 'Unser Stamm-Handwerker fuer Sanitaer und Heizung',
                'responsibility_notes' => 'Sanitaerinstallation, Heizungsreparatur, Notdienst',
                'is_active' => true,
            ],
            [
                'name' => 'Andrea Hoffmann',
                'email' => 'hoffmann@rechtsanwalt-hoffmann.de',
                'phone' => '030 12345-300',
                'contact_notes' => 'Rechtsberatung bei Mietstreitigkeiten',
                'responsibility_notes' => 'Mietrecht, Vertragsangelegenheiten',
                'is_active' => true,
            ],
            [
                'name' => 'Klaus Zimmer',
                'email' => 'zimmer@elektro-zimmer.de',
                'phone' => '0151 2345678',
                'contact_notes' => 'Elektrofachbetrieb fuer alle Haeuser',
                'responsibility_notes' => 'Elektroinstallation, Beleuchtung, Aufzugswartung',
                'is_active' => true,
            ],
            [
                'name' => 'Petra Neumann',
                'email' => null,
                'phone' => '030 12345-400',
                'contact_notes' => 'Ehemalige Ansprechpartnerin Gruenflaechen (im Ruhestand)',
                'responsibility_notes' => 'Gartenpflege, Aussenanlagen',
                'is_active' => false,
            ],
            [
                'name' => 'Frank Dietrich',
                'email' => 'dietrich@gala-dietrich.de',
                'phone' => '0160 1112233',
                'contact_notes' => 'Neuer Garten- und Landschaftsbauer',
                'responsibility_notes' => 'Gruenpflege, Baumschnitt, Aussenanlagen',
                'is_active' => true,
            ],
            [
                'name' => 'Ursula Wagner',
                'email' => 'wagner@schornsteinfeger-wagner.de',
                'phone' => '030 12345-500',
                'contact_notes' => 'Bezirksschornsteinfegerin',
                'responsibility_notes' => 'Schornsteinfegerarbeiten, Abgasmessung, Brandschutz',
                'is_active' => true,
            ],
        ];

        $contactModels = [];
        foreach ($contacts as $contact) {
            $contactModels[] = ContactPerson::firstOrCreate(
                ['name' => $contact['name']],
                $contact
            );
        }

        // Link contacts to tickets (the junction table)
        $ticketContactLinks = [
            [0, 0], // Ticket 1 <-> Bergmann (Hausverwaltung)
            [0, 2], // Ticket 1 <-> Richter (Sanitaer)
            [2, 4], // Ticket 3 <-> Zimmer (Elektro)
            [3, 2], // Ticket 4 <-> Richter (Heizung)
            [3, 1], // Ticket 4 <-> Koenig (Stadtwerke)
            [6, 2], // Ticket 7 <-> Richter (Wasserschaden)
            [9, 2], // Ticket 10 <-> Richter (Schimmel/Sanitaer)
            [11, 1], // Ticket 12 <-> Koenig (Glasfaser-Anfrage)
            [12, 4], // Ticket 13 <-> Zimmer (Aufzug)
            [16, 2], // Ticket 17 <-> Richter (Fenster)
            [17, 6], // Ticket 18 <-> Dietrich (Gruenpflege)
        ];

        foreach ($ticketContactLinks as [$ticketIndex, $contactIndex]) {
            if (isset($tickets[$ticketIndex]) && isset($contactModels[$contactIndex])) {
                $tickets[$ticketIndex]->contactPersons()->syncWithoutDetaching([
                    $contactModels[$contactIndex]->id,
                ]);
            }
        }
    }

    /**
     * Create news articles.
     */
    private function seedNews(): void
    {
        $newsData = [
            [
                'title' => 'Fruehjahrputz in der Genossenschaft',
                'content' => 'Liebe Bewohnerinnen und Bewohner, am 15. Maerz findet unser gemeinsamer Fruehjahrsputz statt. Wir treffen uns um 9:00 Uhr auf dem Innenhof zwischen Haus 2 und 3. Handschuhe und Muellsaecke werden gestellt. Fuer Getraenke und einen kleinen Imbiss ist gesorgt. Wir freuen uns auf zahlreiche Teilnahme!',
                'event_date' => '2025-03-15',
                'created_by' => 'SH',
            ],
            [
                'title' => 'Mitgliederversammlung 2025',
                'content' => 'Die jaehrliche Mitgliederversammlung findet am 20. April 2025 um 18:00 Uhr im Gemeinschaftsraum von Haus 1 statt. Tagesordnungspunkte: Jahresbericht 2024, Wirtschaftsplan 2025, Sanierungsmassnahmen, Verschiedenes. Bitte bringen Sie Ihren Mitgliedsausweis mit.',
                'event_date' => '2025-04-20',
                'created_by' => 'MK',
            ],
            [
                'title' => 'Neue Muelltrennungsregelung ab Februar',
                'content' => 'Ab dem 1. Februar gelten neue Regelungen fuer die Muelltrennung in unserer Genossenschaft. Die wichtigste Aenderung: Biomuell wird ab sofort in separaten braunen Tonnen gesammelt. Bitte beachten Sie die neuen Aushange an den Muellplaetzen. Bei Fragen wenden Sie sich bitte an die Hausverwaltung.',
                'event_date' => '2025-02-01',
                'created_by' => 'SH',
            ],
            [
                'title' => 'Sanierung der Fassade Haus 3 beginnt',
                'content' => 'Ab dem 10. Februar starten die lang geplanten Fassadensanierungsarbeiten an Haus 3. Die Arbeiten werden voraussichtlich 8 Wochen dauern. Waehrend dieser Zeit wird ein Geruest aufgestellt. Bitte halten Sie die Fenster in den betroffenen Bereichen geschlossen, wenn Arbeiten stattfinden. Wir bitten um Ihr Verstaendnis.',
                'event_date' => '2025-02-10',
                'created_by' => 'TP',
            ],
            [
                'title' => 'Sommerfest der Genossenschaft',
                'content' => 'Wir laden alle Bewohnerinnen und Bewohner herzlich zu unserem Sommerfest am 21. Juni ein! Ab 14:00 Uhr gibt es Kaffee und Kuchen, ab 17:00 Uhr Grillgut und Getraenke. Fuer die Kinder gibt es eine Huepfburg und Kinderschminken. Bitte melden Sie sich bis zum 15. Juni bei der Hausverwaltung an.',
                'event_date' => '2025-06-21',
                'created_by' => 'MK',
            ],
        ];

        foreach ($newsData as $data) {
            News::firstOrCreate(
                ['title' => $data['title']],
                $data
            );
        }
    }
}
