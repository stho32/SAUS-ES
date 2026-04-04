<?php
declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Ticket;
use App\Models\TicketAttachment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AttachmentApiController extends Controller
{
    public function store(Request $request, Ticket $ticket): JsonResponse
    {
        $allowedExtensions = config('saus.allowed_file_types', [
            'jpg', 'jpeg', 'png', 'gif', 'pdf', 'doc', 'docx', 'xls', 'xlsx', 'txt',
        ]);
        $maxSize = (int) (config('saus.max_file_size', 10 * 1024 * 1024) / 1024); // bytes to KB

        $request->validate([
            'file' => ['required', 'file', 'max:' . $maxSize],
        ]);

        $file = $request->file('file');
        $extension = strtolower($file->getClientOriginalExtension());

        if (!in_array($extension, $allowedExtensions)) {
            return response()->json([
                'success' => false,
                'message' => 'Dateityp "' . $extension . '" nicht erlaubt. Erlaubt: ' . implode(', ', $allowedExtensions),
            ], 422);
        }

        $safeFilename = bin2hex(random_bytes(16)) . '.' . $file->getClientOriginalExtension();
        $uploadPath = config('saus.upload_path', 'uploads/tickets');
        $ticketDir = $uploadPath . '/' . $ticket->id;

        $file->storeAs($ticketDir, $safeFilename, 'local');

        $attachment = TicketAttachment::create([
            'ticket_id' => $ticket->id,
            'filename' => $safeFilename,
            'original_filename' => $file->getClientOriginalName(),
            'file_type' => $file->getMimeType(),
            'file_size' => $file->getSize(),
            'uploaded_by' => session('username', 'Unbekannt'),
            'upload_date' => now(),
        ]);

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $attachment->id,
                'filename' => $attachment->filename,
                'original_filename' => $attachment->original_filename,
                'file_type' => $attachment->file_type,
                'file_size' => $attachment->file_size,
                'uploaded_by' => $attachment->uploaded_by,
                'upload_date' => $attachment->upload_date->toIso8601String(),
            ],
        ]);
    }

    public function show(Request $request, TicketAttachment $attachment): StreamedResponse
    {
        $uploadPath = config('saus.upload_path', 'uploads/tickets');
        $filePath = $uploadPath . '/' . $attachment->ticket_id . '/' . $attachment->filename;

        if (!Storage::disk('local')->exists($filePath)) {
            abort(404, 'Datei nicht gefunden.');
        }

        $isThumbnail = $request->query('thumbnail') === 'true';
        $isImage = str_starts_with($attachment->file_type, 'image/');

        if ($isThumbnail && $isImage) {
            return $this->serveThumbnail($filePath, $attachment);
        }

        return Storage::disk('local')->response($filePath, $attachment->original_filename, [
            'Content-Type' => $attachment->file_type,
        ]);
    }

    public function destroy(TicketAttachment $attachment): JsonResponse
    {
        $uploadPath = config('saus.upload_path', 'uploads/tickets');
        $filePath = $uploadPath . '/' . $attachment->ticket_id . '/' . $attachment->filename;

        if (Storage::disk('local')->exists($filePath)) {
            Storage::disk('local')->delete($filePath);
        }

        $attachment->delete();

        return response()->json(['success' => true, 'data' => null]);
    }

    private function serveThumbnail(string $filePath, TicketAttachment $attachment): StreamedResponse
    {
        $thumbnailWidth = config('saus.thumbnail_width', 200);
        $fullPath = Storage::disk('local')->path($filePath);

        return response()->stream(function () use ($fullPath, $attachment, $thumbnailWidth) {
            $imageInfo = getimagesize($fullPath);

            if (!$imageInfo) {
                readfile($fullPath);
                return;
            }

            $srcWidth = $imageInfo[0];
            $srcHeight = $imageInfo[1];
            $mimeType = $imageInfo['mime'];

            $ratio = $thumbnailWidth / $srcWidth;
            $newHeight = (int) round($srcHeight * $ratio);

            $source = match ($mimeType) {
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

            // Preserve transparency for PNG and GIF
            if (in_array($mimeType, ['image/png', 'image/gif'])) {
                imagealphablending($thumb, false);
                imagesavealpha($thumb, true);
                $transparent = imagecolorallocatealpha($thumb, 0, 0, 0, 127);
                imagefill($thumb, 0, 0, $transparent);
            }

            imagecopyresampled($thumb, $source, 0, 0, 0, 0, $thumbnailWidth, $newHeight, $srcWidth, $srcHeight);

            match ($mimeType) {
                'image/jpeg' => imagejpeg($thumb, null, 85),
                'image/png' => imagepng($thumb),
                'image/gif' => imagegif($thumb),
                'image/webp' => imagewebp($thumb, null, 85),
                default => null,
            };

            imagedestroy($source);
            imagedestroy($thumb);
        }, 200, [
            'Content-Type' => $attachment->file_type,
            'Cache-Control' => 'public, max-age=86400',
        ]);
    }
}
