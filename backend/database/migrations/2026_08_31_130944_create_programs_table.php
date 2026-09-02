<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('programs', function (Blueprint $table) {
            $table->id();

            $table->foreignId('department_id')
                ->constrained('departments')
                ->cascadeOnDelete();

            $table->string('name', 150);
            $table->string('code', 30);

            $table->text('description')->nullable();

            $table->string('degree_type', 50)->nullable();

            $table->string('status', 20)
                ->default('active');

            $table->timestamps();

            $table->unique(['department_id', 'code']);
            $table->index(['department_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('programs');
    }
};
