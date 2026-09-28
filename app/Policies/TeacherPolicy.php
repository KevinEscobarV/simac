<?php

namespace App\Policies;

use App\Enums\Permission;
use App\Models\Teacher;
use App\Models\User;

class TeacherPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo(Permission::ManageRoll);
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo(Permission::ManageRoll);
    }

    public function update(User $user, Teacher $teacher): bool
    {
        return $user->hasPermissionTo(Permission::ManageRoll) && ! $teacher->trashed();
    }

    /**
     * Deleting a teacher retires them: it is a soft delete.
     */
    public function delete(User $user, Teacher $teacher): bool
    {
        return $user->hasPermissionTo(Permission::ManageRoll) && ! $teacher->trashed();
    }

    public function restore(User $user, Teacher $teacher): bool
    {
        return $user->hasPermissionTo(Permission::ManageRoll) && $teacher->trashed();
    }
}
