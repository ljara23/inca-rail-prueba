<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AccessControlSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            'competitions.view',
            'competitions.import',
            'teams.view',
            'players.view',
            'users.view',
            'users.create',
            'users.update',
            'users.deactivate',
            'roles.view',
            'roles.manage',
            'permissions.view',
        ];

        foreach ($permissions as $slug) {
            Permission::firstOrCreate(
                ['slug' => $slug],
                ['name' => ucwords(str_replace('.', ' ', $slug))]
            );
        }

        $adminRole = Role::firstOrCreate(
            ['slug' => 'admin'],
            ['name' => 'Administrador']
        );

        $readOnlyRole = Role::firstOrCreate(
            ['slug' => 'read-only'],
            ['name' => 'Solo consulta']
        );

        $adminRole->permissions()->sync(
            Permission::whereIn('slug', $permissions)->pluck('id')
        );

        $readOnlyRole->permissions()->sync(
            Permission::whereIn('slug', [
                'competitions.view',
                'teams.view',
                'players.view',
                'permissions.view',
            ])->pluck('id')
        );

        $admin = User::firstOrCreate(
            ['email' => 'admin@inca-rail.test'],
            [
                'name' => 'INCA Rail Admin',
                'password' => Hash::make('IncaRail2026!'),
                'is_active' => true,
            ]
        );

        $reader = User::firstOrCreate(
            ['email' => 'reader@inca-rail.test'],
            [
                'name' => 'INCA Rail Reader',
                'password' => Hash::make('IncaRail2026!'),
                'is_active' => true,
            ]
        );

        $admin->roles()->syncWithoutDetaching([$adminRole->id]);
        $reader->roles()->syncWithoutDetaching([$readOnlyRole->id]);
    }
}