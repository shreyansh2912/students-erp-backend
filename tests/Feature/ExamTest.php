<?php

namespace Tests\Feature;

use App\Models\Batch;
use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\Organization;
use App\Models\Question;
use App\Models\QuestionPaper;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExamTest extends TestCase
{
    use RefreshDatabase;

    protected User $teacher;
    protected Organization $organization;
    protected Batch $batch;
    protected QuestionPaper $paper;

    protected function setUp(): void
    {
        parent::setUp();

        $this->organization = Organization::factory()->create();
        $this->teacher = User::factory()->create(['role' => 'teacher']);
        $this->teacher->organizations()->attach($this->organization->id);

        $this->batch = Batch::factory()->create(['organization_id' => $this->organization->id]);
        
        $this->paper = QuestionPaper::factory()->create([
            'organization_id' => $this->organization->id,
            'created_by' => $this->teacher->id,
        ]);

        // Add questions to paper
        Question::factory()->count(5)->create(['paper_id' => $this->paper->id]);
    }

    /** @test */
    public function teacher_can_create_exam()
    {
        $response = $this->actingAs($this->teacher, 'sanctum')
            ->postJson('/api/exams', [
                'organization_id' => $this->organization->id,
                'batch_id' => $this->batch->id,
                'paper_id' => $this->paper->id,
                'title' => 'Mid-Term Mathematics Exam',
                'start_time' => now()->addDay()->toDateTimeString(),
                'end_time' => now()->addDay()->addHours(2)->toDateTimeString(),
                'duration_minutes' => 120,
            ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'Exam created successfully',
            ]);

        $this->assertDatabaseHas('exams', [
            'title' => 'Mid-Term Mathematics Exam',
            'status' => 'draft',
        ]);
    }

    /** @test */
    public function can_publish_exam_with_questions()
    {
        $exam = Exam::factory()->create([
            'organization_id' => $this->organization->id,
            'batch_id' => $this->batch->id,
            'paper_id' => $this->paper->id,
            'status' => 'draft',
        ]);

        $response = $this->actingAs($this->teacher, 'sanctum')
            ->postJson("/api/exams/{$exam->id}/publish");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Exam published successfully',
            ]);

        $this->assertDatabaseHas('exams', [
            'id' => $exam->id,
            'status' => 'published',
        ]);
    }

    /** @test */
    public function cannot_publish_exam_without_questions()
    {
        $emptyPaper = QuestionPaper::factory()->create([
            'organization_id' => $this->organization->id,
            'created_by' => $this->teacher->id,
        ]);

        $exam = Exam::factory()->create([
            'organization_id' => $this->organization->id,
            'batch_id' => $this->batch->id,
            'paper_id' => $emptyPaper->id,
            'status' => 'draft',
        ]);

        $response = $this->actingAs($this->teacher, 'sanctum')
            ->postJson("/api/exams/{$exam->id}/publish");

        $response->assertStatus(400)
            ->assertJson([
                'success' => false,
                'message' => 'Cannot publish exam with no questions',
            ]);
    }

    /** @test */
    public function cannot_update_published_exam()
    {
        $exam = Exam::factory()->create([
            'organization_id' => $this->organization->id,
            'batch_id' => $this->batch->id,
            'paper_id' => $this->paper->id,
            'status' => 'published',
        ]);

        $response = $this->actingAs($this->teacher, 'sanctum')
            ->putJson("/api/exams/{$exam->id}", [
                'organization_id' => $this->organization->id,
                'batch_id' => $this->batch->id,
                'paper_id' => $this->paper->id,
                'title' => 'Updated Title',
                'start_time' => now()->addDay()->toDateTimeString(),
                'end_time' => now()->addDay()->addHours(2)->toDateTimeString(),
                'duration_minutes' => 120,
            ]);

        $response->assertStatus(400)
            ->assertJson([
                'success' => false,
                'message' => 'Cannot update published or completed exams',
            ]);
    }

    /** @test */
    public function can_delete_exam_without_attempts()
    {
        $exam = Exam::factory()->create([
            'organization_id' => $this->organization->id,
            'batch_id' => $this->batch->id,
            'paper_id' => $this->paper->id,
            'status' => 'draft',
        ]);

        $response = $this->actingAs($this->teacher, 'sanctum')
            ->deleteJson("/api/exams/{$exam->id}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Exam deleted successfully',
            ]);

        $this->assertDatabaseMissing('exams', ['id' => $exam->id]);
    }

    /** @test */
    public function cannot_delete_exam_with_attempts()
    {
        $exam = Exam::factory()->create([
            'organization_id' => $this->organization->id,
            'batch_id' => $this->batch->id,
            'paper_id' => $this->paper->id,
        ]);

        $student = User::factory()->create(['role' => 'student']);
        $studentProfile = \App\Models\Student::factory()->create([
            'user_id' => $student->id,
            'organization_id' => $this->organization->id,
        ]);

ExamAttempt::factory()->create([
            'exam_id' => $exam->id,
            'student_id' => $studentProfile->id,
        ]);

        $response = $this->actingAs($this->teacher, 'sanctum')
            ->deleteJson("/api/exams/{$exam->id}");

        $response->assertStatus(400)
            ->assertJson([
                'success' => false,
                'message' => 'Cannot delete exam with existing attempts',
            ]);
    }

    /** @test */
    public function can_list_exams_with_filters()
    {
        Exam::factory()->count(3)->create([
            'organization_id' => $this->organization->id,
            'batch_id' => $this->batch->id,
            'paper_id' => $this->paper->id,
            'status' => 'published',
        ]);

        $response = $this->actingAs($this->teacher, 'sanctum')
            ->getJson('/api/exams?organization_id=' . $this->organization->id);

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data');
    }

    /** @test */
    public function can_view_exam_details()
    {
        $exam = Exam::factory()->create([
            'organization_id' => $this->organization->id,
            'batch_id' => $this->batch->id,
            'paper_id' => $this->paper->id,
        ]);

        $response = $this->actingAs($this->teacher, 'sanctum')
            ->getJson("/api/exams/{$exam->id}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'id' => $exam->id,
                    'title' => $exam->title,
                ],
            ]);
    }
}
