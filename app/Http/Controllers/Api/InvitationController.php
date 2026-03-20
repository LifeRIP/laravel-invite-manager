<?php

namespace App\Http\Controllers\Api;

use App\Enums\InvitationStatus;
use App\Http\Requests\AcceptInvitationRequest;
use App\Http\Requests\StoreInvitationRequest;
use App\Http\Resources\InvitationResource;
use App\Http\Controllers\Controller;
use App\Mail\OrganizationInvitationMail;
use App\Models\Invitation;
use App\Models\Organization;
use App\Models\OrganizationMember;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class InvitationController extends Controller
{
    public function store(StoreInvitationRequest $request, Organization $organization): JsonResponse
    {
        $this->authorize('createInvitation', $organization);

        $validated = $request->validated();
        $email = strtolower($validated['email']);

        Invitation::query()
            ->where('organization_id', $organization->id)
            ->where('invitee_email', $email)
            ->where('status', InvitationStatus::PENDING->value)
            ->update([
                'status' => InvitationStatus::REVOKED->value,
                'revoked_at' => now(),
            ]);

        $invitation = Invitation::create([
            'organization_id' => $organization->id,
            'inviter_user_id' => $request->user()->id,
            'invitee_email' => $email,
            'role' => $validated['role'],
            'token' => Str::random(64),
            'status' => InvitationStatus::PENDING->value,
            'expires_at' => now()->addDays(7),
            'last_sent_at' => now(),
        ]);

        Mail::to($email)->send(new OrganizationInvitationMail($invitation));

        return (new InvitationResource($invitation->load(['organization', 'inviter:id,name,email'])))
            ->response()
            ->setStatusCode(201);
    }

    public function showByToken(string $token): InvitationResource|JsonResponse
    {
        $invitation = Invitation::query()
            ->where('token', $token)
            ->with(['organization:id,name,slug', 'inviter:id,name,email'])
            ->first();

        if (! $invitation) {
            return response()->json([
                'message' => 'Invitation not found.',
            ], 404);
        }

        $invitation->markAsExpiredIfNeeded();

        return new InvitationResource($invitation);
    }

    public function accept(AcceptInvitationRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $invitation = Invitation::query()
            ->where('token', $validated['token'])
            ->with('organization:id,name,slug')
            ->first();

        if (! $invitation) {
            return response()->json([
                'message' => 'Invitation not found.',
            ], 404);
        }

        $invitation->markAsExpiredIfNeeded();

        if (! $invitation->isPending()) {
            return response()->json([
                'message' => 'Invitation is no longer pending.',
            ], 422);
        }

        if ($invitation->isExpired()) {
            return response()->json([
                'message' => 'Invitation has expired.',
            ], 422);
        }

        $authenticatedUser = Auth::user();

        if ($authenticatedUser && strtolower($authenticatedUser->email) !== strtolower($invitation->invitee_email)) {
            // Invitation acceptance should work from the email token even if another user is currently authenticated.
            $authenticatedUser = null;
        }

        $user = $authenticatedUser ?: User::query()->where('email', $invitation->invitee_email)->first();

        if (! $user) {
            if (empty($validated['name']) || empty($validated['password'])) {
                return response()->json([
                    'message' => 'Name and password are required for new users.',
                    'errors' => [
                        'name' => ['The name field is required for new users.'],
                        'password' => ['The password field is required for new users.'],
                    ],
                ], 422);
            }

            $user = User::create([
                'name' => $validated['name'],
                'email' => $invitation->invitee_email,
                'password' => $validated['password'],
            ]);
        }

        OrganizationMember::query()->updateOrCreate(
            [
                'organization_id' => $invitation->organization_id,
                'user_id' => $user->id,
            ],
            [
                'invited_by_user_id' => $invitation->inviter_user_id,
                'role' => $invitation->role,
                'joined_at' => now(),
            ]
        );

        $invitation->update([
            'status' => InvitationStatus::ACCEPTED->value,
            'accepted_by_user_id' => $user->id,
            'accepted_at' => now(),
        ]);

        $token = $user->createToken('invitation-accepted')->plainTextToken;

        return response()->json([
            'token' => $token,
            'user' => $user,
            'organization' => $invitation->organization,
            'invitation' => new InvitationResource($invitation->fresh(['organization', 'inviter:id,name,email'])),
        ]);
    }
}
