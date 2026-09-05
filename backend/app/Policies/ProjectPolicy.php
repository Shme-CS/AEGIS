<?php

namespace App\Policies;

use App\Models\Project;
use App\Models\User;

class ProjectPolicy
{
    /**
     * Administrators may perform every lifecycle transition, including on
     * projects outside their assigned program.
     */
    public function before(User $user, string $ability): ?bool
    {
        if (! $user->isActive()) {
            return false;
        }

        return $user->hasRole(User::ROLE_ADMIN) ? true : null;
    }

    /**
     * Authorize project creation.
     *
     * Only active students may create projects.
     *
     * The project program and member eligibility are validated separately
     * by ProjectFormationService.
     */
    public function create(User $user): bool
    {
        return $user->isActive()
            && $user->hasRole(User::ROLE_STUDENT);
    }

    /**
     * Authorize viewing a project.
     *
     * Administrators are handled by before().
     * Project leaders and active project members can view their project.
     * Supervisors and coordinators can view projects within their program.
     */
    public function view(User $user, Project $project): bool
    {
        if (! $user->isActive()) {
            return false;
        }

        // Project leader can view the project.
        if (
            $user->hasRole(User::ROLE_STUDENT)
            && (int) $project->student_id === (int) $user->getKey()
        ) {
            return true;
        }

        // Active project members can view the project.
        if (
            $project->memberships()
                ->where('user_id', $user->getKey())
                ->where('status', 'active')
                ->exists()
        ) {
            return true;
        }

        // Supervisors and coordinators can view projects
        // within their own program.
        return $user->hasRole(
            User::ROLE_SUPERVISOR,
            User::ROLE_COORDINATOR,
        )
            && (int) $user->program_id === (int) $project->program_id;
    }

    /**
     * Authorize the actor for the requested destination status.
     *
     * Students may act only on projects they lead. Academic staff actions are
     * program-scoped so a staff member cannot process another program's work.
     */
    public function transition(User $user, Project $project, string $toStatus): bool
    {
        if (! $user->isActive()) {
            return false;
        }

        return match ($toStatus) {
            'submitted', 'in_progress', 'submitted_final' => $this->isProjectLeader($user, $project),
            'under_review', 'draft', 'approved', 'completed' => $this->hasProgramRole(
                $user,
                $project,
                User::ROLE_SUPERVISOR,
            ),
            'archived' => $this->hasProgramRole(
                $user,
                $project,
                User::ROLE_COORDINATOR,
            ),
            default => false,
        };
    }

    private function isProjectLeader(User $user, Project $project): bool
    {
        return $user->hasRole(User::ROLE_STUDENT)
            && (int) $project->student_id === (int) $user->getKey();
    }

    private function hasProgramRole(User $user, Project $project, string $role): bool
    {
        return $user->hasRole($role)
            && (int) $user->program_id === (int) $project->program_id;
    }
}