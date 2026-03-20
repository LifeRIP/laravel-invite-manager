<?php

namespace App\Http\Controllers\Api;

use App\Enums\OrganizationRole;
use App\Http\Requests\StoreOrganizationRequest;
use App\Http\Resources\OrganizationResource;
use App\Http\Controllers\Controller;
use App\Models\Organization;
use App\Models\OrganizationMember;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class OrganizationController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $organizations = $request->user()
            ->organizations()
            ->with('owner:id,name,email')
            ->orderBy('organizations.name')
            ->get();

        return OrganizationResource::collection($organizations);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreOrganizationRequest $request)
    {
        $this->authorize('create', Organization::class);

        $validated = $request->validated();
        $slugBase = Str::slug($validated['name']);
        $slug = $slugBase;
        $suffix = 2;

        while (Organization::query()->where('slug', $slug)->exists()) {
            $slug = $slugBase . '-' . $suffix;
            $suffix++;
        }

        $organization = Organization::create([
            'name' => $validated['name'],
            'slug' => $slug,
            'owner_user_id' => $request->user()->id,
        ]);

        OrganizationMember::create([
            'organization_id' => $organization->id,
            'user_id' => $request->user()->id,
            'invited_by_user_id' => $request->user()->id,
            'role' => OrganizationRole::ADMIN->value,
            'joined_at' => now(),
        ]);

        return (new OrganizationResource($organization->load('owner:id,name,email')))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Organization $organization)
    {
        $this->authorize('view', $organization);

        $organization->load(['owner:id,name,email']);

        return new OrganizationResource($organization);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(StoreOrganizationRequest $request, Organization $organization)
    {
        $this->authorize('update', $organization);

        $organization->update([
            'name' => $request->validated('name'),
        ]);

        return new OrganizationResource($organization->fresh('owner:id,name,email'));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Organization $organization)
    {
        $this->authorize('delete', $organization);

        $organization->delete();

        return response()->noContent();
    }

    public function members(Organization $organization)
    {
        $this->authorize('view', $organization);

        $members = $organization->members()
            ->select('users.id', 'users.name', 'users.email')
            ->withPivot(['role', 'joined_at', 'invited_by_user_id'])
            ->get();

        return response()->json([
            'data' => $members,
        ]);
    }

    public function removeMember(Organization $organization, User $user)
    {
        $this->authorize('manageMembers', $organization);

        if ($user->id === request()->user()->id) {
            return response()->json([
                'message' => 'You cannot remove yourself from organization members.',
            ], 422);
        }

        OrganizationMember::query()
            ->where('organization_id', $organization->id)
            ->where('user_id', $user->id)
            ->delete();

        return response()->noContent();
    }
}
