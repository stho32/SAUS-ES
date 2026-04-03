<?php
declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\News;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class NewsApiController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'content' => ['required', 'string'],
            'event_date' => ['required', 'date'],
            'image' => ['sometimes', 'nullable', 'file', 'image', 'max:' . config('saus.max_file_size', 10240)],
        ]);

        $newsData = [
            'title' => $validated['title'],
            'content' => $validated['content'],
            'event_date' => $validated['event_date'],
            'created_by' => session('username', 'Unbekannt'),
        ];

        if ($request->hasFile('image')) {
            $file = $request->file('image');
            $safeFilename = bin2hex(random_bytes(16)) . '.' . $file->getClientOriginalExtension();
            $uploadPath = config('saus.news_upload_path', 'uploads/news');

            $file->storeAs($uploadPath, $safeFilename, 'local');
            $newsData['image_filename'] = $safeFilename;
        }

        $news = News::create($newsData);

        return response()->json([
            'success' => true,
            'data' => $news->toArray(),
        ]);
    }

    public function update(Request $request, News $news): JsonResponse
    {
        $validated = $request->validate([
            'title' => ['sometimes', 'string', 'max:255'],
            'content' => ['sometimes', 'string'],
            'event_date' => ['sometimes', 'date'],
            'image' => ['sometimes', 'nullable', 'file', 'image', 'max:' . config('saus.max_file_size', 10240)],
        ]);

        $updateData = [];

        if (array_key_exists('title', $validated)) {
            $updateData['title'] = $validated['title'];
        }
        if (array_key_exists('content', $validated)) {
            $updateData['content'] = $validated['content'];
        }
        if (array_key_exists('event_date', $validated)) {
            $updateData['event_date'] = $validated['event_date'];
        }

        if ($request->hasFile('image')) {
            // Delete old image if it exists
            if ($news->image_filename) {
                $oldPath = config('saus.news_upload_path', 'uploads/news') . '/' . $news->image_filename;
                if (Storage::disk('local')->exists($oldPath)) {
                    Storage::disk('local')->delete($oldPath);
                }
            }

            $file = $request->file('image');
            $safeFilename = bin2hex(random_bytes(16)) . '.' . $file->getClientOriginalExtension();
            $uploadPath = config('saus.news_upload_path', 'uploads/news');

            $file->storeAs($uploadPath, $safeFilename, 'local');
            $updateData['image_filename'] = $safeFilename;
        }

        $news->update($updateData);

        return response()->json([
            'success' => true,
            'data' => $news->fresh()->toArray(),
        ]);
    }

    public function destroy(News $news): JsonResponse
    {
        if ($news->image_filename) {
            $imagePath = config('saus.news_upload_path', 'uploads/news') . '/' . $news->image_filename;
            if (Storage::disk('local')->exists($imagePath)) {
                Storage::disk('local')->delete($imagePath);
            }
        }

        $news->delete();

        return response()->json(['success' => true, 'data' => null]);
    }

    public function image(Request $request, News $news): StreamedResponse
    {
        if (!$news->image_filename) {
            abort(404, 'Kein Bild vorhanden.');
        }

        $uploadPath = config('saus.news_upload_path', 'uploads/news');
        $filePath = $uploadPath . '/' . $news->image_filename;

        if (!Storage::disk('local')->exists($filePath)) {
            abort(404, 'Bild nicht gefunden.');
        }

        $isThumbnail = $request->query('thumbnail') === 'true';

        if ($isThumbnail) {
            return $this->serveThumbnail($filePath, $news->image_filename);
        }

        $mimeType = Storage::disk('local')->mimeType($filePath);

        return Storage::disk('local')->response($filePath, $news->image_filename, [
            'Content-Type' => $mimeType,
        ]);
    }

    private function serveThumbnail(string $filePath, string $filename): StreamedResponse
    {
        $thumbnailWidth = config('saus.thumbnail_width', 200);
        $fullPath = Storage::disk('local')->path($filePath);
        $mimeType = Storage::disk('local')->mimeType($filePath);

        return response()->stream(function () use ($fullPath, $thumbnailWidth) {
            $imageInfo = getimagesize($fullPath);

            if (!$imageInfo) {
                readfile($fullPath);
                return;
            }

            $srcWidth = $imageInfo[0];
            $srcHeight = $imageInfo[1];
            $srcMime = $imageInfo['mime'];

            $ratio = $thumbnailWidth / $srcWidth;
            $newHeight = (int) round($srcHeight * $ratio);

            $source = match ($srcMime) {
                'image/jpeg' => imagecreatefromjpeg($fullPath),
                'image/png' => imagecreatefrompng($fullPath),
                'image/gif' => imagecreatefromgif($fullPath),
                'image/webp' => imagecreatefromwebp($fullPath),
                default => null,
            };

            if (!$source) {
                readfile($fullPath);
                return;
            }

            $thumb = imagecreatetruecolor($thumbnailWidth, $newHeight);

            if (in_array($srcMime, ['image/png', 'image/gif'])) {
                imagealphablending($thumb, false);
                imagesavealpha($thumb, true);
                $transparent = imagecolorallocatealpha($thumb, 0, 0, 0, 127);
                imagefill($thumb, 0, 0, $transparent);
            }

            imagecopyresampled($thumb, $source, 0, 0, 0, 0, $thumbnailWidth, $newHeight, $srcWidth, $srcHeight);

            match ($srcMime) {
                'image/jpeg' => imagejpeg($thumb, null, 85),
                'image/png' => imagepng($thumb),
                'image/gif' => imagegif($thumb),
                'image/webp' => imagewebp($thumb, null, 85),
                default => null,
            };

            imagedestroy($source);
            imagedestroy($thumb);
        }, 200, [
            'Content-Type' => $mimeType,
            'Cache-Control' => 'public, max-age=86400',
        ]);
    }
}
