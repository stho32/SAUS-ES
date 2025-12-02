<?php
declare(strict_types=1);
error_reporting(E_ALL);
ini_set('display_errors', '1');

require_once __DIR__ . '/includes/auth_check.php';
require_once 'includes/Database.php';
require_once 'includes/auth.php';

// Check authentication
requireMasterLink();

$currentUsername = getCurrentUsername();
if (!$currentUsername) {
    header('Location: index.php');
    exit;
}

$newsId = isset($_GET['id']) ? (int)$_GET['id'] : null;
$isEditMode = $newsId !== null;

$db = Database::getInstance()->getConnection();
$news = null;

// If edit mode, load existing news
if ($isEditMode) {
    try {
        $stmt = $db->prepare("SELECT * FROM news WHERE id = ?");
        $stmt->execute([$newsId]);
        $news = $stmt->fetch();

        if (!$news) {
            header('Location: news.php?error=' . urlencode('News-Artikel nicht gefunden'));
            exit;
        }
    } catch (Exception $e) {
        header('Location: news.php?error=' . urlencode($e->getMessage()));
        exit;
    }
}

$pageTitle = $isEditMode ? "News bearbeiten" : "Neue News erstellen";
require_once 'includes/header.php';
?>
<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="mb-0"><?= $isEditMode ? 'News bearbeiten' : 'Neue News erstellen' ?></h1>
            <?php if ($isEditMode): ?>
                <small class="text-muted">ID #<?= $news['id'] ?></small>
            <?php endif; ?>
        </div>
        <div>
            <a href="news.php" class="btn btn-outline-secondary">
                <i class="bi bi-x-lg"></i> Abbrechen
            </a>
            <button type="button" class="btn btn-primary ms-2" id="saveButton" onclick="saveNews()">
                <i class="bi bi-check-lg"></i> Speichern
            </button>
        </div>
    </div>

    <?php if ($isEditMode): ?>
        <input type="hidden" id="newsId" value="<?= $newsId ?>">
        <input type="hidden" id="existingImageFilename" value="<?= htmlspecialchars($news['image_filename'] ?? '') ?>">
    <?php else: ?>
        <input type="hidden" id="newsId" value="">
    <?php endif; ?>
    <input type="hidden" id="imageFilename" value="<?= htmlspecialchars($news['image_filename'] ?? '') ?>">

    <form id="newsForm" class="needs-validation" novalidate>
        <div class="row mb-4">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title mb-4">Allgemeine Informationen</h5>

                        <div class="mb-3">
                            <label for="title" class="form-label">Titel <span class="text-danger">*</span></label>
                            <input type="text"
                                   class="form-control"
                                   id="title"
                                   value="<?= $isEditMode ? htmlspecialchars($news['title']) : '' ?>"
                                   required
                                   maxlength="255">
                            <div class="invalid-feedback">Bitte geben Sie einen Titel ein.</div>
                        </div>

                        <div class="mb-3">
                            <label for="content" class="form-label">Inhalt <span class="text-danger">*</span></label>
                            <textarea class="form-control"
                                      id="content"
                                      rows="10"
                                      required><?= $isEditMode ? htmlspecialchars($news['content']) : '' ?></textarea>
                            <div class="form-text">
                                Formatierung: **fett**, *kursiv*, URLs werden automatisch erkannt
                            </div>
                            <div class="invalid-feedback">Bitte geben Sie einen Inhalt ein.</div>
                        </div>

                        <div class="mb-3">
                            <label for="eventDate" class="form-label">Veranstaltungsdatum <span class="text-danger">*</span></label>
                            <input type="date"
                                   class="form-control"
                                   id="eventDate"
                                   value="<?= $isEditMode ? htmlspecialchars($news['event_date']) : '' ?>"
                                   required>
                            <div class="form-text">Datum der Veranstaltung oder des Events</div>
                            <div class="invalid-feedback">Bitte geben Sie ein Veranstaltungsdatum ein.</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title mb-4">Bild</h5>

                        <div class="mb-3">
                            <?php if ($isEditMode && !empty($news['image_filename'])): ?>
                                <div id="currentImagePreview" class="mb-3">
                                    <label class="form-label">Aktuelles Bild</label>
                                    <img src="../public_php_app/api/get_news_image.php?id=<?= $newsId ?>"
                                         alt="Vorschau"
                                         class="img-fluid rounded mb-2"
                                         style="max-width: 100%;">
                                </div>
                            <?php endif; ?>

                            <label for="imageUpload" class="form-label">
                                <?= ($isEditMode && !empty($news['image_filename'])) ? 'Neues Bild hochladen' : 'Bild hochladen' ?>
                            </label>
                            <input type="file"
                                   class="form-control"
                                   id="imageUpload"
                                   accept="image/jpeg,image/png,image/gif"
                                   onchange="uploadImage()">
                            <div class="form-text">
                                Max. 2MB, nur JPG, PNG oder GIF
                            </div>
                            <div id="uploadProgress" class="mt-2" style="display: none;">
                                <div class="progress">
                                    <div class="progress-bar progress-bar-striped progress-bar-animated"
                                         role="progressbar"
                                         style="width: 100%"></div>
                                </div>
                                <small class="text-muted">Wird hochgeladen...</small>
                            </div>
                            <div id="uploadSuccess" class="alert alert-success mt-2" style="display: none;">
                                <i class="bi bi-check-circle"></i> Bild erfolgreich hochgeladen
                            </div>
                            <div id="uploadError" class="alert alert-danger mt-2" style="display: none;"></div>
                        </div>

                        <?php if ($isEditMode): ?>
                            <div class="mb-3">
                                <label class="form-label">Erstellt</label>
                                <p class="mb-0 text-muted small">
                                    <?= date('d.m.Y H:i', strtotime($news['created_at'])) ?> Uhr<br>
                                    von <?= htmlspecialchars($news['created_by']) ?>
                                </p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<script>
