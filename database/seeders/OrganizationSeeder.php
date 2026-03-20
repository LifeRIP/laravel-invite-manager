<?php

namespace Database\Seeders;

use App\Enums\OrganizationRole;
use App\Models\Organization;
use App\Models\OrganizationMember;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class OrganizationSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $admin = User::query()->firstOrCreate(
            ['email' => 'admin@example.com'],
            ['name' => 'Org Admin', 'password' => 'password123']
        );

        $manager = User::query()->firstOrCreate(
            ['email' => 'manager@example.com'],
            ['name' => 'Org Manager', 'password' => 'password123']
        );

        $member = User::query()->firstOrCreate(
            ['email' => 'member@example.com'],
            ['name' => 'Org Member', 'password' => 'password123']
        );

        $organization = Organization::query()->updateOrCreate(
            ['slug' => 'acme-demo'],
            [
                'name' => 'Acme Demo',
                'owner_user_id' => $admin->id,
            ]
        );

        OrganizationMember::query()->updateOrCreate(
            ['organization_id' => $organization->id, 'user_id' => $admin->id],
            [
                'invited_by_user_id' => $admin->id,
                'role' => OrganizationRole::ADMIN->value,
                'joined_at' => now(),
            ]
        );

        OrganizationMember::query()->updateOrCreate(
            ['organization_id' => $organization->id, 'user_id' => $manager->id],
            [
                'invited_by_user_id' => $admin->id,
                'role' => OrganizationRole::MANAGER->value,
                'joined_at' => now(),
            ]
        );

        OrganizationMember::query()->updateOrCreate(
            ['organization_id' => $organization->id, 'user_id' => $member->id],
            [
                'invited_by_user_id' => $admin->id,
                'role' => OrganizationRole::MEMBER->value,
                'joined_at' => now(),
            ]
        );
    }
}
