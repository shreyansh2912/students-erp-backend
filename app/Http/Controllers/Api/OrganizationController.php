<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\AcceptInvitationRequest;
use App\Http\Requests\InviteTeacherRequest;
use App\Http\Requests\OrganizationRequest;
use App\Models\Organization;
use App\Services\OrganizationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OrganizationController extends Controller
{
    protected OrganizationService $organizationService;

    public function __construct(OrganizationService $organizationService)
    {
        $this->organizationService = $organizationService;
    }

    /**
     * Get all organizations for authenticated user
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $organizations = $user->organizations()->with('owner')->get();

        return response()->json([
            'success' => true,
            'data' => $organizations,
        ]);
    }

    /**
     * Create a new organization
     */
    public function store(OrganizationRequest $request): JsonResponse
    {
        $organization = Organization::create([
            'name' => $request->name,
            'owner_id' => $request->user()->id,
        ]);

        // Add creator as admin in organization
        $organization->users()->attach($request->user()->id, ['role' => 'teacher']);

        return response()->json([
            'success' => true,
            'message' => 'Organization created successfully',
            'data' => $organization,
        ], 201);
    }

    /**
     * Get organization details
     */
    public function show(Organization $organization): JsonResponse
    {
        $organization->load(['owner', 'students', 'batches', 'teachers']);

        return response()->json([
            'success' => true,
            'data' => $organization,
        ]);
    }

    /**
     * Update organization
     */
    public function update(OrganizationRequest $request, Organization $organization): JsonResponse
    {
        // Check if user is owner
        if ($organization->owner_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Only the owner can update the organization',
            ], 403);
        }

        $organization->update($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Organization updated successfully',
            'data' => $organization,
        ]);
    }

    /**
     * Delete organization
     */
    public function destroy(Organization $organization, Request $request): JsonResponse
    {
        // Check if user is owner
        if ($organization->owner_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Only the owner can delete the organization',
            ], 403);
        }

        $organization->delete();

        return response()->json([
            'success' => true,
            'message' => 'Organization deleted successfully',
        ]);
    }

    /**
     * Invite a teacher to the organization
     */
    public function inviteTeacher(InviteTeacherRequest $request, Organization $organization): JsonResponse
    {
        try {
            $invitation = $this->organizationService->inviteTeacher(
                $organization,
                $request->email,
                $request->user()
            );

            return response()->json([
                'success' => true,
                'message' => 'Invitation sent successfully',
                'data' => [
                    'invitation' => $invitation,
                    'invitation_link' => url("/api/invitations/{$invitation->token}/accept"),
                ],
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Accept invitation (public endpoint)
     */
    public function acceptInvitation(AcceptInvitationRequest $request, string $token): JsonResponse
    {
        try {
            $user = $this->organizationService->acceptInvitation($token, $request->validated());

            // Create token for the newly created user
            $authToken = $user->createToken('auth-token')->plainTextToken;

            return response()->json([
                'success' => true,
                'message' => 'Invitation accepted successfully',
                'data' => [
                    'user' => $user,
                    'token' => $authToken,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }
}
