<?php
declare(strict_types=1);
/**
 * News Management Page
 * Implements REQ0009: News Management and Display
 */

require_once 'includes/auth_check.php';
require_once 'includes/Database.php';
require_once 'includes/auth.php';

// Check authentication
requireMasterLink();

$currentUsername = getCurrentUsername();
if (!$currentUsername) {
    header('Location: index.php');
    exit;
}

$errorMessage = '';
$successMessage = '';

// Handle success/error messages from redirects
if (isset($_GET['success'])) {
    $successMessage = htmlspecialchars($_GET['success']);
}
if (isset($_GET['error'])) {
    $errorMessage = htmlspecialchars($_GET['error']);
}

// Get search and pagination parameters
$searchText = isset($_GET['search']) ? trim($_GET['search']) : '';
$sortField = isset($_GET['sort']) ? trim($_GET['sort']) : 'event_date';
$sortOrder = isset($_GET['order']) && strtolower($_GET['order']) === 'asc' ? 'ASC' : 'DESC';
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$perPage = 20;
$offset = ($page - 1) * $perPage;

// Get news articles
$db = Database::getInstance()->getConnection();

try {
    // Build SQL query for counting
    $countSql = "SELECT COUNT(*) FROM news WHERE 1=1";
    $params = [];

    // Add search condition
    if ($searchText !== '') {
        $countSql .= " AND (title LIKE ? OR content LIKE ? OR created_by LIKE ?)";
        $searchParam = '%' . $searchText . '%';
        $params[] = $searchParam;
        $params[] = $searchParam;
        $params[] = $searchParam;
    }

    // Get total count
    $countStmt = $db->prepare($countSql);
    $countStmt->execute($params);
    $totalNews = (int)$countStmt->fetchColumn();
    $totalPages = (int)ceil($totalNews / $perPage);

    // Validate sort field
    $validSortFields = [
        'id' => 'ID',
        'title' => 'Titel',
        'event_date' => 'Veranstaltungsdatum',
        'created_at' => 'Erstelldatum',
        'created_by' => 'Ersteller'
    ];

    if (!array_key_exists($sortField, $validSortFields)) {
        $sortField = 'event_date';
    }

    // Build SQL query for fetching
    $sql = "SELECT id, title, event_date, created_at, created_by, image_filename
            FROM news WHERE 1=1";

    $fetchParams = [];

    // Add search condition
    if ($searchText !== '') {
        $sql .= " AND (title LIKE ? OR content LIKE ? OR created_by LIKE ?)";
        $fetchParams[] = $searchParam;
        $fetchParams[] = $searchParam;
        $fetchParams[] = $searchParam;
    }

    // Add sorting
    $sql .= " ORDER BY " . $sortField . " " . $sortOrder;

    // Add pagination
    $sql .= " LIMIT ? OFFSET ?";
    $fetchParams[] = $perPage;
    $fetchParams[] = $offset;

    $stmt = $db->prepare($sql);

    // Bind parameters
    $paramIndex = 1;
    foreach ($fetchParams as $param) {
        if ($param === $perPage || $param === $offset) {
            $stmt->bindValue($paramIndex, $param, PDO::PARAM_INT);
        } else {
            $stmt->bindValue($paramIndex, $param, PDO::PARAM_STR);
        }
        $paramIndex++;
    }

    $stmt->execute();
    $newsList = $stmt->fetchAll();

} catch (Exception $e) {
    $errorMessage = 'Fehler beim Laden der News: ' . $e->getMessage();
    $newsList = [];
    $totalNews = 0;
    $totalPages = 0;
}

