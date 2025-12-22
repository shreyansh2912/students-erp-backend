<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * BUSINESS RULES:
     * - Exam is a time-bound instance of a paper
     * - Students can only access exam between start_time and end_time
     * - Exam auto-submits after duration_minutes
     * - Cannot delete exam if any attempts exist
     * - Cannot edit paper once exam is published
     */
    public function up(): void
    {
        Schema::create('exams', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->onDelete('cascade');
            $table->foreignId('batch_id')->constrained()->onDelete('cascade');
            $table->foreignId('paper_id')->constrained('question_papers')->onDelete('cascade');
            $table->string('title');
            $table->dateTime('start_time');
            $table->dateTime('end_time');
            $table->integer('duration_minutes');
            $table->enum('status', ['draft', 'published', 'completed'])->default('draft');
            $table->timestamps();

            $table->index('organization_id');
            $table->index('batch_id');
            $table->index('paper_id');
            $table->index('start_time');
            $table->index('end_time');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('exams');
    }
};
