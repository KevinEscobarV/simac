<?php

namespace App\Policies;

use App\Enums\Permission;
use App\Models\Assembly;
use App\Models\User;

class AssemblyPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo(Permission::ManageAssemblies);
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo(Permission::ManageAssemblies);
    }

    /**
     * Adjusting the quorum, which can be done while it is open.
     */
    public function update(User $user, Assembly $assembly): bool
    {
        return $user->hasPermissionTo(Permission::ManageAssemblies);
    }

    public function close(User $user, Assembly $assembly): bool
    {
        return $user->hasPermissionTo(Permission::ManageAssemblies) && $assembly->isOpen();
    }

    public function reopen(User $user, Assembly $assembly): bool
    {
        return $user->hasPermissionTo(Permission::ManageAssemblies) && ! $assembly->isOpen();
    }

    /**
     * Whether it still has attendance is a business rule: DeleteAssembly
     * enforces it and says so.
     */
    public function delete(User $user, Assembly $assembly): bool
    {
        return $user->hasPermissionTo(Permission::ManageAssemblies);
    }

    /**
     * Check-ins and check-outs, from the panel or the desk. A closed assembly
     * keeps its history but takes no more changes.
     */
    public function registerAttendance(User $user, Assembly $assembly): bool
    {
        return $user->hasPermissionTo(Permission::RegisterAttendance) && $assembly->isOpen();
    }

    /**
     * The registration desk, which waits for an assembly to open when there
     * is none.
     */
    public function useDesk(User $user): bool
    {
        return $user->hasPermissionTo(Permission::RegisterAttendance);
    }

    /**
     * Receiving the live updates of the assemblies (openings, closings and
     * every check-in), which the panel and the desks both show.
     */
    public function followLive(User $user): bool
    {
        return $user->hasPermissionTo(Permission::ManageAssemblies)
            || $user->hasPermissionTo(Permission::RegisterAttendance);
    }
}
