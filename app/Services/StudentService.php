<?php

namespace App\Services;

use App\Models\Student;
use App\Models\Organization;
use App\Models\User;
use App\Models\Batch;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class StudentService
{
    /**
     * Create student with auto user creation if needed
     * BUSINESS RULE: Check if user exists by email, create if needed, then create student profile
     *
     * @param Organization $organization
     * @param array $data Contains: first_name, last_name, email, phone, parent info, etc.
     * @return Student
     */
    public function createStudent(Organization $organization, array $data): Student
    {
        return DB::transaction(function () use ($organization, $data) {
            // Check if user exists by email
            $user = User::where('email', $data['email'])->first();

            if (!$user) {
                // Create new user account with random password (they can reset later)
                $user = User::create([
                    'email' => $data['email'],
                    'password' => Hash::make(bin2hex(random_bytes(16))), // Random password
                    'role' => 'student',
                ]);
            }

            // Check if student profile already exists
            if ($user->studentProfile) {
                throw new \Exception('Student profile already exists for this user');
            }

            // Create student profile
            $student = Student::create([
                'user_id' => $user->id,
                'organization_id' => $organization->id,
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'phone_number' => $data['phone_number'] ?? null,
                'parent_email' => $data['parent_email'] ?? null,
                'parent_phone_number' => $data['parent_phone_number'] ?? null,
                'age' => $data['age'] ?? null,
                'blood_group' => $data['blood_group'] ?? null,
                'address' => $data['address'] ?? null,
                'city' => $data['city'] ?? null,
                'state' => $data['state'] ?? null,
                'pincode' => $data['pincode'] ?? null,
            ]);

            // Add user to organization as student
            if (!$organization->users()->where('user_id', $user->id)->exists()) {
                $organization->users()->attach($user->id, ['role' => 'student']);
            }

            return $student;
        });
    }

    /**
     * Update student profile
     *
     * @param Student $student
     * @param array $data
     * @return Student
     */
    public function updateStudent(Student $student, array $data): Student
    {
        $student->update([
            'first_name' => $data['first_name'] ?? $student->first_name,
            'last_name' => $data['last_name'] ?? $student->last_name,
            'phone_number' => $data['phone_number'] ?? $student->phone_number,
            'parent_email' => $data['parent_email'] ?? $student->parent_email,
            'parent_phone_number' => $data['parent_phone_number'] ?? $student->parent_phone_number,
            'age' => $data['age'] ?? $student->age,
            'blood_group' => $data['blood_group'] ?? $student->blood_group,
            'address' => $data['address'] ?? $student->address,
            'city' => $data['city'] ?? $student->city,
            'state' => $data['state'] ?? $student->state,
            'pincode' => $data['pincode'] ?? $student->pincode,
        ]);

        return $student->fresh();
    }

    /**
     * Add student to a batch
     *
     * @param Student $student
     * @param Batch $batch
     * @return void
     */
    public function addToBatch(Student $student, Batch $batch): void
    {
        if ($batch->students()->where('student_id', $student->id)->exists()) {
            throw new \Exception('Student is already in this batch');
        }

        $batch->students()->attach($student->id);
    }

    /**
     * Remove student from a batch
     *
     * @param Student $student
     * @param Batch $batch
     * @return void
     */
    public function removeFromBatch(Student $student, Batch $batch): void
    {
        $batch->students()->detach($student->id);
    }
}
