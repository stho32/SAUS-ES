<?php
require_once 'includes/Database.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';

// Prüfe Master-Link oder Partner-Link
$partnerLink = $_GET['partner'] ?? null;
$partner = null;

if (!isset($_SESSION['master_code'])) {
    if ($partnerLink) {
        $partner = isPartnerLink($partnerLink);
        if (!$partner) {
            header('Location: error.php?type=invalid_partner');
            exit;
        }
    } else {
        requireMasterLink();
    }
}

// Hole Ticket-ID
$ticketId = $_GET['id'] ?? null;
if (!$ticketId) {
    header('Location: error.php?type=invalid_ticket');
    exit;
}

$db = Database::getInstance()->getConnection();

// Hole Ticket-Details
$stmt = $db->prepare("
    SELECT t.*, ts.name as status_name 
    FROM tickets t 
    JOIN ticket_status ts ON t.status_id = ts.id 
    WHERE t.id = ?
");
$stmt->execute([$ticketId]);
$ticket = $stmt->fetch();

if (!$ticket) {
    header('Location: error.php?type=invalid_ticket');
    exit;
}

// Hole Kommentare
$stmt = $db->prepare("
    SELECT * FROM comments 
    WHERE ticket_id = ? 
    ORDER BY created_at ASC
");
$stmt->execute([$ticketId]);
$comments = $stmt->fetchAll();

// Hole Abstimmungen
$votes = countVotes($ticketId);

// Verarbeite neue Kommentare
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $currentUsername = getCurrentUsername();
    if (!$currentUsername) {
        header('Location: index.php');
        exit;
    }

    if (isset($_POST['comment'])) {
        $comment = trim($_POST['comment']);
        if (!empty($comment)) {
            $stmt = $db->prepare("
                INSERT INTO comments (ticket_id, username, content)
                VALUES (?, ?, ?)
            ");
            $stmt->execute([$ticketId, $currentUsername, $comment]);
            
            // Aktualisiere die Kommentarliste
            header("Location: ticket_view.php?id=$ticketId" . ($partnerLink ? "&partner=$partnerLink" : ""));
            exit;
        }
    }
}

// Generiere Partner-Link
function generateNewPartnerLink($ticketId) {
    $db = Database::getInstance()->getConnection();
    $partnerLink = generatePartnerLink();
    
    $stmt = $db->prepare("
        INSERT INTO partners (ticket_id, partner_link)
        VALUES (?, ?)
    ");
    $stmt->execute([$ticketId, $partnerLink]);
    
    return $partnerLink;
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SAUS-ES - Ticket #<?= htmlspecialchars($ticket['ticket_number']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body>
    <?php if (!$partner): ?>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="index.php">SAUS-ES</a>
            <div class="navbar-text text-white">
                Angemeldet als: <?= htmlspecialchars(getCurrentUsername()) ?>
            </div>
        </div>
    </nav>
    <?php endif; ?>

    <div class="container mt-4">
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h2 class="mb-0">Ticket #<?= htmlspecialchars($ticket['ticket_number']) ?></h2>
                <span class="badge bg-<?= $ticket['status_name'] === 'geschlossen' ? 'secondary' : 'primary' ?>">
                    <?= htmlspecialchars($ticket['status_name']) ?>
                </span>
            </div>
            <div class="card-body">
                <h3><?= htmlspecialchars($ticket['title']) ?></h3>
                
                <div class="ki-summary mt-4">
                    <h4>KI-Zusammenfassung</h4>
                    <p><?= nl2br(htmlspecialchars($ticket['ki_summary'])) ?></p>
                </div>
                
                <?php if ($ticket['ki_interim']): ?>
                <div class="ki-summary mt-4">
                    <h4>Zwischenzusammenfassung</h4>
                    <p><?= nl2br(htmlspecialchars($ticket['ki_interim'])) ?></p>
                </div>
                <?php endif; ?>
                
                <?php if (!$partner): ?>
                <div class="mt-4">
                    <h4>Partner-Link generieren</h4>
                    <form method="post" class="mb-3">
                        <button type="submit" name="generate_partner" class="btn btn-secondary">
                            Neuen Partner-Link erstellen
                        </button>
                    </form>
                    
                    <?php
                    if (isset($_POST['generate_partner'])) {
                        $newLink = generateNewPartnerLink($ticketId);
                        $fullUrl = "http://{$_SERVER['HTTP_HOST']}/ticket_view.php?id=$ticketId&partner=$newLink";
                        echo '<div class="partner-link-box">';
                        echo '<p>Neuer Partner-Link:</p>';
                        echo '<input type="text" class="form-control" value="' . htmlspecialchars($fullUrl) . '" readonly>';
                        echo '<button class="btn btn-sm btn-primary mt-2 copy-link" data-link="' . htmlspecialchars($fullUrl) . '">Link kopieren</button>';
                        echo '</div>';
                    }
                    ?>
                </div>
                <?php endif; ?>
                
                <div class="votes mt-4">
                    <h4>Abstimmung</h4>
                    <div class="d-flex gap-3">
                        <div>
                            <button class="vote-button" data-ticket-id="<?= $ticketId ?>" data-vote-type="up">
                                👍 <span id="up-votes-<?= $ticketId ?>"><?= $votes['up_votes'] ?></span>
                            </button>
                        </div>
                        <div>
                            <button class="vote-button" data-ticket-id="<?= $ticketId ?>" data-vote-type="down">
                                👎 <span id="down-votes-<?= $ticketId ?>"><?= $votes['down_votes'] ?></span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-header">
                <h4 class="mb-0">Kommentare</h4>
            </div>
            <div class="card-body">
                <?php foreach ($comments as $comment): ?>
                <div class="comment">
                    <div class="comment-meta">
                        <strong><?= htmlspecialchars($comment['username']) ?></strong>
                        <span class="text-muted">am <?= formatDateTime($comment['created_at']) ?></span>
                    </div>
                    <div class="comment-content mt-2">
                        <?= nl2br(htmlspecialchars($comment['content'])) ?>
                    </div>
                </div>
                <?php endforeach; ?>
                
                <?php if ($currentUsername || $partner): ?>
                <form method="post" class="mt-4">
                    <div class="mb-3">
                        <label for="comment" class="form-label">Neuer Kommentar</label>
                        <textarea class="form-control" id="comment" name="comment" rows="3" required></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary">Kommentar hinzufügen</button>
                </form>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/main.js"></script>
</body>
</html>
