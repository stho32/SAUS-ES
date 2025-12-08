<?php
declare(strict_types=1);

// Enable error display for debugging
error_reporting(E_ALL);
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');

require_once __DIR__ . '/includes/Database.php';
require_once __DIR__ . '/includes/comment_formatter.php';

try {
    $db = Database::getInstance()->getConnection();

    // Get page, search, and article ID parameters
    $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
    $search = isset($_GET['search']) ? trim($_GET['search']) : '';
    $articleId = isset($_GET['id']) ? (int)$_GET['id'] : null;
    $perPage = 10;
    $offset = ($page - 1) * $perPage;

    // If article ID is provided, show single article view
    if ($articleId) {
        $singleArticleStmt = $db->prepare("SELECT id, title, content, image_filename, event_date, created_at FROM news WHERE id = ?");
        $singleArticleStmt->execute([$articleId]);
        $singleArticle = $singleArticleStmt->fetch();
    }

    // Build base SQL for counting
    $countSql = "SELECT COUNT(*) FROM news WHERE 1=1";
    $params = [];

    if (!empty($search)) {
        $countSql .= " AND (title LIKE :search OR content LIKE :search)";
        $params[':search'] = '%' . $search . '%';
    }

    // Get total count
    $countStmt = $db->prepare($countSql);
    foreach ($params as $key => $value) {
        $countStmt->bindValue($key, $value, PDO::PARAM_STR);
    }
    $countStmt->execute();
    $totalNews = (int)$countStmt->fetchColumn();
    $totalPages = (int)ceil($totalNews / $perPage);

    // Build SQL for fetching news
    $sql = "SELECT id, title, content, image_filename, event_date, created_at
            FROM news WHERE 1=1";

    if (!empty($search)) {
        $sql .= " AND (title LIKE :search OR content LIKE :search)";
    }

    $sql .= " ORDER BY event_date DESC LIMIT :limit OFFSET :offset";

    $stmt = $db->prepare($sql);

    // Bind search parameter if provided
    if (!empty($search)) {
        $stmt->bindValue(':search', '%' . $search . '%', PDO::PARAM_STR);
    }

    $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);

    $stmt->execute();
    $newsList = $stmt->fetchAll();

} catch (Exception $e) {
    die('Fehler beim Laden der News: ' . $e->getMessage());
}

