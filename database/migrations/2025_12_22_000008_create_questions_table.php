<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('paper_id')->constrained('question_papers')->onDelete('cascade');
            $table->enum('type', ['mcq', 'short_answer']);
            $table->text('question_text');
            $table->integer('marks');
            $table->timestamps();

            $table->index('paper_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('questions');
    }
};
