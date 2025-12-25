<?php

namespace Tests\Feature;

use App\Models\Batch;
use App\Models\Exam;
use App\Models\Organization;
use App\Models\Question;
use App\Models\QuestionPaper;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QuestionTest extends TestCase
{
    use RefreshDatabase;

    protected User $teacher;
    protected Organization $organization;
    protected QuestionPaper $paper;

    protected function setUp(): void
    {
        parent::setUp();

        $this->organization = Organization::factory()->create();
        $this->teacher = User::factory()->create(['role' => 'teacher']);
        $this->teacher->organizations()->attach($this->organization->id);

        $this->paper = QuestionPaper::factory()->create([
            'organization_id' => $this->organization->id,
            'created_by' => $this->teacher->id,
        ]);
    }

    /** @test */
    public function can_create_mcq_question_with_options()
    {
        $response = $this->actingAs($this->teacher, 'sanctum')
            ->postJson('/api/questions', [
                'paper_id' => $this->paper->id,
                'question_text' => 'What is 2 + 2?',
                'question_type' => 'mcq',
                'marks' => 5,
                'options' => [
                    ['text' => '3', 'is_correct' => false],
                    ['text' => '4', 'is_correct' => true],
                    ['text' => '5', 'is_correct' => false],
                ],
            ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'Question created successfully',
            ]);

        $this->assertDatabaseHas('questions', [
            'paper_id' => $this->paper->id,
            'question_text' => 'What is 2 + 2?',
            'question_type' => 'mcq',
            'marks' => 5,
        ]);

        $this->assertDatabaseHas('question_options', [
            'option_text' => '4',
            'is_correct' => true,
        ]);
    }

    /** @test */
    public function can_create_short_answer_question()
    {
        $response = $this->actingAs($this->teacher, 'sanctum')
            ->postJson('/api/questions', [
                'paper_id' => $this->paper->id,
                'question_text' => 'Explain photosynthesis.',
                'question_type' => 'short_answer',
                'marks' => 10,
            ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
            ]);

        $this->assertDatabaseHas('questions', [
            'question_text' => 'Explain photosynthesis.',
            'question_type' => 'short_answer',
        ]);
    }

    /** @test */
    public function mcq_must_have_at_least_two_options()
    {
        $response = $this->actingAs($this->teacher, 'sanctum')
            ->postJson('/api/questions', [
                'paper_id' => $this->paper->id,
                'question_text' => 'Test question?',
                'question_type' => 'mcq',
                'marks' => 5,
                'options' => [
                    ['text' => 'Only one option', 'is_correct' => true],
                ],
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['options']);
    }

    /** @test */
    public function mcq_must_have_exactly_one_correct_answer()
    {
        $response = $this->actingAs($this->teacher, 'sanctum')
            ->postJson('/api/questions', [
                'paper_id' => $this->paper->id,
                'question_text' => 'Test question?',
                'question_type' => 'mcq',
                'marks' => 5,
                'options' => [
                    ['text' => 'Option 1', 'is_correct' => true],
                    ['text' => 'Option 2', 'is_correct' => true],
                    ['text' => 'Option 3', 'is_correct' => false],
                ],
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['options']);
    }

    /** @test */
    public function mcq_must_have_at_least_one_correct_answer()
    {
        $response = $this->actingAs($this->teacher, 'sanctum')
            ->postJson('/api/questions', [
                'paper_id' => $this->paper->id,
                'question_text' => 'Test question?',
                'question_type' => 'mcq',
                'marks' => 5,
                'options' => [
                    ['text' => 'Option 1', 'is_correct' => false],
                    ['text' => 'Option 2', 'is_correct' => false],
                ],
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['options']);
    }

    /** @test */
    public function can_list_questions_for_a_paper()
    {
        Question::factory()->count(5)->create(['paper_id' => $this->paper->id]);

        $response = $this->actingAs($this->teacher, 'sanctum')
            ->getJson("/api/questions/paper/{$this->paper->id}");

        $response->assertStatus(200)
            ->assertJsonCount(5, 'data.questions');
    }

    /** @test */
    public function can_view_question_details()
    {
        $question = Question::factory()->create([
            'paper_id' => $this->paper->id,
            'question_type' => 'short_answer',
        ]);

        $response = $this->actingAs($this->teacher, 'sanctum')
            ->getJson("/api/questions/{$question->id}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'id' => $question->id,
                    'question_text' => $question->question_text,
                ],
            ]);
    }

    /** @test */
    public function can_update_question()
    {
        $question = Question::factory()->create([
            'paper_id' => $this->paper->id,
            'question_type' => 'short_answer',
            'marks' => 5,
        ]);

        $response = $this->actingAs($this->teacher, 'sanctum')
            ->putJson("/api/questions/{$question->id}", [
                'paper_id' => $this->paper->id,
                'question_text' => 'Updated question text',
                'question_type' => 'short_answer',
                'marks' => 10,
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'question_text' => 'Updated question text',
                    'marks' => 10,
                ],
            ]);
    }

    /** @test */
    public function cannot_add_question_to_locked_paper()
    {
        $batch = Batch::factory()->create(['organization_id' => $this->organization->id]);

        Exam::factory()->create([
            'organization_id' => $this->organization->id,
            'batch_id' => $batch->id,
            'paper_id' => $this->paper->id,
            'status' => 'published',
        ]);

        $response = $this->actingAs($this->teacher, 'sanctum')
            ->postJson('/api/questions', [
                'paper_id' => $this->paper->id,
                'question_text' => 'New question',
                'question_type' => 'short_answer',
                'marks' => 5,
            ]);

        $response->assertStatus(400)
            ->assertJson([
                'success' => false,
                'message' => 'Cannot add questions to a locked question paper',
            ]);
    }

    /** @test */
    public function cannot_update_question_in_locked_paper()
    {
        $question = Question::factory()->create(['paper_id' => $this->paper->id]);

        $batch = Batch::factory()->create(['organization_id' => $this->organization->id]);

        Exam::factory()->create([
            'organization_id' => $this->organization->id,
            'batch_id' => $batch->id,
            'paper_id' => $this->paper->id,
            'status' => 'published',
        ]);

        $response = $this->actingAs($this->teacher, 'sanctum')
            ->putJson("/api/questions/{$question->id}", [
                'paper_id' => $this->paper->id,
                'question_text' => 'Updated text',
                'question_type' => $question->question_type,
                'marks' => 5,
            ]);

        $response->assertStatus(400)
            ->assertJson([
                'success' => false,
                'message' => 'Cannot update questions in a locked question paper',
            ]);
    }

    /** @test */
    public function can_delete_question()
    {
        $question = Question::factory()->create(['paper_id' => $this->paper->id]);

        $response = $this->actingAs($this->teacher, 'sanctum')
            ->deleteJson("/api/questions/{$question->id}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Question deleted successfully',
            ]);

        $this->assertDatabaseMissing('questions', ['id' => $question->id]);
    }

    /** @test */
    public function cannot_delete_question_from_locked_paper()
    {
        $question = Question::factory()->create(['paper_id' => $this->paper->id]);

        $batch = Batch::factory()->create(['organization_id' => $this->organization->id]);

        Exam::factory()->create([
            'organization_id' => $this->organization->id,
            'batch_id' => $batch->id,
            'paper_id' => $this->paper->id,
            'status' => 'published',
        ]);

        $response = $this->actingAs($this->teacher, 'sanctum')
            ->deleteJson("/api/questions/{$question->id}");

        $response->assertStatus(400)
            ->assertJson([
                'success' => false,
                'message' => 'Cannot delete questions from a locked question paper',
            ]);
    }
}
