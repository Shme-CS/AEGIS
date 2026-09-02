<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('program_id')
                ->nullable()
                ->after('status')
                ->constrained('programs')
                ->nullOnDelete();

            $table->index(['program_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['program_id']);
            $table->dropIndex(['program_id', 'status']);
            $table->dropColumn('program_id');
        });
    }
};