$pageTitle = 'News verwalten';
include 'includes/header.php';
?>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1><?= $pageTitle ?></h1>
        <a href="news_edit.php" class="btn btn-primary">
            <i class="bi bi-plus-lg"></i> Neue News erstellen
        </a>
    </div>

    <?php if ($errorMessage): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?= $errorMessage ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if ($successMessage): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?= $successMessage ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- Search and Filter Card -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="get" class="row g-3">
                <div class="col-md-6">
                    <label for="search" class="form-label">
                        <i class="bi bi-search"></i> Suche
                    </label>
                    <input type="text"
                           class="form-control"
                           id="search"
                           name="search"
                           value="<?= htmlspecialchars($searchText) ?>"
                           placeholder="Titel, Inhalt oder Ersteller durchsuchen...">
                </div>
                <div class="col-md-4">
                    <label for="sort" class="form-label">
                        <i class="bi bi-sort-down"></i> Sortieren nach
                    </label>
                    <select class="form-select" id="sort" name="sort">
                        <?php foreach ($validSortFields as $field => $label): ?>
                            <option value="<?= $field ?>" <?= $sortField === $field ? 'selected' : '' ?>>
                                <?= $label ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label for="order" class="form-label">Reihenfolge</label>
                    <select class="form-select" id="order" name="order">
                        <option value="desc" <?= $sortOrder === 'DESC' ? 'selected' : '' ?>>Absteigend</option>
                        <option value="asc" <?= $sortOrder === 'ASC' ? 'selected' : '' ?>>Aufsteigend</option>
                    </select>
                </div>
                <div class="col-12">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-funnel"></i> Filtern
                    </button>
                    <?php if ($searchText !== '' || $sortField !== 'event_date' || $sortOrder !== 'DESC'): ?>
                        <a href="news.php" class="btn btn-outline-secondary">
                            <i class="bi bi-x-lg"></i> Zurücksetzen
                        </a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>

    <!-- Results Info -->
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <strong><?= $totalNews ?></strong> News-Artikel gefunden
            <?php if ($searchText !== ''): ?>
                <span class="text-muted">für "<?= htmlspecialchars($searchText) ?>"</span>
            <?php endif; ?>
        </div>
        <?php if ($totalPages > 1): ?>
            <div class="text-muted">
                Seite <?= $page ?> von <?= $totalPages ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- News Table -->
    <div class="card">
        <div class="card-body">
            <?php if (empty($newsList)): ?>
                <div class="text-center py-5">
                    <i class="bi bi-inbox" style="font-size: 3rem; color: #ccc;"></i>
                    <p class="text-muted mt-3">
                        <?php if ($searchText !== ''): ?>
                            Keine News-Artikel gefunden, die Ihrer Suche entsprechen.
                        <?php else: ?>
                            Keine News-Artikel gefunden.
                        <?php endif; ?>
                    </p>
                    <p>
                        <a href="news_edit.php" class="btn btn-primary">
                            <i class="bi bi-plus-lg"></i> Erste News erstellen
                        </a>
                    </p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead>
                            <tr>
                                <th style="width: 60px;">
                                    <a href="?sort=id&order=<?= $sortField === 'id' && $sortOrder === 'ASC' ? 'desc' : 'asc' ?><?= $searchText !== '' ? '&search=' . urlencode($searchText) : '' ?>"
                                       class="text-decoration-none text-dark">
                                        ID
                                        <?php if ($sortField === 'id'): ?>
                                            <i class="bi bi-caret-<?= $sortOrder === 'ASC' ? 'up' : 'down' ?>-fill"></i>
                                        <?php endif; ?>
                                    </a>
                                </th>
                                <th>
                                    <a href="?sort=title&order=<?= $sortField === 'title' && $sortOrder === 'ASC' ? 'desc' : 'asc' ?><?= $searchText !== '' ? '&search=' . urlencode($searchText) : '' ?>"
                                       class="text-decoration-none text-dark">
                                        Titel
                                        <?php if ($sortField === 'title'): ?>
                                            <i class="bi bi-caret-<?= $sortOrder === 'ASC' ? 'up' : 'down' ?>-fill"></i>
                                        <?php endif; ?>
                                    </a>
                                </th>
                                <th>
                                    <a href="?sort=event_date&order=<?= $sortField === 'event_date' && $sortOrder === 'ASC' ? 'desc' : 'asc' ?><?= $searchText !== '' ? '&search=' . urlencode($searchText) : '' ?>"
                                       class="text-decoration-none text-dark">
                                        Veranstaltungsdatum
                                        <?php if ($sortField === 'event_date'): ?>
                                            <i class="bi bi-caret-<?= $sortOrder === 'ASC' ? 'up' : 'down' ?>-fill"></i>
                                        <?php endif; ?>
                                    </a>
                                </th>
                                <th>
                                    <a href="?sort=created_at&order=<?= $sortField === 'created_at' && $sortOrder === 'ASC' ? 'desc' : 'asc' ?><?= $searchText !== '' ? '&search=' . urlencode($searchText) : '' ?>"
                                       class="text-decoration-none text-dark">
                                        Erstellt
                                        <?php if ($sortField === 'created_at'): ?>
                                            <i class="bi bi-caret-<?= $sortOrder === 'ASC' ? 'up' : 'down' ?>-fill"></i>
                                        <?php endif; ?>
                                    </a>
                                </th>
                                <th>
                                    <a href="?sort=created_by&order=<?= $sortField === 'created_by' && $sortOrder === 'ASC' ? 'desc' : 'asc' ?><?= $searchText !== '' ? '&search=' . urlencode($searchText) : '' ?>"
                                       class="text-decoration-none text-dark">
                                        Ersteller
                                        <?php if ($sortField === 'created_by'): ?>
                                            <i class="bi bi-caret-<?= $sortOrder === 'ASC' ? 'up' : 'down' ?>-fill"></i>
                                        <?php endif; ?>
                                    </a>
                                </th>
                                <th>Bild</th>
                                <th style="width: 150px;">Aktionen</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($newsList as $news): ?>
                                <tr>
                                    <td><?= $news['id'] ?></td>
                                    <td>
                                        <strong><?= htmlspecialchars($news['title']) ?></strong>
                                    </td>
                                    <td>
                                        <i class="bi bi-calendar-event"></i>
                                        <?= date('d.m.Y', strtotime($news['event_date'])) ?>
                                    </td>
                                    <td>
                                        <small class="text-muted">
                                            <?= date('d.m.Y H:i', strtotime($news['created_at'])) ?>
                                        </small>
                                    </td>
                                    <td>
                                        <small><?= htmlspecialchars($news['created_by']) ?></small>
                                    </td>
                                    <td>
                                        <?php if (!empty($news['image_filename'])): ?>
                                            <i class="bi bi-image text-success" title="Bild vorhanden"></i>
                                        <?php else: ?>
                                            <i class="bi bi-image text-muted" title="Kein Bild"></i>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm" role="group">
                                            <a href="news_edit.php?id=<?= $news['id'] ?>"
                                               class="btn btn-outline-primary"
                                               title="Bearbeiten">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                            <button type="button"
                                                    class="btn btn-outline-danger"
                                                    onclick="deleteNews(<?= $news['id'] ?>, '<?= htmlspecialchars(addslashes($news['title'])) ?>')"
                                                    title="Löschen">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <?php if ($totalPages > 1): ?>
                    <nav aria-label="Seitennavigation" class="mt-4">
                        <ul class="pagination justify-content-center">
                            <?php if ($page > 1): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?= $page - 1 ?>&sort=<?= $sortField ?>&order=<?= strtolower($sortOrder) ?><?= $searchText !== '' ? '&search=' . urlencode($searchText) : '' ?>">
                                        <i class="bi bi-chevron-left"></i> Zurück
                                    </a>
                                </li>
                            <?php endif; ?>

                            <?php
                            // Show page numbers
                            $startPage = max(1, $page - 2);
                            $endPage = min($totalPages, $page + 2);

                            if ($startPage > 1) {
                                echo '<li class="page-item"><a class="page-link" href="?page=1&sort=' . $sortField . '&order=' . strtolower($sortOrder) . ($searchText !== '' ? '&search=' . urlencode($searchText) : '') . '">1</a></li>';
                                if ($startPage > 2) {
                                    echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                                }
                            }

                            for ($i = $startPage; $i <= $endPage; $i++) {
                                $active = $i === $page ? ' active' : '';
                                echo '<li class="page-item' . $active . '"><a class="page-link" href="?page=' . $i . '&sort=' . $sortField . '&order=' . strtolower($sortOrder) . ($searchText !== '' ? '&search=' . urlencode($searchText) : '') . '">' . $i . '</a></li>';
                            }

                            if ($endPage < $totalPages) {
                                if ($endPage < $totalPages - 1) {
                                    echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                                }
                                echo '<li class="page-item"><a class="page-link" href="?page=' . $totalPages . '&sort=' . $sortField . '&order=' . strtolower($sortOrder) . ($searchText !== '' ? '&search=' . urlencode($searchText) : '') . '">' . $totalPages . '</a></li>';
                            }
                            ?>

                            <?php if ($page < $totalPages): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?= $page + 1 ?>&sort=<?= $sortField ?>&order=<?= strtolower($sortOrder) ?><?= $searchText !== '' ? '&search=' . urlencode($searchText) : '' ?>">
                                        Weiter <i class="bi bi-chevron-right"></i>
                                    </a>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </nav>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
async function deleteNews(newsId, title) {
    if (!confirm(`Möchten Sie die News "${title}" wirklich löschen?\n\nDiese Aktion kann nicht rückgängig gemacht werden.`)) {
        return;
    }

    try {
        const response = await fetch('api/delete_news.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id: newsId })
        });

        const result = await response.json();

        if (result.success) {
            window.location.href = 'news.php?success=' + encodeURIComponent('News erfolgreich gelöscht');
        } else {
            alert('Fehler beim Löschen: ' + result.message);
        }
    } catch (error) {
        alert('Fehler beim Löschen: ' + error.message);
    }
}
</script>

<?php include 'includes/footer.php'; ?>
