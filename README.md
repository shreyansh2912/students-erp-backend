# Student ERP Backend

Production-grade Laravel 12 backend for an exam-first education platform used by coaching institutes.

## Tech Stack

- **Framework**: Laravel 12
- **PHP**: 8.2+
- **Database**: MySQL 8
- **Authentication**: Laravel Sanctum (API tokens)
- **Architecture**: Multi-tenant (organization-based)

## Features

### ✅ Multi-Tenant System
- Organizations with owner management
- Teacher invitation via email with token-based acceptance
- Students with separated profile data
- Role-based access control (admin, teacher, student)

### ✅ Batch Management
- Organize students into batches
- Subject-based grouping
- Attendance tracking

### ✅ Question Paper & Question Management
- **Create reusable question papers** with title and subject
- **Add MCQ and short answer questions** to papers
- **Question options** with correct answer marking
- **Locked paper protection**: Papers cannot be edited once linked to published exam

### ✅ Exam System
- **Reusable question papers** (MCQ & short answer)
- **Time-bound exams** (start_time → end_time)
- **Auto-submit** after duration minutes
- **Access control**: Students only access exams in their batch during active window
- **Business rules**:
  - Papers locked once exam published
  - Cannot delete exam with attempts
  - Prevent duplicate attempts per student

### ✅ Auto-Grading & Results
- MCQ questions auto-graded on submission
- Score calculation
- Performance analytics (average, highest, lowest scores)
- Pass rate calculation

---

## Installation

### Prerequisites
- PHP 8.2 or higher
- Composer
- MySQL 8

### Setup Steps

1. **Install dependencies**
   ```bash
   composer install
   ```

2. **Configure environment**
   ```bash
   copy .env.example .env
   ```

   Update `.env` with your database credentials:
   ```
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=student_erp
   DB_USERNAME=root
   DB_PASSWORD=your_password
   ```

3. **Generate application key**
   ```bash
   php artisan key:generate
   ```

4. **Run migrations**
   ```bash
   php artisan migrate
   ```

5. **Install Laravel Sanctum** (if not already installed)
   ```bash
   composer require laravel/sanctum
   php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider"
   ```

6. **Start development server**
   ```bash
   php artisan serve
   ```

   API will be available at: `http://localhost:8000/api`

---

## API Documentation

### Authentication

#### Login
```
POST /api/login
Content-Type: application/json

{
  "email": "user@example.com",
  "password": "password"
}

Response:
{
  "success": true,
  "message": "Login successful",
  "data": {
    "user": {...},
    "token": "1|xxxxxxxxxxxxx"
  }
}
```

#### Logout
```
POST /api/logout
Authorization: Bearer {token}
```

### Organizations

```
GET    /api/organizations                    # List user's organizations
POST   /api/organizations                    # Create organization
GET    /api/organizations/{id}               # Get organization
PUT    /api/organizations/{id}               # Update (owner only)
DELETE /api/organizations/{id}               # Delete (owner only)
POST   /api/organizations/{id}/invite-teacher # Invite teacher
```

### Students

```
GET    /api/organizations/{id}/students      # List students (teachers)
POST   /api/organizations/{id}/students      # Create student (auto-creates user)
GET    /api/students/{id}                    # Get student details
PUT    /api/students/{id}                    # Update student
DELETE /api/students/{id}                    # Delete student
```

### Batches

```
GET    /api/batches                          # List batches
POST   /api/batches                          # Create batch (teachers)
GET    /api/batches/{id}                     # Get batch
PUT    /api/batches/{id}                     # Update batch
DELETE /api/batches/{id}                     # Delete batch
POST   /api/batches/{id}/students            # Add student to batch
DELETE /api/batches/{id}/students            # Remove student from batch
```

###Question Papers (Teachers)

```
GET    /api/question-papers                  # List question papers
POST   /api/question-papers                  # Create question paper
GET    /api/question-papers/{id}             # Get question paper with questions
PUT    /api/question-papers/{id}             # Update (if not locked)
DELETE /api/question-papers/{id}             # Delete (if not locked)
```

### Questions (Teachers)

