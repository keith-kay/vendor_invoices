<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\Claim;
use Illuminate\Auth\Access\HandlesAuthorization;

class ClaimPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:Claim');
    }

    public function view(AuthUser $authUser, Claim $claim): bool
    {
        return $authUser->can('View:Claim');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:Claim');
    }

    public function update(AuthUser $authUser, Claim $claim): bool
    {
        return $authUser->can('Update:Claim');
    }

    public function delete(AuthUser $authUser, Claim $claim): bool
    {
        return $authUser->can('Delete:Claim');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:Claim');
    }

    public function restore(AuthUser $authUser, Claim $claim): bool
    {
        return $authUser->can('Restore:Claim');
    }

    public function forceDelete(AuthUser $authUser, Claim $claim): bool
    {
        return $authUser->can('ForceDelete:Claim');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:Claim');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:Claim');
    }

    public function replicate(AuthUser $authUser, Claim $claim): bool
    {
        return $authUser->can('Replicate:Claim');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:Claim');
    }

}