<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('departments', function (Blueprint $table) {
            $table->id();

            $table->foreignId('college_id')
                ->constrained('colleges')
                ->cascadeOnDelete();

            $table->string('name', 150);
            $table->string('code', 30);

            $table->text('description')->nullable();

            $table->string('status', 20)
                ->default('active');

            $table->timestamps();

            $table->unique(['college_id', 'code']);
            $table->index(['college_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('departments');
    }
};
