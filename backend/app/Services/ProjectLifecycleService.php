<?php

namespace App\Services;

use App\Models\Project;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class ProjectLifecycleService
{
    /**
     * @var array<string, list<string>>
     */
    private const ALLOWED_TRANSITIONS = [
        'draft' => ['submitted'],
        'submitted' => ['under_review'],
        'under_review' => ['draft', 'approved'],
        'approved' => ['in_progress'],
        'in_progress' => ['submitted_final'],
        'submitted_final' => ['completed'],
        'completed' => ['archived'],
        'archived' => [],
    ];

    public function submit(Project $project, User $changedBy, ?string $note = null): Project
    {
        return $this->transition($project, 'submitted', $changedBy, $note);
    }

    public function startReview(Project $project, User $changedBy, ?string $note = null): Project
    {
        return $this->transition($project, 'under_review', $changedBy, $note);
    }

    public function approve(Project $project, User $changedBy, ?string $note = null): Project
    {
        return $this->transition($project, 'approved', $changedBy, $note);
    }

    /**
     * Return a project to draft when a reviewer requests revisions.
     */
    public function requestRevision(Project $project, User $changedBy, ?string $note = null): Project
    {
        return $this->transition($project, 'draft', $changedBy, $note);
    }

    /**
     * Backwards-compatible name for a reviewer rejecting a proposal for revision.
     */
    public function reject(Project $project, User $changedBy, ?string $note = null): Project
    {
        return $this->requestRevision($project, $changedBy, $note);
    }

    public function start(Project $project, User $changedBy, ?string $note = null): Project
    {
        return $this->transition($project, 'in_progress', $changedBy, $note);
    }

    public function submitFinal(Project $project, User $changedBy, ?string $note = null): Project
    {
        return $this->transition($project, 'submitted_final', $changedBy, $note);
    }

    public function complete(Project $project, User $changedBy, ?string $note = null): Project
    {
        return $this->transition($project, 'completed', $changedBy, $note);
    }

    public function archive(Project $project, User $changedBy, ?string $note = null): Project
    {
        return $this->transition($project, 'archived', $changedBy, $note);
    }

    /**
     * Apply a valid status change and write its audit record atomically.
     */
    public function transition(
        Project $project,
        string $toStatus,
        User $changedBy,
        ?string $note = null,
    ): Project {
        return DB::transaction(function () use ($project, $toStatus, $changedBy, $note): Project {
            $lockedProject = Project::query()
                ->lockForUpdate()
                ->findOrFail($project->getKey());

            $fromStatus = $lockedProject->status;

            if (! in_array($toStatus, self::ALLOWED_TRANSITIONS[$fromStatus] ?? [], true)) {
                throw ValidationException::withMessages([
                    'status' => ["The project cannot transition from {$fromStatus} to {$toStatus}."],
                ]);
            }

            Gate::forUser($changedBy)->authorize('transition', [$lockedProject, $toStatus]);

            $attributes = ['status' => $toStatus];

            if ($toStatus === 'submitted') {
                $attributes['submitted_at'] = now();
            }

            if ($toStatus === 'approved') {
                $attributes['approved_at'] = now();
            }

            if ($toStatus === 'completed') {
                $attributes['completed_at'] = now();
            }

            $lockedProject->update($attributes);

            $lockedProject->statusHistory()->create([
                'from_status' => $fromStatus,
                'to_status' => $toStatus,
                'changed_by' => $changedBy?->getKey(),
                'note' => $note,
            ]);

            return $lockedProject->refresh();
        });
    }
}
