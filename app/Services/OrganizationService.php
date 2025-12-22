<?php

namespace App\Services;

use App\Models\Organization;
use App\Models\OrganizationInvitation;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

class OrganizationService
{
    /**
     * Invite a teacher to the organization
     * BUSINESS RULE: Teacher invitation via email with unique token
     *
     * @param Organization $organization
     * @param string $email
     * @param User $inviter
     * @return OrganizationInvitation
     */
    public function inviteTeacher(Organization $organization, string $email, User $inviter): OrganizationInvitation
    {
        //  Check if user already exists and is in the organization
        $existingUser = User::where('email', $email)->first();
        if ($existingUser && $organization->users()->where('user_id', $existingUser->id)->exists()) {
            throw new \Exception('User is already a member of this organization');
        }

        // Check if there's a pending invitation
        $existingInvitation = OrganizationInvitation::where('organization_id', $organization->id)
            ->where('email', $email)
            ->where('status', 'pending')
            ->where('expires_at', '>', now())
            ->first();

        if ($existingInvitation) {
            return $existingInvitation;
        }

        // Create invitation
        $invitation = OrganizationInvitation::create([
            'organization_id' => $organization->id,
            'email' => $email,
            'role' => 'teacher',
            'token' => OrganizationInvitation::generateToken(),
            'status' => 'pending',
            'invited_by' => $inviter->id,
            'expires_at' => now()->addDays(7),
        ]);

        // TODO: Send invitation email
        // Mail::to($email)->send(new TeacherInvitationMail($invitation));

        return $invitation;
    }

    /**
     * Accept invitation and create/update user account
     * BUSINESS RULE: Create user if doesn't exist, add to organization
     *
     * @param string $token
     * @param array $userData Contains: password, (optional) existing user if logged in
     * @return User
     */
    public function acceptInvitation(string $token, array $userData): User
    {
        $invitation = OrganizationInvitation::where('token', $token)->firstOrFail();

        if (!$invitation->isValid()) {
            throw new \Exception('Invitation has expired or is no longer valid');
        }

        return DB::transaction(function () use ($invitation, $userData) {
            // Find or create user
            $user = User::where('email', $invitation->email)->first();

            if (!$user) {
                // Create new user account
                $user = User::create([
                    'email' => $invitation->email,
                    'password' => Hash::make($userData['password']),
                    'role' => 'teacher',
                ]);
            }

            // Accept the invitation (adds user to organization)
            $invitation->accept($user);

            return $user;
        });
    }

    /**
     * Resend invitation email
     *
     * @param OrganizationInvitation $invitation
     * @return void
     */
    public function resendInvitation(OrganizationInvitation $invitation): void
    {
        if (!$invitation->isValid()) {
            throw new \Exception('Cannot resend expired invitation');
        }

        // TODO: Send invitation email
        // Mail::to($invitation->email)->send(new TeacherInvitationMail($invitation));
    }
}