```
GET    /api/questions/paper/{paper_id}       # List questions in a paper
POST   /api/questions                        # Add question to paper
GET    /api/questions/{id}                   # Get question details
PUT    /api/questions/{id}                   # Update question (if paper not locked)
DELETE /api/questions/{id}                   # Delete question (if paper not locked)
```

### Exams (Teachers)

```
GET    /api/exams                            # List exams
POST   /api/exams                            # Create exam
GET    /api/exams/{id}                       # Get exam
PUT    /api/exams/{id}                       # Update exam
DELETE /api/exams/{id}                       # Delete exam
POST   /api/exams/{id}/publish               # Publish exam
```

### Student Exams

```
GET    /api/student/exams                    # Available exams for student
GET    /api/student/exams/{id}               # View exam questions
POST   /api/student/exams/{id}/start         # Start exam attempt
POST   /api/student/exams/{id}/answers       # Save answer
POST   /api/student/exams/{id}/submit        # Submit exam
```

### Results

```
GET    /api/results/exams/{id}               # Exam results (teachers)
GET    /api/results/exams/{id}/my-result     # Student's result
GET    /api/results/students/{id}/performance # Performance analytics
```

---

## Database Schema

### Core Tables
- `users` - Authentication only (email, password, role)
- `organizations` - Multi-tenant organizations
- `organization_invitations` - Teacher invitation system
- `organization_user` - Pivot for user-organization membership
- `students` - Full student profile data

### Learning Tables
- `batches` - Student groups
- `batch_student` - Pivot for batch-student membership
- `question_papers` - Reusable exam papers
- `questions` - MCQ or short_answer
- `question_options` - MCQ answer choices
- `exams` - Time-bound exam instances
- `exam_attempts` - Student exam attempts (unique per student per exam)
- `exam_answers` - Student responses
- `attendances` - Attendance tracking

### Other
- `ai_request_logs` - AI operation audit trail

---

## Business Rules

### Teacher Invitation
- Admin invites teacher via email
- Invitation token unique, expires in 7 days
- Teacher accesses `/api/invitations/{token}/accept` to create account

### Student Creation
- Teachers add students with email
- System checks if user exists by email
- If not, creates user with random password
- Creates student profile with all details
- Auto-adds user to organization

### Exam Access
- Students can only access exams in their batches
- Access only during start_time → end_time window
- Auto-submit after duration_minutes elapsed
- One attempt per student per exam

### Scoring
- MCQ answers auto-graded on submission
- Short answers require manual grading
- Score = sum of marks_awarded

---

## Architecture

### Service Layer
Business logic encapsulated in services:
- `OrganizationService` - Invitation management
- `StudentService` - Smart user creation
- `ExamAccessService` - Time-window validation
- `ExamService` - Publish, delete logic
- `ExamAttemptService` - Start, save, submit, auto-submit
- `ResultCalculationService` - Grading and analytics

### Controllers
Thin controllers delegate to services:
- `AuthController`
- `OrganizationController`
- `StudentController`
- `BatchController`
- `ExamController`
- `StudentExamController`
- `ResultController`

### Middleware
- `auth:sanctum` - API authentication
- `EnsureUserHasRole` - Role-based authorization

---

## Security

- **API-only authentication** via Laravel Sanctum tokens
- **Role-based access control** (admin, teacher, student)
- **Multi-tenant isolation** - all data scoped to organizations
- **Time-bound exam access** - strict enforcement
- **Validation** via Form Request classes
- **Authorization** enforced at route level via middleware

---

## Development

### Code Style
- PSR-12 coding standards
- Business rules documented in comments
- Service layer for complex logic
- Thin controllers
- Form Requests for validation

### Testing
```bash
# Run tests
php artisan test

# Run specific test
php artisan test --filter=ExamAccessTest
```

---

## Production Checklist

- [ ] Set `APP_ENV=production` in `.env`
- [ ] Set `APP_DEBUG=false`
- [ ] Configure proper database credentials
- [ ] Set up queue workers for email notifications
- [ ] Configure CORS for your frontend domain
- [ ] Set up rate limiting
- [ ] Enable HTTPS
- [ ] Configure backup strategy

---

## License

MIT License
