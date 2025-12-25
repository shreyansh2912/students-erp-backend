<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BatchController;
use App\Http\Controllers\Api\ExamController;
use App\Http\Controllers\Api\OrganizationController;
use App\Http\Controllers\Api\QuestionController;
use App\Http\Controllers\Api\QuestionPaperController;
use App\Http\Controllers\Api\ResultController;
use App\Http\Controllers\Api\StudentController;
use App\Http\Controllers\Api\StudentExamController;
use App\Http\Middleware\EnsureUserHasRole;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Public routes
Route::post('/login', [AuthController::class, 'login'])
    ->middleware('throttle:login')
    ->name('login');
Route::post('/invitations/{token}/accept', [OrganizationController::class, 'acceptInvitation'])
    ->middleware('throttle:invitations')
    ->name('invitations.accept');

// Protected routes (require authentication)
Route::middleware('auth:sanctum')->group(function () {
    
    // Authentication routes
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/me', [AuthController::class, 'me'])->name('me');

    // Organization routes
    Route::prefix('organizations')->name('organizations.')->group(function () {
        Route::get('/', [OrganizationController::class, 'index'])->name('index');
        Route::post('/', [OrganizationController::class, 'store'])->name('store');
        Route::get('/{organization}', [OrganizationController::class, 'show'])->name('show');
        Route::put('/{organization}', [OrganizationController::class, 'update'])->name('update');
        Route::delete('/{organization}', [OrganizationController::class, 'destroy'])->name('destroy');
        
        // Teacher invitation (teachers and admins only)
        Route::post('/{organization}/invite-teacher', [OrganizationController::class, 'inviteTeacher'])
            ->middleware(EnsureUserHasRole::class . ':admin,teacher')
            ->name('invite-teacher');
        
        // Students management (teachers only)
        Route::get('/{organization}/students', [StudentController::class, 'index'])
            ->middleware(EnsureUserHasRole::class . ':admin,teacher')
            ->name('students.index');
        Route::post('/{organization}/students', [StudentController::class, 'store'])
            ->middleware(EnsureUserHasRole::class . ':admin,teacher')
            ->name('students.store');
    });

    // Student routes (teachers only)
    Route::prefix('students')->middleware(EnsureUserHasRole::class . ':admin,teacher')->name('students.')->group(function () {
        Route::get('/{student}', [StudentController::class, 'show'])->name('show');
        Route::put('/{student}', [StudentController::class, 'update'])->name('update');
        Route::delete('/{student}', [StudentController::class, 'destroy'])->name('destroy');
    });

    // Batch routes
    Route::prefix('batches')->name('batches.')->group(function () {
        Route::get('/', [BatchController::class, 'index'])->name('index');
        
        // Teachers only for batch management
        Route::middleware(EnsureUserHasRole::class . ':admin,teacher')->group(function () {
            Route::post('/', [BatchController::class, 'store'])->name('store');
            Route::get('/{batch}', [BatchController::class, 'show'])->name('show');
            Route::put('/{batch}', [BatchController::class, 'update'])->name('update');
            Route::delete('/{batch}', [BatchController::class, 'destroy'])->name('destroy');
            
            // Batch student management
            Route::post('/{batch}/students', [BatchController::class, 'addStudent'])->name('add-student');
            Route::delete('/{batch}/students', [BatchController::class, 'removeStudent'])->name('remove-student');
        });
    });

    // Question Paper routes (teachers only)
    Route::prefix('question-papers')->middleware(EnsureUserHasRole::class . ':admin,teacher')->name('papers.')->group(function () {
        Route::get('/', [QuestionPaperController::class, 'index'])->name('index');
        Route::post('/', [QuestionPaperController::class, 'store'])->name('store');
        Route::get('/{paper}', [QuestionPaperController::class, 'show'])->name('show');
        Route::put('/{paper}', [QuestionPaperController::class, 'update'])->name('update');
        Route::delete('/{paper}', [QuestionPaperController::class, 'destroy'])->name('destroy');
    });

    // Questions routes (teachers only)
    Route::prefix('questions')->middleware(EnsureUserHasRole::class . ':admin,teacher')->name('questions.')->group(function () {
        Route::get('/paper/{paper}', [QuestionController::class, 'index'])->name('index');
        Route::post('/', [QuestionController::class, 'store'])->name('store');
        Route::get('/{question}', [QuestionController::class, 'show'])->name('show');
        Route::put('/{question}', [QuestionController::class, 'update'])->name('update');
        Route::delete('/{question}', [QuestionController::class, 'destroy'])->name('destroy');
    });

    // Exam routes (teachers only)
    Route::prefix('exams')->middleware(EnsureUserHasRole::class . ':admin,teacher')->name('exams.')->group(function () {
        Route::get('/', [ExamController::class, 'index'])->name('index');
        Route::post('/', [ExamController::class, 'store'])->name('store');
        Route::get('/{exam}', [ExamController::class, 'show'])->name('show');
        Route::put('/{exam}', [ExamController::class, 'update'])->name('update');
        Route::delete('/{exam}', [ExamController::class, 'destroy'])->name('destroy');
        Route::post('/{exam}/publish', [ExamController::class, 'publish'])->name('publish');
    });

    // Student exam routes (students only)
    Route::prefix('student/exams')->middleware(EnsureUserHasRole::class . ':student')->name('student.exams.')->group(function () {
        Route::get('/', [StudentExamController::class, 'index'])->name('index'); // Available exams
        Route::get('/{exam}', [StudentExamController::class, 'show'])->name('show'); // View questions
        Route::post('/{exam}/start', [StudentExamController::class, 'start'])->name('start'); // Start attempt
        Route::post('/{exam}/answers', [StudentExamController::class, 'saveAnswer'])->name('save-answer'); // Save answer
        Route::post('/{exam}/submit', [StudentExamController::class, 'submit'])->name('submit'); // Submit exam
    });

    // AI routes (teachers and admins only)
    Route::prefix('ai')
        ->middleware(EnsureUserHasRole::class . ':admin,teacher')
        ->name('ai.')
        ->group(function () {
            Route::post('/generate-questions', [App\Http\Controllers\Api\AIController::class, 'generateQuestions'])
                ->name('generate-questions');
            Route::post('/questions/{question}/refine', [App\Http\Controllers\Api\AIController::class, 'refineQuestion'])
                ->name('refine-question');
            Route::post('/generate-options', [App\Http\Controllers\Api\AIController::class, 'generateOptions'])
                ->name('generate-options');
        });

    // Result routes
    Route::prefix('results')->name('results.')->group(function () {
        // Teachers can view all exam results
        Route::get('/exams/{exam}', [ResultController::class, 'examResults'])
            ->middleware(EnsureUserHasRole::class . ':admin,teacher')
            ->name('exam');
        
        // Students can view their own results
        Route::get('/exams/{exam}/my-result', [ResultController::class, 'studentExamResult'])
            ->middleware(EnsureUserHasRole::class . ':student')
            ->name('student-exam');
        
        // Performance analytics
        Route::get('/students/{student?}/performance', [ResultController::class, 'studentPerformance'])
            ->name('student-performance');
    });
});
