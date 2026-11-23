<?php

namespace Domain\Auth\Support;

use Domain\Auth\Models\User;

/**
 * Working rule, 8 Nov 2026. Revise later. No person is named.
 *
 * A primary administrator is any signed-in user with the super-admin role.
 * A deputy is another user with that role, or a role that includes the permission
 * for the job (for example import employees).
 * Sign-in is an existing account. There is no open registration.
 * A signed-in person can search, open the organogram, and read the directory.
 * Date of birth is included only for someone who can update employees.
 * Export uses the same permission as the import or catalog create for that model.
 * Expertise catalog writes use the skill and certificate permissions.
 * Turning is_active on or off is the review, and it uses the update permission.
 * Profile links use the employee skill and employee certificate permissions.
 * A leaver stays on file. Nothing deletes the profile or the import log on a timer.
 * The uploaded file itself is not stored. The log keeps the file name, the user, the outcome, and the counts.
 */
final class Maintainer
{
    public static function allows(?User $user, string $permission): bool
    {
        return $user !== null && $user->can($permission);
    }
}
