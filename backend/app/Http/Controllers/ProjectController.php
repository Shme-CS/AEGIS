<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateProjectRequest;
use App\Models\Project;
use App\Services\ProjectFormationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

class ProjectController extends Controller
{
    public function __construct(
        private readonly ProjectFormationService $projectFormationService,
    ) {
    }

    /**
     * Create an individual or group project.
     */
    public function store(CreateProjectRequest $request): JsonResponse
    {
        $project = $this->projectFormationService->create(
            $request->user(),
            $request->validated(),
            $request->validated('members', []),
        );

        return response()->json([
            'message' => 'Project created successfully.',
            'project' => $project,
        ], 201);
    }

    /**
     * Display a project.
     */
    public function show(Project $project): JsonResponse
    {
        Gate::authorize('view', $project);

        $project->load([
            'program.department.college',
            'leader',
            'memberships.user',
            'statusHistory.changedBy',
        ]);

        return response()->json([
            'project' => $project,
        ]);
    }
}