<?php

namespace App\Policies;

use App\Enums\OrganizationRole;
use App\Models\Organization;
use App\Models\User;

class OrganizationPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Organization $organization): bool
    {
        return $user->memberships()->where('organization_id', $organization->id)->exists();
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Organization $organization): bool
    {
        return $user->isAdminInOrganization($organization->id);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Organization $organization): bool
    {
        return $user->isAdminInOrganization($organization->id);
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Organization $organization): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Organization $organization): bool
    {
        return false;
    }

    public function createInvitation(User $user, Organization $organization): bool
    {
        return $user->hasAnyRoleInOrganization($organization->id, [
            OrganizationRole::ADMIN->value,
            OrganizationRole::MANAGER->value,
        ]);
    }

    public function manageMembers(User $user, Organization $organization): bool
    {
        return $user->isAdminInOrganization($organization->id);
    }
}
