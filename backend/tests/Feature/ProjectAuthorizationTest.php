<?php

namespace Tests\Feature;

use App\Models\Program;
use App\Models\Project;
use App\Models\ProjectMember;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_view_a_project(): void
    {
        [$project] = $this->projectWithLeader();

        $this->getJson($this->projectUrl($project))
            ->assertUnauthorized();
    }

    public function test_project_leader_can_view_their_project(): void
    {
        [$project, $leader] = $this->projectWithLeader();

        $this->viewProjectAs($leader, $project)
            ->assertOk()
            ->assertJsonPath('project.id', $project->id);
    }

    public function test_active_project_member_can_view_the_project(): void
    {
        [$project] = $this->projectWithLeader();
        $member = User::factory()->student()->for($project->program)->create();

        ProjectMember::factory()
            ->for($project)
            ->for($member)
            ->active()
            ->create();

        $this->viewProjectAs($member, $project)
            ->assertOk()
            ->assertJsonPath('project.id', $project->id);
    }

    public function test_same_program_supervisor_can_view_a_project(): void
    {
        [$project] = $this->projectWithLeader();
        $supervisor = User::factory()->supervisor()->for($project->program)->create();

        $this->viewProjectAs($supervisor, $project)
            ->assertOk();
    }

    public function test_supervisor_from_another_program_cannot_view_a_project(): void
    {
        [$project] = $this->projectWithLeader();
        $supervisor = User::factory()->supervisor()->for(Program::factory())->create();

        $this->viewProjectAs($supervisor, $project)
            ->assertForbidden();
    }

    public function test_same_program_coordinator_can_view_a_project(): void
    {
        [$project] = $this->projectWithLeader();
        $coordinator = User::factory()->coordinator()->for($project->program)->create();

        $this->viewProjectAs($coordinator, $project)
            ->assertOk();
    }

    public function test_coordinator_from_another_program_cannot_view_a_project(): void
    {
        [$project] = $this->projectWithLeader();
        $coordinator = User::factory()->coordinator()->for(Program::factory())->create();

        $this->viewProjectAs($coordinator, $project)
            ->assertForbidden();
    }

    public function test_active_administrator_can_view_any_project(): void
    {
        [$project] = $this->projectWithLeader();
        $administrator = User::factory()->administrator()->create();

        $this->viewProjectAs($administrator, $project)
            ->assertOk();
    }

    public function test_unrelated_student_cannot_view_a_project(): void
    {
        [$project] = $this->projectWithLeader();
        $student = User::factory()->student()->for($project->program)->create();

        $this->viewProjectAs($student, $project)
            ->assertForbidden();
    }

    public function test_inactive_supervisor_cannot_view_a_project_in_their_program(): void
    {
        [$project] = $this->projectWithLeader();
        $supervisor = User::factory()
            ->supervisor()
            ->inactive()
            ->for($project->program)
            ->create();

        $this->viewProjectAs($supervisor, $project)
            ->assertForbidden();
    }

    /**
     * @return array{0: Project, 1: User}
     */
    private function projectWithLeader(): array
    {
        $program = Program::factory()->create();
        $leader = User::factory()->student()->for($program)->create();

        return [
            Project::factory()
                ->for($program)
                ->for($leader, 'leader')
                ->create(),
            $leader,
        ];
    }

    private function projectUrl(Project $project): string
    {
        return '/api/projects/'.$project->id;
    }

    private function viewProjectAs(User $user, Project $project): \Illuminate\Testing\TestResponse
    {
        return $this->actingAs($user, 'sanctum')
            ->getJson($this->projectUrl($project));
    }
}
