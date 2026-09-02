<?php

namespace Tests\Feature;

use App\Models\College;
use App\Models\Department;
use App\Models\Program;
use App\Models\Project;
use App\Models\User;
use App\Services\ProjectLifecycleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class ProjectLifecycleServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_submits_a_draft_project(): void
    {
        [$project, $student] = $this->createProject();

        $updatedProject = app(ProjectLifecycleService::class)
            ->submit($project, $student);

        $this->assertSame('submitted', $updatedProject->status);

        $this->assertDatabaseHas('project_status_histories', [
            'project_id' => $project->id,
            'from_status' => 'draft',
            'to_status' => 'submitted',
            'changed_by' => $student->id,
        ]);
    }

    public function test_it_starts_review_for_a_submitted_project(): void
    {
        [$project, $student] = $this->createProject([
            'status' => 'submitted',
        ]);

        $supervisor = User::factory()->create([
            'program_id' => $student->program_id,
            'role' => User::ROLE_SUPERVISOR,
        ]);

        $updatedProject = app(ProjectLifecycleService::class)
            ->startReview($project, $supervisor);

        $this->assertSame('under_review', $updatedProject->status);

        $this->assertDatabaseHas('project_status_histories', [
            'project_id' => $project->id,
            'from_status' => 'submitted',
            'to_status' => 'under_review',
            'changed_by' => $supervisor->id,
        ]);
    }

    public function test_it_approves_a_project_under_review(): void
    {
        [$project, $student] = $this->createProject([
            'status' => 'under_review',
        ]);

        $supervisor = User::factory()->create([
            'program_id' => $student->program_id,
            'role' => User::ROLE_SUPERVISOR,
        ]);

        $updatedProject = app(ProjectLifecycleService::class)
            ->approve($project, $supervisor);

        $this->assertSame('approved', $updatedProject->status);
        $this->assertNotNull($updatedProject->approved_at);
    }

    public function test_it_rejects_an_invalid_transition(): void
    {
        [$project, $student] = $this->createProject();

        $this->expectException(ValidationException::class);

        app(ProjectLifecycleService::class)
            ->approve($project, $student);
    }

    public function test_every_transition_creates_status_history(): void
    {
        [$project, $student] = $this->createProject();

        $service = app(ProjectLifecycleService::class);

        $service->submit($project, $student);

        $this->assertDatabaseCount('project_status_histories', 1);
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return array{0: Project, 1: User}
     */
    private function createProject(array $attributes = []): array
    {
        $program = $this->createProgram();

        $student = User::factory()->create([
            'program_id' => $program->id,
        ]);

        $project = Project::query()->create([
            'program_id' => $program->id,
            'student_id' => $student->id,
            'title' => 'Project '.uniqid(),
            'slug' => 'project-'.uniqid(),
            'academic_year' => '2026/2027',
            'status' => 'draft',
            ...$attributes,
        ]);

        return [$project, $student];
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
