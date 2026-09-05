<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class RoleAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Route::middleware(['auth:sanctum', 'role:admin'])
            ->get('/api/test/admin-only', function () {
                return response()->json([
                    'message' => 'Admin access granted.',
                ]);
            });
    }

    public function test_admin_can_access_admin_only_route(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'status' => 'active',
        ]);

        $response = $this->actingAs($admin)->getJson('/api/test/admin-only');

        $response->assertOk()
            ->assertJson([
                'message' => 'Admin access granted.',
            ]);
    }

    public function test_student_cannot_access_admin_only_route(): void
    {
        $student = User::factory()->create([
            'role' => User::ROLE_STUDENT,
            'status' => 'active',
        ]);

        $response = $this->actingAs($student)->getJson('/api/test/admin-only');

        $response->assertForbidden()
            ->assertJson([
                'message' => 'You are not authorized to perform this action.',
            ]);
    }

    public function test_guest_cannot_access_admin_only_route(): void
    {
        $response = $this->getJson('/api/test/admin-only');

        $response->assertUnauthorized();
    }
}