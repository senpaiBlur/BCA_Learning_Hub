<?php

/**
 * Role Hierarchy:
 * 1. owner (Saurabh) - All powerful, max 1.
 * 2. co-partner (Shivam, Yash) - High power, max 2. Cannot delete owner.
 * 3. admin - Management power. Cannot delete higher tiers.
 * 4. student - Basic user.
 */

define('ROLE_OWNER', 'owner');
define('ROLE_CO_PARTNER', 'co-partner');
define('ROLE_ADMIN', 'admin');
define('ROLE_STUDENT', 'student');

/**
 * Get the numeric level of a role (lower is more powerful)
 */
function get_role_level($role) {
    switch ($role) {
        case ROLE_OWNER: return 1;
        case ROLE_CO_PARTNER: return 2;
        case ROLE_ADMIN: return 3;
        case ROLE_STUDENT: return 4;
        default: return 5;
    }
}

/**
 * Check if User A (actor) can manage/delete User B (target)
 */
function can_manage_user($actor_role, $target_role) {
    $actor_level = get_role_level($actor_role);
    $target_level = get_role_level($target_role);

    // Owner can manage everyone except themselves (controlled in management logic)
    if ($actor_role === ROLE_OWNER) return true;

    // ONLY Owner can manage/delete Co-partners
    if ($target_role === ROLE_CO_PARTNER) return false;

    // Co-partner can manage Admins and Students
    if ($actor_role === ROLE_CO_PARTNER) {
        return ($target_role === ROLE_ADMIN || $target_role === ROLE_STUDENT);
    }

    // Admin can delete ONLY students
    if ($actor_role === ROLE_ADMIN) {
        return ($target_role === ROLE_STUDENT);
    }

    return false;
}

/**
 * Get roles that a specific role is allowed to assign/create
 */
function get_assignable_roles($actor_role) {
    if ($actor_role === ROLE_OWNER) {
        return [ROLE_OWNER, ROLE_CO_PARTNER, ROLE_ADMIN, ROLE_STUDENT];
    }
    
    if ($actor_role === ROLE_CO_PARTNER || $actor_role === ROLE_ADMIN) {
        // According to audio: Admins can create Admins/Students
        return [ROLE_ADMIN, ROLE_STUDENT];
    }

    return [ROLE_STUDENT];
}
?>
