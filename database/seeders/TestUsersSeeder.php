<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Development only: one test user per workflow role, with just the permissions
 * that role needs. Delete these users before going live.
 */
class TestUsersSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        // role => [user name, email, permissions]
        $roles = [
            'registrar' => ['Test Registrar', 'registrar@example.test', [
                'ViewAny:Invoice', 'View:Invoice', 'Create:Invoice', 'Update:Invoice', 'Submit:Invoice',
                'ViewAny:Vendor', 'View:Vendor', 'Create:Vendor',
            ]],
            'receiver' => ['Test Receiver', 'receiver@example.test', [
                'ViewAny:Invoice', 'View:Invoice', 'ReceiverApprove:Invoice', 'Reject:Invoice',
                'ViewAny:Claim', 'View:Claim',
            ]],
            'customer_service' => ['Test Customer Service', 'cs@example.test', [
                'ViewAny:Invoice', 'View:Invoice', 'ConfirmAccrual:Invoice', 'Reject:Invoice',
            ]],
            'finance' => ['Test Finance', 'finance@example.test', [
                'ViewAny:Invoice', 'View:Invoice', 'FinanceApprove:Invoice', 'MarkPaid:Invoice', 'Reject:Invoice',
                'ViewAny:Claim', 'View:Claim',
            ]],
            'gbs' => ['Test GBS', 'gbs@example.test', [
                'ViewAny:Invoice', 'View:Invoice', 'Post:Invoice', 'Reject:Invoice',
            ]],
            'claims' => ['Test Claims', 'claims@example.test', [
                'ViewAny:Claim', 'View:Claim', 'Update:Claim',
                'ViewAny:Invoice', 'View:Invoice',
            ]],
        ];

        // Dashboard widgets everyone may see.
        $widgetPermissions = Permission::where('guard_name', 'web')
            ->where(function ($q) {
                foreach (['InvoiceStats', 'InvoicesByStage', 'NeedsAttention'] as $widget) {
                    $q->orWhere('name', 'like', '%' . $widget);
                }
            })
            ->pluck('name')
            ->all();

        foreach ($roles as $roleName => [$userName, $email, $permissions]) {
            $role = Role::findOrCreate($roleName, 'web');

            $found = Permission::where('guard_name', 'web')
                ->whereIn('name', array_merge($permissions, $widgetPermissions))
                ->get();

            $missing = array_diff($permissions, $found->pluck('name')->all());
            if ($missing) {
                $this->command?->warn("{$roleName}: permissions not found: " . implode(', ', $missing));
            }

            $role->syncPermissions($found);

            $user = User::firstOrCreate(
                ['email' => $email],
                ['name' => $userName, 'password' => Hash::make('password')],
            );
            $user->syncRoles([$roleName]);

            $this->command?->info("{$roleName}: {$email} ({$found->count()} permissions)");
        }
    }
}