async function uploadImage() {
    const fileInput = document.getElementById('imageUpload');
    const file = fileInput.files[0];

    if (!file) return;

    // Validate file size
    if (file.size > 2 * 1024 * 1024) {
        showUploadError('Datei zu groß (max 2MB)');
        fileInput.value = '';
        return;
    }

    // Validate file type
    const allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
    if (!allowedTypes.includes(file.type)) {
        showUploadError('Nur JPG, PNG oder GIF erlaubt');
        fileInput.value = '';
        return;
    }

    // First, save the news article if it doesn't exist yet
    const newsId = document.getElementById('newsId').value;
    let currentNewsId = newsId;

    if (!currentNewsId) {
        // Create news first to get an ID
        const title = document.getElementById('title').value.trim();
        const content = document.getElementById('content').value.trim();
        const eventDate = document.getElementById('eventDate').value;

        if (!title || !content || !eventDate) {
            showUploadError('Bitte füllen Sie zuerst Titel, Inhalt und Datum aus');
            fileInput.value = '';
            return;
        }

        try {
            const response = await fetch('api/create_news.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ title, content, event_date: eventDate, image_filename: '' })
            });

            const result = await response.json();
            if (!result.success) {
                throw new Error(result.message || 'Fehler beim Erstellen');
            }

            currentNewsId = result.data.id;
            document.getElementById('newsId').value = currentNewsId;
        } catch (error) {
            showUploadError('Fehler: ' + error.message);
            fileInput.value = '';
            return;
        }
    }

    // Show upload progress
    document.getElementById('uploadProgress').style.display = 'block';
    document.getElementById('uploadSuccess').style.display = 'none';
    document.getElementById('uploadError').style.display = 'none';

    // Upload image
    const formData = new FormData();
    formData.append('file', file);
    formData.append('newsId', currentNewsId);

    try {
        const response = await fetch('api/upload_news_image.php', {
            method: 'POST',
            body: formData
        });

        const result = await response.json();

        if (result.success) {
            document.getElementById('imageFilename').value = result.filename;
            document.getElementById('uploadProgress').style.display = 'none';
            document.getElementById('uploadSuccess').style.display = 'block';

            // Hide success message after 3 seconds
            setTimeout(() => {
                document.getElementById('uploadSuccess').style.display = 'none';
            }, 3000);
        } else {
            throw new Error(result.error || 'Upload fehlgeschlagen');
        }
    } catch (error) {
        document.getElementById('uploadProgress').style.display = 'none';
        showUploadError(error.message);
        fileInput.value = '';
    }
}

function showUploadError(message) {
    const errorDiv = document.getElementById('uploadError');
    errorDiv.textContent = message;
    errorDiv.style.display = 'block';

    setTimeout(() => {
        errorDiv.style.display = 'none';
    }, 5000);
}

async function saveNews() {
    const form = document.getElementById('newsForm');

    if (!form.checkValidity()) {
        form.classList.add('was-validated');
        return;
    }

    const newsId = document.getElementById('newsId').value;
    const title = document.getElementById('title').value.trim();
    const content = document.getElementById('content').value.trim();
    const eventDate = document.getElementById('eventDate').value;
    const imageFilename = document.getElementById('imageFilename').value;

    const data = {
        title: title,
        content: content,
        event_date: eventDate,
        image_filename: imageFilename
    };

    try {
        const saveButton = document.getElementById('saveButton');
        saveButton.disabled = true;
        saveButton.innerHTML = '<span class="spinner-border spinner-border-sm" role="status"></span> Speichern...';

        let apiUrl, method;

        if (newsId) {
            // Update existing
            apiUrl = 'api/update_news.php';
            data.id = parseInt(newsId);
        } else {
            // Create new
            apiUrl = 'api/create_news.php';
        }

        const response = await fetch(apiUrl, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        });

        const result = await response.json();

        if (result.success) {
            window.location.href = 'news.php?success=' + encodeURIComponent(newsId ? 'News aktualisiert' : 'News erstellt');
        } else {
            throw new Error(result.message || 'Fehler beim Speichern');
        }
    } catch (error) {
        alert('Fehler: ' + error.message);
        const saveButton = document.getElementById('saveButton');
        saveButton.disabled = false;
        saveButton.innerHTML = '<i class="bi bi-check-lg"></i> Speichern';
    }
}
</script>

<?php require_once 'includes/footer.php'; ?>
