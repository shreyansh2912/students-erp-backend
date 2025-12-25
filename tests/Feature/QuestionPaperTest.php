<?php

namespace Tests\Feature;

use App\Models\Batch;
use App\Models\Exam;
use App\Models\Organization;
use App\Models\QuestionPaper;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QuestionPaperTest extends TestCase
{
    use RefreshDatabase;

    protected User $teacher;
    protected User $student;
    protected Organization $organization;

    protected function setUp(): void
    {
        parent::setUp();

        // Create organization
        $this->organization = Organization::factory()->create();

        // Create teacher user
        $this->teacher = User::factory()->create(['role' => 'teacher']);
        $this->teacher->organizations()->attach($this->organization->id);

        // Create student user
        $this->student = User::factory()->create(['role' => 'student']);
        $this->student->organizations()->attach($this->organization->id);
    }

    /** @test */
    public function teacher_can_create_question_paper()
    {
        $response = $this->actingAs($this->teacher, 'sanctum')
            ->postJson('/api/question-papers', [
                'organization_id' => $this->organization->id,
                'title' => 'Mathematics Mid-Term',
                'subject' => 'Mathematics',
                'total_marks' => 100,
            ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'Question paper created successfully',
            ]);

        $this->assertDatabaseHas('question_papers', [
            'title' => 'Mathematics Mid-Term',
            'subject' => 'Mathematics',
            'total_marks' => 100,
            'created_by' => $this->teacher->id,
        ]);
    }

    /** @test */
    public function student_cannot_create_question_paper()
    {
        $response = $this->actingAs($this->student, 'sanctum')
            ->postJson('/api/question-papers', [
                'organization_id' => $this->organization->id,
                'title' => 'Mathematics Mid-Term',
                'subject' => 'Mathematics',
                'total_marks' => 100,
            ]);

        $response->assertStatus(403);
    }

    /** @test */
    public function can_list_question_papers()
    {
        QuestionPaper::factory()->count(3)->create([
            'organization_id' => $this->organization->id,
            'created_by' => $this->teacher->id,
        ]);

        $response = $this->actingAs($this->teacher, 'sanctum')
            ->getJson('/api/question-papers?organization_id=' . $this->organization->id);

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data');
    }

    /** @test */
    public function can_view_question_paper_details()
    {
        $paper = QuestionPaper::factory()->create([
            'organization_id' => $this->organization->id,
            'created_by' => $this->teacher->id,
        ]);

        $response = $this->actingAs($this->teacher, 'sanctum')
            ->getJson("/api/question-papers/{$paper->id}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'id' => $paper->id,
                    'title' => $paper->title,
                ],
            ]);
    }

    /** @test */
    public function can_update_question_paper()
    {
        $paper = QuestionPaper::factory()->create([
            'organization_id' => $this->organization->id,
            'created_by' => $this->teacher->id,
            'title' => 'Old Title',
        ]);

        $response = $this->actingAs($this->teacher, 'sanctum')
            ->putJson("/api/question-papers/{$paper->id}", [
                'organization_id' => $this->organization->id,
                'title' => 'New Title',
                'subject' => $paper->subject,
                'total_marks' => $paper->total_marks,
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'title' => 'New Title',
                ],
            ]);
    }

    /** @test */
    public function cannot_update_locked_question_paper()
    {
        $paper = QuestionPaper::factory()->create([
            'organization_id' => $this->organization->id,
            'created_by' => $this->teacher->id,
        ]);

        $batch = Batch::factory()->create(['organization_id' => $this->organization->id]);

        // Create published exam to lock the paper
        Exam::factory()->create([
            'organization_id' => $this->organization->id,
            'batch_id' => $batch->id,
            'paper_id' => $paper->id,
            'status' => 'published',
        ]);

        $response = $this->actingAs($this->teacher, 'sanctum')
            ->putJson("/api/question-papers/{$paper->id}", [
                'organization_id' => $this->organization->id,
                'title' => 'New Title',
                'subject' => $paper->subject,
                'total_marks' => $paper->total_marks,
            ]);

        $response->assertStatus(400)
            ->assertJson([
                'success' => false,
                'message' => 'Cannot update question paper that is linked to a published exam',
            ]);
    }

    /** @test */
    public function can_delete_question_paper()
    {
        $paper = QuestionPaper::factory()->create([
            'organization_id' => $this->organization->id,
            'created_by' => $this->teacher->id,
        ]);

        $response = $this->actingAs($this->teacher, 'sanctum')
            ->deleteJson("/api/question-papers/{$paper->id}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Question paper deleted successfully',
            ]);

        $this->assertDatabaseMissing('question_papers', ['id' => $paper->id]);
    }

    /** @test */
    public function cannot_delete_locked_question_paper()
    {
        $paper = QuestionPaper::factory()->create([
            'organization_id' => $this->organization->id,
            'created_by' => $this->teacher->id,
        ]);

        $batch = Batch::factory()->create(['organization_id' => $this->organization->id]);

        // Create published exam to lock the paper
        Exam::factory()->create([
            'organization_id' => $this->organization->id,
            'batch_id' => $batch->id,
            'paper_id' => $paper->id,
            'status' => 'published',
        ]);

        $response = $this->actingAs($this->teacher, 'sanctum')
            ->deleteJson("/api/question-papers/{$paper->id}");

        $response->assertStatus(400)
            ->assertJson([
                'success' => false,
                'message' => 'Cannot delete question paper that is linked to a published exam',
            ]);
    }

    /** @test */
    public function validation_fails_without_required_fields()
    {
        $response = $this->actingAs($this->teacher, 'sanctum')
            ->postJson('/api/question-papers', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['organization_id', 'title', 'subject', 'total_marks']);
    }
}
