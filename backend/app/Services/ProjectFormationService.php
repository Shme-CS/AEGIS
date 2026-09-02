<?php

namespace App\Services;

use App\Models\Project;
use App\Models\ProjectMember;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ProjectFormationService
{
    /**
     * Return active students in the leader's program who can join a project
     * for the supplied academic year.
     *
     * @return Builder<User>
     */
    public function eligibleStudents(User $leader, string $academicYear): Builder
    {
        return User::query()
            ->where('status', 'active')
            ->where('program_id', $leader->program_id)
            ->whereKeyNot($leader->getKey())
            ->whereDoesntHave('projectMemberships', function (Builder $query) use ($academicYear): void {
                $query->where('status', 'active')
                    ->whereHas('project', function (Builder $projectQuery) use ($academicYear): void {
                        $projectQuery->where('academic_year', $academicYear);
                    });
            });
    }

    /**
     * Create an individual or group project with the creator as its leader.
     *
     * @param  array<string, mixed>  $attributes
     * @param  array<int, int|string>  $memberIds
     */
    public function create(User $leader, array $attributes, array $memberIds = []): Project
    {
        $programId = $attributes['program_id'] ?? null;
        $academicYear = $attributes['academic_year'] ?? null;

        if (! is_int($programId) && ! ctype_digit((string) $programId)) {
            throw ValidationException::withMessages(['program_id' => 'A program is required.']);
        }

        if (! is_string($academicYear) || $academicYear === '') {
            throw ValidationException::withMessages(['academic_year' => 'An academic year is required.']);
        }

        $memberIds = array_values(array_unique(array_map('intval', $memberIds)));
        $memberIds = array_values(array_filter(
            $memberIds,
            fn (int $memberId): bool => $memberId !== $leader->getKey(),
        ));

        return DB::transaction(function () use ($leader, $attributes, $memberIds, $programId, $academicYear): Project {
            $students = User::query()
                ->whereIn('id', [$leader->getKey(), ...$memberIds])
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            $this->ensureEligibleStudents($students->all(), $leader, (int) $programId, $academicYear, $memberIds);

            $project = Project::query()->create([
                ...Arr::only($attributes, [
                    'program_id',
                    'title',
                    'slug',
                    'abstract',
                    'project_type',
                    'academic_year',
                    'status',
                    'submitted_at',
                    'approved_at',
                    'completed_at',
                    'version',
                ]),
                'program_id' => (int) $programId,
                'student_id' => $leader->getKey(),
            ]);

            $project->memberships()->createMany([
                [
                    'user_id' => $leader->getKey(),
                    'role' => 'leader',
                    'status' => 'active',
                    'joined_at' => now(),
                ],
                ...array_map(fn (int $memberId): array => [
                    'user_id' => $memberId,
                    'role' => 'member',
                    'status' => 'active',
                    'joined_at' => now(),
                ], $memberIds),
            ]);

            return $project->load(['leader', 'memberships.user']);
        });
    }

    /**
     * @param  array<int, User>  $students
     * @param  array<int, int>  $memberIds
     */
    private function ensureEligibleStudents(
        array $students,
        User $leader,
        int $programId,
        string $academicYear,
        array $memberIds,
    ): void {
        $requiredIds = [$leader->getKey(), ...$memberIds];

        if (count($students) !== count($requiredIds)) {
            throw ValidationException::withMessages(['members' => 'One or more selected students do not exist.']);
        }

        foreach ($requiredIds as $studentId) {
            $student = $students[$studentId];

            if ($student->status !== 'active') {
                throw ValidationException::withMessages(['members' => 'Only active students may form or join a project.']);
            }

            if ((int) $student->program_id !== $programId) {
                throw ValidationException::withMessages(['members' => 'All project members must belong to the project program.']);
            }
        }

        $alreadyAssigned = ProjectMember::query()
            ->whereIn('user_id', $requiredIds)
            ->where('status', 'active')
            ->whereHas('project', function (Builder $query) use ($academicYear): void {
                $query->where('academic_year', $academicYear);
            })
            ->exists();

        if ($alreadyAssigned) {
            throw ValidationException::withMessages([
                'members' => 'Each student may participate in only one final-year project per academic year.',
            ]);
        }
    }
}
