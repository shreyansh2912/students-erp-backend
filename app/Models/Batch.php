<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Batch extends Model
{
    use HasFactory;

    protected $fillable = [
        'organization_id',
        'name',
        'subject',
    ];

    /**
     * Get the organization
     */
    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * Get students in this batch
     */
    public function students()
    {
        return $this->belongsToMany(Student::class, 'batch_student');
    }

    /**
     * Get exams for this batch
     */
    public function exams()
    {
        return $this->hasMany(Exam::class);
    }

    /**
     * Get attendance records
     */
    public function attendances()
    {
        return $this->hasMany(Attendance::class);
    }
}
