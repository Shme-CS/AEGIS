<?php

namespace Tests\Feature;

use App\Models\College;
use App\Models\Department;
use App\Models\Program;
use App\Models\Project;
use App\Models\ProjectMember;
use App\Models\User;
use App\Services\ProjectFormationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class ProjectFormationServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_an_individual_project_with_an_active_leader_membership(): void
    {
        [$program, $leader] = $this->programWithStudent();

        $project = app(ProjectFormationService::class)->create($leader, $this->projectAttributes($program));

        $this->assertSame($leader->id, $project->student_id);
        $this->assertDatabaseHas('project_members', [
            'project_id' => $project->id,
            'user_id' => $leader->id,
            'role' => 'leader',
            'status' => 'active',
        ]);
    }

    public function test_it_creates_a_group_project_for_eligible_students_in_the_same_program(): void
    {
        [$program, $leader] = $this->programWithStudent();
        $member = User::factory()->create(['program_id' => $program->id]);

        $project = app(ProjectFormationService::class)->create(
            $leader,
            $this->projectAttributes($program),
            [$member->id],
        );

        $this->assertSame(2, $project->memberships->count());
        $this->assertDatabaseHas('project_members', [
            'project_id' => $project->id,
            'user_id' => $member->id,
            'role' => 'member',
            'status' => 'active',
        ]);
    }

    public function test_it_rejects_students_already_assigned_to_a_project_in_the_same_year(): void
    {
        [$program, $leader] = $this->programWithStudent();
        $member = User::factory()->create(['program_id' => $program->id]);

        app(ProjectFormationService::class)->create(
            $leader,
            $this->projectAttributes($program),
            [$member->id],
        );

        $newLeader = User::factory()->create(['program_id' => $program->id]);

        $this->expectException(ValidationException::class);

        app(ProjectFormationService::class)->create(
            $newLeader,
            $this->projectAttributes($program, 'A different title'),
            [$member->id],
        );
    }

    public function test_eligible_students_excludes_inactive_wrong_program_and_already_assigned_students(): void
    {
        [$program, $leader] = $this->programWithStudent();
        $eligible = User::factory()->create(['program_id' => $program->id]);
        $inactive = User::factory()->create(['program_id' => $program->id, 'status' => 'inactive']);
        $otherProgram = $this->createProgram();
        $wrongProgram = User::factory()->create(['program_id' => $otherProgram->id]);
        $assigned = User::factory()->create(['program_id' => $program->id]);

        $existing = Project::query()->create([
            ...$this->projectAttributes($program, 'Existing project'),
            'student_id' => $leader->id,
        ]);
        ProjectMember::query()->create([
            'project_id' => $existing->id,
            'user_id' => $assigned->id,
            'role' => 'member',
            'status' => 'active',
            'joined_at' => now(),
        ]);

        $ids = app(ProjectFormationService::class)
            ->eligibleStudents($leader, '2026/2027')
            ->pluck('id')
            ->all();

        $this->assertContains($eligible->id, $ids);
        $this->assertNotContains($inactive->id, $ids);
        $this->assertNotContains($wrongProgram->id, $ids);
        $this->assertNotContains($assigned->id, $ids);
    }

    /** @return array{0: Program, 1: User} */
    private function programWithStudent(): array
    {
        $program = $this->createProgram();

        return [$program, User::factory()->create(['program_id' => $program->id])];
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

    /** @return array<string, mixed> */
    private function projectAttributes(Program $program, string $title = 'Project title'): array
    {
        return [
            'program_id' => $program->id,
            'title' => $title,
            'slug' => str($title)->slug()->toString(),
            'academic_year' => '2026/2027',
        ];
    }
}
