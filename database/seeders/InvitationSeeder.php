<?php

namespace Database\Seeders;

use App\Enums\InvitationStatus;
use App\Enums\OrganizationRole;
use App\Models\Invitation;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class InvitationSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $organization = Organization::query()->where('slug', 'acme-demo')->first();
        $inviter = User::query()->where('email', 'admin@example.com')->first();

        if (! $organization || ! $inviter) {
            return;
        }

        Invitation::query()->updateOrCreate(
            [
                'organization_id' => $organization->id,
                'invitee_email' => 'invitee@example.com',
                'status' => InvitationStatus::PENDING->value,
            ],
            [
                'inviter_user_id' => $inviter->id,
                'role' => OrganizationRole::MANAGER->value,
                'token' => Str::random(64),
                'expires_at' => now()->addDays(7),
                'last_sent_at' => now(),
                'revoked_at' => null,
                'accepted_at' => null,
                'accepted_by_user_id' => null,
            ]
        );

        Invitation::query()->updateOrCreate(
            [
                'organization_id' => $organization->id,
                'invitee_email' => 'revoked@example.com',
                'status' => InvitationStatus::REVOKED->value,
            ],
            [
                'inviter_user_id' => $inviter->id,
                'role' => OrganizationRole::MEMBER->value,
                'token' => Str::random(64),
                'expires_at' => now()->addDays(7),
                'last_sent_at' => now()->subDay(),
                'revoked_at' => now(),
            ]
        );
    }
}
