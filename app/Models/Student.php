<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Student extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'organization_id',
        'first_name',
        'last_name',
        'phone_number',
        'parent_email',
        'parent_phone_number',
        'age',
        'blood_group',
        'address',
        'city',
        'state',
        'pincode',
    ];

    /**
     * Get the user account
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the organization
     */
    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * Get batches this student belongs to
     */
    public function batches()
    {
        return $this->belongsToMany(Batch::class, 'batch_student');
    }

    /**
     * Get exam attempts
     */
    public function examAttempts()
    {
        return $this->hasMany(ExamAttempt::class);
    }

    /**
     * Get attendance records
     */
    public function attendances()
    {
        return $this->hasMany(Attendance::class);
    }

    /**
     * Get full name accessor
     */
    public function getFullNameAttribute(): string
    {
        return "{$this->first_name} {$this->last_name}";
    }
}
