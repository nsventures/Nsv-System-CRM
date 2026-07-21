<?php

namespace App\Services;

use App\Models\Priority;
use App\Models\Status;
use Illuminate\Support\Collection;

class ProjectMetaService
{
    /**
     * Get all statuses formatted for API
     */
    public function getStatuses(): Collection
    {
        return Status::select('id', 'title', 'color')
            ->get()
            ->map(function ($status) {
                return [
                    'id' => $status->id,
                    'title' => $status->title ?? $status->name ?? 'Untitled',
                    'color' => $status->color ?? '#6c757d',
                ];
            });
    }

    /**
     * Get all priorities formatted for API
     */
    public function getPriorities(): Collection
    {
        return Priority::select('id', 'title', 'color')
            ->get()
            ->map(function ($priority) {
                return [
                    'id' => $priority->id,
                    'title' => $priority->title ?? $priority->name ?? 'Untitled',
                    'color' => $priority->color ?? '#6c757d',
                ];
            });
    }
}

