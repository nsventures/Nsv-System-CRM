<?php

namespace Database\Seeders;

use App\Models\CandidateStatus;
use Illuminate\Database\Seeder;

class CandidateStatusSeeder extends Seeder
{
    /**
     * Seed a default candidate hiring pipeline.
     *
     * Candidate creation requires a status (status_id is required), so a fresh install
     * with an empty candidate_statuses table blocks adding candidates. This provides a
     * sensible default pipeline. It is idempotent: existing statuses (matched by name)
     * are left untouched, so it is safe to re-run and won't clobber custom statuses.
     */
    public function run(): void
    {
        $defaults = [
            ['name' => 'Applied',   'color' => 'info',      'order' => 1],
            ['name' => 'Screening', 'color' => 'primary',   'order' => 2],
            ['name' => 'Interview', 'color' => 'warning',   'order' => 3],
            ['name' => 'Offer',     'color' => 'secondary', 'order' => 4],
            ['name' => 'Hired',     'color' => 'success',   'order' => 5],
            ['name' => 'Rejected',  'color' => 'danger',    'order' => 6],
        ];

        foreach ($defaults as $status) {
            CandidateStatus::firstOrCreate(
                ['name' => $status['name']],
                ['color' => $status['color'], 'order' => $status['order']]
            );
        }
    }
}
