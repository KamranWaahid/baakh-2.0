<?php

namespace App\Policies;

use App\Models\Team;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class TeamPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('view_team');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Team $team): bool
    {
        if ($user->hasPermissionTo('view_team')) {
            return true;
        }

        // Check if user is a member of the team
        return $team->members()->where('user_id', $user->id)->exists();
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        // Maybe only default permission or specific role?
        // Everyone usually gets a personal team, but creating extra teams might be restricted.
        return true;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Team $team): bool
    {
        if ($user->hasPermissionTo('manage_settings')) {
            return true;
        }

        return $team->owner_id === $user->id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Team $team): bool
    {
        if ($user->hasPermissionTo('manage_settings')) {
            return true;
        }

        return $team->owner_id === $user->id;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Team $team): bool
    {
        return $user->hasPermissionTo('manage_settings');
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Team $team): bool
    {
        return $user->hasPermissionTo('manage_settings');
    }

    /**
     * Determine whether the user can add members to the team.
     */
    public function addMember(User $user, Team $team): bool
    {
        if ($user->hasPermissionTo('manage_team_members')) {
            return true;
        }

        return $team->owner_id === $user->id;
    }

    /**
     * Determine whether the user can remove members from the team.
     */
    public function removeMember(User $user, Team $team): bool
    {
        if ($user->hasPermissionTo('manage_team_members')) {
            return true;
        }

        return $team->owner_id === $user->id;
    }
}