// Set page title based on context
if ($articleId && $singleArticle) {
    $pageTitle = htmlspecialchars($singleArticle['title']) . " - News";
} else {
    $pageTitle = "News & Veranstaltungen";
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            background: transparent;
            padding: 0;
            margin: 0;
            font-family: system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
        }
        .container {
            padding: 15px;
            max-width: 100%;
        }
        .card {
            margin-bottom: 1.5rem;
            border: none;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            transition: transform 0.2s, box-shadow 0.2s;
            background: #fff;
        }
        .card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.12);
        }
        .card-header {
            background: none;
            border-bottom: 1px solid rgba(0,0,0,0.08);
            padding: 1.5rem 1.5rem 1rem;
        }
        .news-title {
            font-size: 1.4rem;
            font-weight: 600;
            margin: 0.5rem 0 1rem;
            padding: 0;
            color: #2c3e50;
            line-height: 1.3;
        }
        .news-date-badge {
            display: inline-block;
            font-size: 0.85rem;
            color: #6c757d;
            margin-bottom: 0.5rem;
            font-weight: 500;
        }
        .news-thumbnail {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 8px;
            box-shadow: 0 2px 6px rgba(0,0,0,0.1);
            flex-shrink: 0;
        }
        .news-list-item {
            display: flex;
            gap: 1rem;
            text-decoration: none;
            color: inherit;
        }
        .news-list-item:hover .news-title {
            color: #007bff;
        }
        .news-list-content {
            flex: 1;
            min-width: 0;
        }
        .news-excerpt {
            color: #666;
            font-size: 0.95rem;
            line-height: 1.5;
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        .news-image-full {
            max-width: 100%;
            height: auto;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            margin: 1.5rem 0;
        }
        .back-link {
            display: inline-block;
            margin-bottom: 1.5rem;
            color: #007bff;
            text-decoration: none;
        }
        .back-link:hover {
            text-decoration: underline;
        }
        .news-content {
            margin-bottom: 1rem;
            line-height: 1.6;
            color: #444;
        }
        .news-meta {
            color: #6c757d;
            font-size: 0.9rem;
            margin-top: 1rem;
            padding-top: 1rem;
            border-top: 1px solid rgba(0,0,0,0.08);
        }
        .search-box {
            background: white;
            padding: 1.5rem;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            margin-bottom: 2rem;
        }
        .pagination {
            justify-content: center;
            margin-top: 2rem;
        }
        .pagination .page-link {
            border-radius: 8px;
            margin: 0 0.25rem;
            color: #007bff;
        }
        .pagination .page-item.active .page-link {
            background-color: #007bff;
            border-color: #007bff;
        }
        .no-results {
            text-align: center;
            padding: 3rem;
            color: #6c757d;
        }
        @media (max-width: 768px) {
            .news-image {
                float: none;
                width: 100%;
                margin: 0 0 1rem 0;
            }
        }

        /* Modal styles */
        .modal-content {
            border-radius: 12px;
        }
        .modal-body {
            padding: 0;
        }
        .modal-body img {
            width: 100%;
            height: auto;
            border-radius: 12px;
        }
    </style>
</head>
<body>
    <div class="container">
        <?php if ($articleId && $singleArticle): ?>
            <!-- Single Article View -->
            <a href="news.php<?= !empty($search) ? '?search=' . urlencode($search) : '' ?><?= $page > 1 ? (!empty($search) ? '&' : '?') . 'page=' . $page : '' ?>" class="back-link">
                <i class="bi bi-arrow-left"></i> Zurück zur Übersicht
            </a>

            <div class="card">
                <div class="card-body">
                    <div class="news-date-badge">
                        <i class="bi bi-calendar-event"></i>
                        <?= date('d.m.Y', strtotime($singleArticle['event_date'])) ?>
                    </div>

                    <h1 class="news-title"><?= htmlspecialchars($singleArticle['title']) ?></h1>

                    <?php if (!empty($singleArticle['image_filename'])): ?>
                        <div class="text-center">
                            <img src="api/get_news_image.php?id=<?= $singleArticle['id'] ?>"
                                 alt="<?= htmlspecialchars($singleArticle['title']) ?>"
                                 class="news-image-full">
                        </div>
                    <?php endif; ?>

                    <div class="news-content">
                        <?= formatComment($singleArticle['content']) ?>
                    </div>

                    <div class="news-meta">
                        <i class="bi bi-clock"></i>
                        Veröffentlicht: <?= date('d.m.Y', strtotime($singleArticle['created_at'])) ?>
                    </div>
                </div>
            </div>

        <?php elseif ($articleId && !$singleArticle): ?>
            <!-- Article Not Found -->
            <div class="alert alert-warning">
                <i class="bi bi-exclamation-triangle"></i>
                Artikel nicht gefunden.
            </div>
            <a href="news.php" class="btn btn-primary">
                <i class="bi bi-arrow-left"></i> Zurück zur Übersicht
            </a>

        <?php else: ?>
            <!-- News List View -->
            <h1 class="mb-4"><?= $pageTitle ?></h1>

            <!-- Search Box -->
            <div class="search-box">
                <form method="get" class="row g-3">
                    <div class="col-md-10">
                        <label for="search-box" class="form-label">
                            <i class="bi bi-search"></i> Suche
                        </label>
                        <input type="text"
                               id="search-box"
                               name="search"
                               value="<?= htmlspecialchars($search) ?>"
                               class="form-control"
                               placeholder="Suchbegriff eingeben...">
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="bi bi-search"></i> Suchen
                        </button>
                    </div>
                    <?php if (!empty($search)): ?>
                        <div class="col-12">
                            <a href="news.php" class="btn btn-sm btn-outline-secondary">
                                <i class="bi bi-x-lg"></i> Suche zurücksetzen
                            </a>
                        </div>
                    <?php endif; ?>
                </form>
            </div>

            <!-- Results Count -->
            <?php if (!empty($search)): ?>
                <div class="alert alert-info">
                    <i class="bi bi-info-circle"></i>
                    <?= $totalNews ?> Ergebnis<?= $totalNews !== 1 ? 'se' : '' ?> für "<?= htmlspecialchars($search) ?>"
                </div>
            <?php endif; ?>

            <!-- News List -->
            <?php if (empty($newsList)): ?>
                <div class="no-results">
                    <i class="bi bi-inbox" style="font-size: 3rem; color: #ccc;"></i>
                    <h3>Keine News gefunden</h3>
                    <?php if (!empty($search)): ?>
                        <p>Versuchen Sie es mit einem anderen Suchbegriff.</p>
                    <?php else: ?>
                        <p>Derzeit sind keine News-Artikel verfügbar.</p>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <?php foreach ($newsList as $news): ?>
                    <div class="card">
                        <div class="card-body">
                            <a href="news.php?id=<?= $news['id'] ?><?= !empty($search) ? '&search=' . urlencode($search) : '' ?><?= $page > 1 ? '&page=' . $page : '' ?>"
                               class="news-list-item">
                                <?php if (!empty($news['image_filename'])): ?>
                                    <img src="api/get_news_image.php?id=<?= $news['id'] ?>&thumbnail=true"
                                         alt="<?= htmlspecialchars($news['title']) ?>"
                                         class="news-thumbnail">
                                <?php else: ?>
                                    <div class="news-thumbnail" style="background: #f0f0f0; display: flex; align-items: center; justify-content: center;">
                                        <i class="bi bi-image text-muted" style="font-size: 2rem;"></i>
                                    </div>
                                <?php endif; ?>

                                <div class="news-list-content">
                                    <div class="news-date-badge">
                                        <i class="bi bi-calendar-event"></i>
                                        <?= date('d.m.Y', strtotime($news['event_date'])) ?>
                                    </div>

                                    <h2 class="news-title" style="font-size: 1.2rem; margin: 0.25rem 0 0.5rem;">
                                        <?= htmlspecialchars($news['title']) ?>
                                    </h2>

                                    <div class="news-excerpt">
                                        <?= htmlspecialchars(strip_tags($news['content'])) ?>
                                    </div>

                                    <div class="news-meta" style="border-top: none; padding-top: 0.5rem; margin-top: 0.5rem; font-size: 0.8rem;">
                                        <i class="bi bi-clock"></i>
                                        Veröffentlicht: <?= date('d.m.Y', strtotime($news['created_at'])) ?>
                                    </div>
                                </div>
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>

                <!-- Pagination -->
                <?php if ($totalPages > 1): ?>
                    <nav aria-label="Seitennavigation">
                        <ul class="pagination">
                            <?php if ($page > 1): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?= $page - 1 ?><?= !empty($search) ? '&search=' . urlencode($search) : '' ?>">
                                        <i class="bi bi-chevron-left"></i> Zurück
                                    </a>
                                </li>
                            <?php endif; ?>

                            <?php
                            // Show page numbers
                            $startPage = max(1, $page - 2);
                            $endPage = min($totalPages, $page + 2);

                            if ($startPage > 1) {
                                echo '<li class="page-item"><a class="page-link" href="?page=1' . (!empty($search) ? '&search=' . urlencode($search) : '') . '">1</a></li>';
                                if ($startPage > 2) {
                                    echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                                }
                            }

                            for ($i = $startPage; $i <= $endPage; $i++) {
                                $active = $i === $page ? ' active' : '';
                                echo '<li class="page-item' . $active . '"><a class="page-link" href="?page=' . $i . (!empty($search) ? '&search=' . urlencode($search) : '') . '">' . $i . '</a></li>';
                            }

                            if ($endPage < $totalPages) {
                                if ($endPage < $totalPages - 1) {
                                    echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                                }
                                echo '<li class="page-item"><a class="page-link" href="?page=' . $totalPages . (!empty($search) ? '&search=' . urlencode($search) : '') . '">' . $totalPages . '</a></li>';
                            }
                            ?>

                            <?php if ($page < $totalPages): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?= $page + 1 ?><?= !empty($search) ? '&search=' . urlencode($search) : '' ?>">
                                        Weiter <i class="bi bi-chevron-right"></i>
                                    </a>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </nav>

                    <div class="text-center text-muted mb-4">
                        Seite <?= $page ?> von <?= $totalPages ?> (<?= $totalNews ?> News gesamt)
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
