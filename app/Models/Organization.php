<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Organization extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'owner_id',
    ];

    /**
     * Get the owner of the organization
     */
    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    /**
     * Get all users in this organization
     */
    public function users()
    {
        return $this->belongsToMany(User::class, 'organization_user')
                    ->withPivot('role')
                    ->withTimestamps();
    }

    /**
     * Get teachers in this organization
     */
    public function teachers()
    {
        return $this->belongsToMany(User::class, 'organization_user')
                    ->wherePivot('role', 'teacher')
                    ->withTimestamps();
    }

    /**
     * Get students in this organization
     */
    public function students()
    {
        return $this->hasMany(Student::class);
    }

    /**
     * Get batches in this organization
     */
    public function batches()
    {
        return $this->hasMany(Batch::class);
    }

    /**
     * Get question papers in this organization
     */
    public function papers()
    {
        return $this->hasMany(QuestionPaper::class);
    }

    /**
     * Get exams in this organization
     */
    public function exams()
    {
        return $this->hasMany(Exam::class);
    }

    /**
     * Get pending invitations
     */
    public function invitations()
    {
        return $this->hasMany(OrganizationInvitation::class);
    }
}
