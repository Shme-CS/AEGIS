<?php

namespace Tests\Feature;

use App\Models\College;
use App\Models\Department;
use App\Models\Program;
use App\Models\Project;
use App\Models\User;
use App\Services\ProjectLifecycleService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectLifecycleAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_student_leader_can_submit_and_start_their_project(): void
    {
        [$project, $leader] = $this->project('draft');
        $service = app(ProjectLifecycleService::class);

        $submitted = $service->submit($project, $leader);

        $this->assertSame('submitted', $submitted->status);

        $approved = $this->project('approved', $leader)[0];
        $started = $service->start($approved, $leader);

        $this->assertSame('in_progress', $started->status);
    }

    public function test_a_student_who_does_not_lead_the_project_cannot_submit_it(): void
    {
        [$project] = $this->project('draft');
        $otherStudent = User::factory()->create([
            'program_id' => $project->program_id,
        ]);

        $this->expectException(AuthorizationException::class);

        app(ProjectLifecycleService::class)->submit($project, $otherStudent);
    }

    public function test_only_a_supervisor_in_the_project_program_can_start_review(): void
    {
        [$project] = $this->project('submitted');
        $supervisor = User::factory()->create([
            'program_id' => $project->program_id,
            'role' => User::ROLE_SUPERVISOR,
        ]);

        $updated = app(ProjectLifecycleService::class)->startReview($project, $supervisor);

        $this->assertSame('under_review', $updated->status);
    }

    public function test_supervisor_from_another_program_cannot_start_review(): void
    {
        [$project] = $this->project('submitted');
        $supervisor = User::factory()->create([
            'program_id' => $this->createProgram()->id,
            'role' => User::ROLE_SUPERVISOR,
        ]);

        $this->expectException(AuthorizationException::class);

        app(ProjectLifecycleService::class)->startReview($project, $supervisor);
    }

    public function test_a_supervisor_can_approve_or_request_revision(): void
    {
        [$project] = $this->project('under_review');
        $supervisor = User::factory()->create([
            'program_id' => $project->program_id,
            'role' => User::ROLE_SUPERVISOR,
        ]);

        $approved = app(ProjectLifecycleService::class)->approve($project, $supervisor);

        $this->assertSame('approved', $approved->status);

        $sameProgramLeader = User::factory()->create([
            'program_id' => $project->program_id,
        ]);
        [$revisionProject] = $this->project('under_review', $sameProgramLeader);
        $revised = app(ProjectLifecycleService::class)->requestRevision($revisionProject, $supervisor);

        $this->assertSame('draft', $revised->status);
    }

    public function test_coordinator_from_same_program_cannot_approve_a_project(): void
    {
        [$project] = $this->project('under_review');
        $coordinator = User::factory()->create([
            'program_id' => $project->program_id,
            'role' => User::ROLE_COORDINATOR,
        ]);

        $this->expectException(AuthorizationException::class);

        app(ProjectLifecycleService::class)->approve($project, $coordinator);
    }

    public function test_a_supervisor_can_complete_a_final_submission(): void
    {
        [$project] = $this->project('submitted_final');
        $supervisor = User::factory()->create([
            'program_id' => $project->program_id,
            'role' => User::ROLE_SUPERVISOR,
        ]);

        $completed = app(ProjectLifecycleService::class)->complete($project, $supervisor);

        $this->assertSame('completed', $completed->status);
    }

    public function test_a_coordinator_can_archive_a_completed_project(): void
    {
        [$project] = $this->project('completed');
        $coordinator = User::factory()->create([
            'program_id' => $project->program_id,
            'role' => User::ROLE_COORDINATOR,
        ]);

        $archived = app(ProjectLifecycleService::class)->archive($project, $coordinator);

        $this->assertSame('archived', $archived->status);
    }

    public function test_inactive_supervisor_cannot_review_project(): void
    {
        [$project] = $this->project('submitted');
        $supervisor = User::factory()->create([
            'program_id' => $project->program_id,
            'role' => User::ROLE_SUPERVISOR,
            'status' => 'inactive',
        ]);

        $this->expectException(AuthorizationException::class);

        app(ProjectLifecycleService::class)->startReview($project, $supervisor);
    }

    public function test_an_active_administrator_can_transition_any_project(): void
    {
        [$project] = $this->project('submitted');
        $administrator = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'program_id' => null,
        ]);

        $updated = app(ProjectLifecycleService::class)->startReview($project, $administrator);

        $this->assertSame('under_review', $updated->status);
    }

    /**
     * @return array{0: Project, 1: User}
     */
    private function project(string $status, ?User $leader = null): array
    {
        $program = $leader?->program ?? $this->createProgram();
        $leader ??= User::factory()->create(['program_id' => $program->id]);

        $project = Project::query()->create([
            'program_id' => $program->id,
            'student_id' => $leader->id,
            'title' => 'Project '.uniqid(),
            'slug' => 'project-'.uniqid(),
            'academic_year' => '2026/2027',
            'status' => $status,
        ]);

        return [$project, $leader];
    }

    private function createProgram(): Program
    {
        $college = College::query()->create([
            'name' => 'College '.uniqid(),
            'code' => 'C'.uniqid(),
        ]);
        $department = Department::query()->create([
            'college_id' => $college->id,
            'name' => 'Department '.uniqid(),
            'code' => 'D'.uniqid(),
        ]);

        return Program::query()->create([
            'department_id' => $department->id,
            'name' => 'Program '.uniqid(),
            'code' => 'P'.uniqid(),
        ]);
    }
}
