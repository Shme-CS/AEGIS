<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('projects', function (Blueprint $table) {
            $table->id();

            // Academic ownership
            $table->foreignId('program_id')
                ->constrained('programs')
                ->restrictOnDelete();

            $table->foreignId('student_id')
                ->constrained('users')
                ->restrictOnDelete();

            // Project identity
            $table->string('title', 255);
            $table->string('slug', 255);

            $table->text('abstract')->nullable();

            // Classification
            $table->string('project_type', 30)
                ->default('technical');

            $table->string('academic_year', 20);

            // Lifecycle
            $table->string('status', 30)
                ->default('draft');

            // Submission / approval information
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('completed_at')->nullable();

            // Versioning
            $table->unsignedInteger('version')
                ->default(1);

            $table->timestamps();

            // Prevent duplicate project titles within a program/year.
            $table->unique(
                ['program_id', 'title', 'academic_year'],
                'projects_program_title_year_unique'
            );

            // Common query indexes.
            $table->index(
                ['program_id', 'status'],
                'projects_program_status_index'
            );

            $table->index(
                ['student_id', 'status'],
                'projects_student_status_index'
            );

            $table->index(
                ['academic_year', 'status'],
                'projects_year_status_index'
            );

            $table->index('slug');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('projects');
    }
};
