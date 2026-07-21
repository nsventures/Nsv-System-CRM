<?php

namespace App\Services;

use App\Models\Task;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class TaskMediaService
{
    /**
     * Upload media files to a task
     */
    public function uploadMedia(Task $task, array $mediaFiles): array
    {
        $mediaIds = [];

        foreach ($mediaFiles as $mediaFile) {
            $mediaItem = $task->addMedia($mediaFile)
                ->sanitizingFileName(function ($fileName) {
                    $sanitizedFileName = strtolower(str_replace(['#', '/', '\\', ' '], '-', $fileName));
                    $uniqueId = time() . '_' . mt_rand(1000, 9999);
                    $extension = pathinfo($sanitizedFileName, PATHINFO_EXTENSION);
                    $baseName = pathinfo($sanitizedFileName, PATHINFO_FILENAME);
                    return "{$baseName}-{$uniqueId}.{$extension}";
                })
                ->toMediaCollection('task-media');
            $mediaIds[] = $mediaItem->id;
        }

        return $mediaIds;
    }

    /**
     * Get media files for a task with search and sorting
     */
    public function getMedia(Task $task, ?string $search = null, string $sort = 'id', string $order = 'DESC'): Collection
    {
        $media = $task->getMedia('task-media');

        if ($search) {
            $media = $media->filter(function ($mediaItem) use ($search) {
                return (
                    stripos($mediaItem->id, $search) !== false ||
                    stripos($mediaItem->file_name, $search) !== false ||
                    stripos($mediaItem->created_at->format('Y-m-d'), $search) !== false
                );
            });
        }

        return $order === 'asc'
            ? $media->sortBy($sort)
            : $media->sortByDesc($sort);
    }

    /**
     * Format media for web view
     */
    public function formatMediaForWeb(Collection $media, bool $canDelete = false): Collection
    {
        return $media->map(function ($mediaItem) use ($canDelete) {
            $isPublicDisk = $mediaItem->disk == 'public' ? 1 : 0;
            $fileUrl = $isPublicDisk
                ? asset('storage/task-media/' . $mediaItem->file_name)
                : $mediaItem->getFullUrl();
            $fileExtension = pathinfo($fileUrl, PATHINFO_EXTENSION);
            $imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp'];
            $isImage = in_array(strtolower($fileExtension), $imageExtensions);

            if ($isImage) {
                $html = '<a href="' . $fileUrl . '" data-lightbox="task-media">';
                $html .= '<img src="' . $fileUrl . '" alt="' . $mediaItem->file_name . '" width="50">';
                $html .= '</a>';
            } else {
                $html = '<a href="' . $fileUrl . '" title="' . get_label('download', 'Download') . '">' . $mediaItem->file_name . '</a>';
            }

            $actions = '<a href="' . $fileUrl . '" title="' . get_label('download', 'Download') . '" download>' .
                '<i class="bx bx-download bx-sm"></i>' .
                '</a>';
            if ($canDelete) {
                $actions .= '<button title="' . get_label('delete', 'Delete') . '" type="button" class="btn delete" data-id="' . $mediaItem->id . '" data-type="task-media" data-table="task_media_table">' .
                    '<i class="bx bx-trash text-danger"></i>' .
                    '</button>';
            }

            return [
                'id' => $mediaItem->id,
                'file' => $html,
                'file_name' => $mediaItem->file_name,
                'file_size' => formatSize($mediaItem->size),
                'created_at' => format_date($mediaItem->created_at, true),
                'updated_at' => format_date($mediaItem->updated_at, true),
                'actions' => $actions,
            ];
        });
    }

    /**
     * Format media for API view
     */
    public function formatMediaForApi(Collection $media, bool $canDelete = false): Collection
    {
        return $media->map(function ($mediaItem) use ($canDelete) {
            $isPublicDisk = $mediaItem->disk == 'public' ? 1 : 0;
            $fileUrl = $isPublicDisk
                ? asset('storage/task-media/' . $mediaItem->file_name)
                : $mediaItem->getFullUrl();
            $fileExtension = pathinfo($fileUrl, PATHINFO_EXTENSION);
            $imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp'];
            $isImage = in_array(strtolower($fileExtension), $imageExtensions);
            $previewUrl = $isImage ? $fileUrl : asset('storage/file-icon.png');

            return [
                'id' => $mediaItem->id,
                'file' => $fileUrl,
                'preview' => $previewUrl,
                'file_name' => $mediaItem->file_name,
                'file_size' => formatSize($mediaItem->size),
                'created_at' => format_date($mediaItem->created_at, to_format: 'Y-m-d'),
                'updated_at' => format_date($mediaItem->updated_at, to_format: 'Y-m-d'),
                'can_delete' => $canDelete,
            ];
        });
    }

    /**
     * Delete a media file
     */
    public function deleteMedia(Media $mediaItem): bool
    {
        try {
            Storage::disk($mediaItem->disk)->delete($mediaItem->getPath());
            return $mediaItem->delete();
        } catch (\Exception $e) {
            Log::error('Task media deletion error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'media_id' => $mediaItem->id
            ]);
            throw $e;
        }
    }

    /**
     * Delete multiple media files
     */
    public function deleteMultipleMedia(array $mediaIds): array
    {
        $deletedIds = [];
        $deletedTitles = [];
        $parentIds = [];

        foreach ($mediaIds as $id) {
            $media = Media::find($id);
            if ($media) {
                $deletedIds[] = $id;
                $deletedTitles[] = $media->file_name;
                $parentIds[] = $media->model_id;
                $this->deleteMedia($media);
            }
        }

        return [
            'deleted_ids' => $deletedIds,
            'deleted_titles' => $deletedTitles,
            'parent_ids' => $parentIds
        ];
    }
}











