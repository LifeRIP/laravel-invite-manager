<?php

namespace Tests\Feature;

use App\Enums\OrganizationRole;
use App\Mail\OrganizationInvitationMail;
use App\Models\Invitation;
use App\Models\Organization;
use App\Models\OrganizationMember;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class InvitationFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_invite_and_new_user_can_accept_invitation(): void
    {
        Mail::fake();

        $admin = User::factory()->create();
        Sanctum::actingAs($admin);

        $organization = Organization::create([
            'name' => 'Acme Inc',
            'slug' => 'acme-inc',
            'owner_user_id' => $admin->id,
        ]);

        OrganizationMember::create([
            'organization_id' => $organization->id,
            'user_id' => $admin->id,
            'invited_by_user_id' => $admin->id,
            'role' => OrganizationRole::ADMIN->value,
            'joined_at' => now(),
        ]);

        $inviteResponse = $this->postJson("/api/v1/organizations/{$organization->id}/invitations", [
            'email' => 'newuser@example.com',
            'role' => OrganizationRole::MANAGER->value,
        ]);

        $inviteResponse->assertCreated();

        Mail::assertSent(OrganizationInvitationMail::class);

        $invitation = Invitation::query()->where('invitee_email', 'newuser@example.com')->firstOrFail();

        $acceptResponse = $this->postJson('/api/v1/invitations/accept', [
            'token' => $invitation->token,
            'name' => 'New User',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $acceptResponse
            ->assertOk()
            ->assertJsonStructure([
                'token',
                'user' => ['id', 'name', 'email'],
                'organization' => ['id', 'name', 'slug'],
                'invitation' => ['status'],
            ]);

        $this->assertDatabaseHas('organization_members', [
            'organization_id' => $organization->id,
            'user_id' => User::query()->where('email', 'newuser@example.com')->value('id'),
            'role' => OrganizationRole::MANAGER->value,
        ]);
    }

    public function test_manager_can_invite_but_cannot_remove_member(): void
    {
        $admin = User::factory()->create();
        $manager = User::factory()->create();
        $member = User::factory()->create();

        $organization = Organization::create([
            'name' => 'Beta Corp',
            'slug' => 'beta-corp',
            'owner_user_id' => $admin->id,
        ]);

        OrganizationMember::insert([
            [
                'organization_id' => $organization->id,
                'user_id' => $admin->id,
                'invited_by_user_id' => $admin->id,
                'role' => OrganizationRole::ADMIN->value,
                'joined_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'organization_id' => $organization->id,
                'user_id' => $manager->id,
                'invited_by_user_id' => $admin->id,
                'role' => OrganizationRole::MANAGER->value,
                'joined_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'organization_id' => $organization->id,
                'user_id' => $member->id,
                'invited_by_user_id' => $admin->id,
                'role' => OrganizationRole::MEMBER->value,
                'joined_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        Sanctum::actingAs($manager);

        $inviteResponse = $this->postJson("/api/v1/organizations/{$organization->id}/invitations", [
            'email' => 'another@example.com',
            'role' => OrganizationRole::MEMBER->value,
        ]);

        $inviteResponse->assertCreated();

        $removeResponse = $this->deleteJson("/api/v1/organizations/{$organization->id}/members/{$member->id}");
        $removeResponse->assertForbidden();

        $this->assertDatabaseHas('organization_members', [
            'organization_id' => $organization->id,
            'user_id' => $member->id,
        ]);
    }
}
