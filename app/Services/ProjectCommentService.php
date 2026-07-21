<?php

namespace App\Services;

use App\Models\Comment;
use App\Models\CommentAttachment;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ProjectCommentService
{
    /**
     * Create a new comment
     * Returns array with comment and mention information
     */
    public function createComment(string $modelType, int $modelId, User $user, string $content, ?int $parentId = null, array $attachments = []): array
    {
        try {
            list($processedContent, $mentionedUserIds, $mentionedClientIds) = replaceUserMentionsWithLinks($content);

            $comment = Comment::create([
                'commentable_type' => $modelType,
                'commentable_id' => $modelId,
                'content' => $processedContent,
                'commenter_id' => $user->id,
                'commenter_type' => get_class($user),
                'parent_id' => $parentId,
            ]);

            // Save attachments
            if (!empty($attachments)) {
                $this->saveAttachments($comment, $attachments);
            }

            return [
                'comment' => $comment->load('attachments'),
                'mentioned_user_ids' => $mentionedUserIds,
                'mentioned_client_ids' => $mentionedClientIds,
            ];
        } catch (\Exception $e) {
            Log::error('Comment creation error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'model_type' => $modelType,
                'model_id' => $modelId
            ]);
            throw $e;
        }
    }

    /**
     * Update an existing comment
     * Returns array with comment and mention information
     */
    public function updateComment(Comment $comment, string $content): array
    {
        try {
            list($processedContent, $mentionedUserIds, $mentionedClientIds) = replaceUserMentionsWithLinks($content);
            $comment->content = $processedContent;
            $comment->save();
            return [
                'comment' => $comment,
                'mentioned_user_ids' => $mentionedUserIds,
                'mentioned_client_ids' => $mentionedClientIds,
            ];
        } catch (\Exception $e) {
            Log::error('Comment update error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'comment_id' => $comment->id
            ]);
            throw $e;
        }
    }

    /**
     * Delete a comment and its attachments
     */
    public function deleteComment(Comment $comment): bool
    {
        try {
            $attachments = $comment->attachments;

            // Delete attachments from storage
            foreach ($attachments as $attachment) {
                Storage::disk('public')->delete($attachment->file_path);
                $attachment->delete();
            }

            // Permanently delete the comment
            return $comment->forceDelete();
        } catch (\Exception $e) {
            Log::error('Comment deletion error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'comment_id' => $comment->id
            ]);
            throw $e;
        }
    }

    /**
     * Save attachments for a comment
     */
    private function saveAttachments(Comment $comment, array $files): void
    {
        // Create directory if it does not exist
        $directoryPath = storage_path('app/public/comment_attachments');
        if (!is_dir($directoryPath)) {
            mkdir($directoryPath, 0755, true);
        }

        foreach ($files as $file) {
            $path = str_replace('public/', '', $file->store('public/comment_attachments'));
            CommentAttachment::create([
                'comment_id' => $comment->id,
                'file_name' => $file->getClientOriginalName(),
                'file_path' => $path,
                'file_type' => $file->getClientMimeType(),
            ]);
        }
    }

    /**
     * Delete a comment attachment
     */
    public function deleteCommentAttachment(CommentAttachment $attachment): bool
    {
        try {
            Storage::disk('public')->delete($attachment->file_path);
            return $attachment->delete();
        } catch (\Exception $e) {
            Log::error('Comment attachment deletion error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'attachment_id' => $attachment->id
            ]);
            throw $e;
        }
    }
}

