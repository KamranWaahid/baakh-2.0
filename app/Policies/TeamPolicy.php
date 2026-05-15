<?php

namespace App\Policies;

use App\Models\Team;
use App\Models\User;
use Spatie\Permission\Exceptions\PermissionDoesNotExist;

class TeamPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->userCan($user, 'assign_roles')
            || $this->userCan($user, 'view_team');
    }

    public function view(User $user, Team $team): bool
    {
        if ($this->userCan($user, 'view_team')) {
            return true;
        }

        return $this->isTeamMember($user, $team);
    }

    public function create(User $user): bool
    {
        return $this->userCan($user, 'assign_roles');
    }

    public function update(User $user, Team $team): bool
    {
        if ($this->userCan($user, 'manage_settings') || $this->userCan($user, 'view_team')) {
            return true;
        }

        return $team->members()
            ->where('user_id', $user->id)
            ->whereIn('role', ['owner', 'admin'])
            ->exists();
    }

    public function delete(User $user, Team $team): bool
    {
        if ($this->userCan($user, 'manage_settings') || $this->userCan($user, 'view_team')) {
            return true;
        }

        return (int) $team->owner_id === (int) $user->id;
    }

    public function restore(User $user, Team $team): bool
    {
        return $this->userCan($user, 'manage_settings');
    }

    public function forceDelete(User $user, Team $team): bool
    {
        return $this->userCan($user, 'manage_settings');
    }

    public function addMember(User $user, Team $team): bool
    {
        if ($this->userCan($user, 'manage_team_members') || $this->userCan($user, 'view_team')) {
            return true;
        }

        return $team->members()
            ->where('user_id', $user->id)
            ->whereIn('role', ['owner', 'admin'])
            ->exists();
    }

    public function removeMember(User $user, Team $team): bool
    {
        return $this->addMember($user, $team);
    }

    private function isTeamMember(User $user, Team $team): bool
    {
        return $team->members()
            ->where('user_id', $user->id)
            ->exists();
    }

    private function userCan(User $user, string $permission): bool
    {
        try {
            return $user->hasPermissionTo($permission);
        } catch (PermissionDoesNotExist) {
            return false;
        }
    }
}
