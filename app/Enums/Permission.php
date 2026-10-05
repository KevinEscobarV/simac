<?php

namespace App\Enums;

/**
 * What a user may do. Roles are granted a set of these (see Role::permissions()),
 * and the application always checks the permission, never the role.
 */
enum Permission: string
{
    case ManageUsers = 'users.manage';
    case ManageRoll = 'roll.manage';
    case ManageAssemblies = 'assemblies.manage';
    case RegisterAttendance = 'attendance.register';
    case DrawRaffles = 'raffles.draw';
    case ViewRaffles = 'raffles.view';
    case ViewScreen = 'screen.view';
    case ManageSettings = 'settings.manage';
}
