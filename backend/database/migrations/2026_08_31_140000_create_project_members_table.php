<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_members', function (Blueprint $table) {
            $table->id();

            $table->foreignId('project_id')
                ->constrained('projects')
                ->cascadeOnDelete();

            $table->foreignId('user_id')
                ->constrained('users')
                ->restrictOnDelete();

            $table->string('role', 20)->default('member');
            $table->string('status', 20)->default('invited');
            $table->timestamp('joined_at')->nullable();
            $table->timestamps();

            $table->unique(['project_id', 'user_id']);
            $table->index(['user_id', 'status']);
            $table->index(['project_id', 'status']);
        });

        // Preserve existing projects: their legacy student_id remains the
        // creator/leader and is represented as an active membership.
        DB::table('projects')
            ->orderBy('id')
            ->eachById(function (object $project): void {
                DB::table('project_members')->insert([
                    'project_id' => $project->id,
                    'user_id' => $project->student_id,
                    'role' => 'leader',
                    'status' => 'active',
                    'joined_at' => $project->created_at,
                    'created_at' => $project->created_at,
                    'updated_at' => $project->updated_at,
                ]);
            });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_members');
    }
};
