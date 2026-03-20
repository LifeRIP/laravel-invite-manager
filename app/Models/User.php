<?php

namespace App\Models;

use App\Enums\OrganizationRole;
// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

#[Fillable(['name', 'email', 'password'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    public function organizations(): BelongsToMany
    {
        return $this->belongsToMany(Organization::class, 'organization_members')
            ->wherePivotNull('deactivated_at')
            ->withPivot(['role', 'invited_by_user_id', 'joined_at', 'deactivated_at', 'deactivated_by_user_id'])
            ->withTimestamps();
    }

    public function memberships(): HasMany
    {
        return $this->hasMany(OrganizationMember::class)->whereNull('deactivated_at');
    }

    public function sentInvitations(): HasMany
    {
        return $this->hasMany(Invitation::class, 'inviter_user_id');
    }

    public function hasAnyRoleInOrganization(int $organizationId, array $roles): bool
    {
        return $this->memberships()
            ->where('organization_id', $organizationId)
            ->whereIn('role', $roles)
            ->exists();
    }

    public function isAdminInOrganization(int $organizationId): bool
    {
        return $this->hasAnyRoleInOrganization($organizationId, [OrganizationRole::ADMIN->value]);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
}
