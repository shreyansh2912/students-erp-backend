<?php

namespace Tests\Feature;

use App\Models\Batch;
use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\Organization;
use App\Models\Question;
use App\Models\QuestionOption;
use App\Models\QuestionPaper;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudentExamTest extends TestCase
{
    use RefreshDatabase;

    protected User $student;
    protected Student $studentProfile;
    protected Organization $organization;
    protected Batch $batch;
    protected Exam $exam;
    protected QuestionPaper $paper;

    protected function setUp(): void
    {
        parent::setUp();

        $this->organization = Organization::factory()->create();
        $this->batch = Batch::factory()->create(['organization_id' => $this->organization->id]);

        // Create student
        $this->student = User::factory()->create(['role' => 'student']);
        $this->studentProfile = Student::factory()->create([
            'user_id' => $this->student->id,
            'organization_id' => $this->organization->id,
        ]);
        $this->studentProfile->batches()->attach($this->batch->id);

        // Create question paper with MCQ questions
        $this->paper = QuestionPaper::factory()->create([
            'organization_id' => $this->organization->id,
            'total_marks' => 30,
        ]);

        $question1 = Question::factory()->create([
            'paper_id' => $this->paper->id,
            'question_type' => 'mcq',
            'question_text' => 'What is 2 + 2?',
            'marks' => 10,
        ]);
        QuestionOption::create([
            'question_id' => $question1->id,
            'option_text' => '3',
            'is_correct' => false,
        ]);
        QuestionOption::create([
            'question_id' => $question1->id,
            'option_text' => '4',
            'is_correct' => true,
        ]);

        $question2 = Question::factory()->create([
            'paper_id' => $this->paper->id,
            'question_type' => 'mcq',
            'question_text' => 'What is the capital of France?',
            'marks' => 10,
        ]);
        QuestionOption::create([
            'question_id' => $question2->id,
            'option_text' => 'London',
            'is_correct' => false,
        ]);
        QuestionOption::create([
            'question_id' => $question2->id,
            'option_text' => 'Paris',
            'is_correct' => true,
        ]);

        $question3 = Question::factory()->create([
            'paper_id' => $this->paper->id,
            'question_type' => 'short_answer',
            'question_text' => 'Explain gravity.',
            'marks' => 10,
        ]);

        // Create published exam
        $this->exam = Exam::factory()->create([
            'organization_id' => $this->organization->id,
            'batch_id' => $this->batch->id,
            'paper_id' => $this->paper->id,
            'status' => 'published',
            'start_time' => now()->subHour(),
            'end_time' => now()->addHour(),
            'duration_minutes' => 60,
        ]);
    }

    /** @test */
    public function student_can_view_available_exams()
    {
        $response = $this->actingAs($this->student, 'sanctum')
            ->getJson('/api/student/exams');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $this->exam->id);
    }

    /** @test */
    public function student_can_view_exam_questions()
    {
        $response = $this->actingAs($this->student, 'sanctum')
            ->getJson("/api/student/exams/{$this->exam->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'exam',
                    'paper' => [
                        'questions' => [
                            '*' => ['options'],
                        ],
                    ],
                ],
            ]);

        // Verify correct answers are hidden
        $questions = $response->json('data.paper.questions');
        foreach ($questions as $question) {
            if (!empty($question['options'])) {
                foreach ($question['options'] as $option) {
                    $this->assertArrayNotHasKey('is_correct', $option);
                }
            }
        }
    }

    /** @test */
    public function student_can_start_exam_attempt()
    {
        $response = $this->actingAs($this->student, 'sanctum')
            ->postJson("/api/student/exams/{$this->exam->id}/start");

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'Exam started successfully',
            ])
            ->assertJsonStructure([
                'data' => [
                    'attempt',
                    'time_remaining',
                ],
            ]);

        $this->assertDatabaseHas('exam_attempts', [
            'exam_id' => $this->exam->id,
            'student_id' => $this->studentProfile->id,
            'status' => 'in_progress',
        ]);
    }

    /** @test */
    public function student_cannot_start_duplicate_attempt()
    {
        // First attempt
        ExamAttempt::factory()->create([
            'exam_id' => $this->exam->id,
            'student_id' => $this->studentProfile->id,
        ]);

        // Try second attempt
        $response = $this->actingAs($this->student, 'sanctum')
            ->postJson("/api/student/exams/{$this->exam->id}/start");

        $response->assertStatus(400)
            ->assertJson([
                'success' => false,
                'message' => 'You have already attempted this exam',
            ]);
    }

    /** @test */
    public function student_can_save_answers_during_exam()
    {
        $attempt = ExamAttempt::factory()->create([
            'exam_id' => $this->exam->id,
            'student_id' => $this->studentProfile->id,
            'status' => 'in_progress',
        ]);

        $question = $this->paper->questions()->first();
        $option = $question->options()->first();

        $response = $this->actingAs($this->student, 'sanctum')
            ->postJson("/api/student/exams/{$this->exam->id}/answers", [
                'attempt_id' => $attempt->id,
                'question_id' => $question->id,
                'selected_option_id' => $option->id,
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Answer saved successfully',
            ]);

        $this->assertDatabaseHas('exam_answers', [
            'exam_attempt_id' => $attempt->id,
            'question_id' => $question->id,
            'selected_option_id' => $option->id,
        ]);
    }

    /** @test */
    public function student_can_submit_exam()
    {
        $attempt = ExamAttempt::factory()->create([
            'exam_id' => $this->exam->id,
            'student_id' => $this->studentProfile->id,
            'status' => 'in_progress',
        ]);

        // Answer all MCQ questions correctly
        $questions = $this->paper->questions()->where('question_type', 'mcq')->get();
        foreach ($questions as $question) {
            $correctOption = $question->options()->where('is_correct', true)->first();
            \App\Models\ExamAnswer::create([
                'exam_attempt_id' => $attempt->id,
                'question_id' => $question->id,
                'selected_option_id' => $correctOption->id,
            ]);
        }

        $response = $this->actingAs($this->student, 'sanctum')
            ->postJson("/api/student/exams/{$this->exam->id}/submit", [
                'attempt_id' => $attempt->id,
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Exam submitted successfully',
            ])
            ->assertJsonPath('data.attempt.status', 'submitted');

        // Verify score is calculated (2 correct MCQ * 10 marks each = 20)
        $this->assertDatabaseHas('exam_attempts', [
            'id' => $attempt->id,
            'status' => 'submitted',
        ]);

        $this->assertNotNull($attempt->fresh()->score);
    }

    /** @test */
    public function mcq_answers_are_auto_graded()
    {
        $attempt = ExamAttempt::factory()->create([
            'exam_id' => $this->exam->id,
            'student_id' => $this->studentProfile->id,
            'status' => 'in_progress',
        ]);

        // Get the first MCQ question
        $question = $this->paper->questions()->where('question_type', 'mcq')->first();
        $correctOption = $question->options()->where('is_correct', true)->first();

        // Save correct answer
        \App\Models\ExamAnswer::create([
            'exam_attempt_id' => $attempt->id,
            'question_id' => $question->id,
            'selected_option_id' => $correctOption->id,
        ]);

        // Submit exam
        $this->actingAs($this->student, 'sanctum')
            ->postJson("/api/student/exams/{$this->exam->id}/submit", [
                'attempt_id' => $attempt->id,
            ]);

        // Verify marks were awarded
        $this->assertDatabaseHas('exam_answers', [
            'exam_attempt_id' => $attempt->id,
            'question_id' => $question->id,
            'marks_awarded' => $question->marks,
        ]);
    }

    /** @test */
    public function student_cannot_access_exam_outside_time_window()
    {
        $futureExam = Exam::factory()->create([
            'organization_id' => $this->organization->id,
            'batch_id' => $this->batch->id,
            'paper_id' => $this->paper->id,
            'status' => 'published',
            'start_time' => now()->addDay(),
            'end_time' => now()->addDay()->addHours(2),
        ]);

        $response = $this->actingAs($this->student, 'sanctum')
            ->getJson("/api/student/exams/{$futureExam->id}");

        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'message' => 'You do not have access to this exam or it is not currently active',
            ]);
    }

    /** @test */
    public function student_from_different_batch_cannot_access_exam()
    {
        $otherBatch = Batch::factory()->create(['organization_id' => $this->organization->id]);
        $otherExam = Exam::factory()->create([
            'organization_id' => $this->organization->id,
            'batch_id' => $otherBatch->id,
            'paper_id' => $this->paper->id,
            'status' => 'published',
            'start_time' => now()->subHour(),
            'end_time' => now()->addHour(),
        ]);

        $response = $this->actingAs($this->student, 'sanctum')
            ->getJson("/api/student/exams/{$otherExam->id}");

        $response->assertStatus(403);
    }
}
