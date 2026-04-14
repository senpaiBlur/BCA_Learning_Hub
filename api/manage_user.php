<?php
session_start();
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/permissions.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

// Fetch actor role
$stmt = $conn->prepare("SELECT role FROM users WHERE id = ?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$actor_role = $stmt->get_result()->fetch_assoc()['role'] ?? 'student';

$action = $_POST['action'] ?? '';

if ($action === 'create') {
    $name = $_POST['name'] ?? '';
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $role = $_POST['role'] ?? ROLE_STUDENT;

    // Check if actor can create this role
    $allowed_roles = get_assignable_roles($actor_role);
    if (!in_array($role, $allowed_roles)) {
        echo json_encode(['success' => false, 'message' => 'Hierarchy violation: You cannot create a ' . $role]);
        exit();
    }

    $hashed = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $conn->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $name, $email, $hashed, $role);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'User created successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error: ' . $conn->error]);
    }

} elseif ($action === 'delete') {
    $target_id = (int)($_POST['user_id'] ?? 0);
    
    // Safety: You cannot delete YOURSELF
    if ($_SESSION['user_id'] == $target_id) {
        echo json_encode(['success' => false, 'message' => 'Protection Error: You cannot delete your own account.']);
        exit();
    }

    // Fetch target role
    $stmt = $conn->prepare("SELECT role FROM users WHERE id = ?");
    $stmt->bind_param("i", $target_id);
// ... existing lines ...
    if (!can_manage_user($actor_role, $target_user['role'])) {
        echo json_encode(['success' => false, 'message' => 'Hierarchy violation: Access Denied']);
        exit();
    }

    $del = $conn->prepare("DELETE FROM users WHERE id = ?");
    $del->bind_param("i", $target_id);
    if ($del->execute()) {
        echo json_encode(['success' => true, 'message' => 'User deleted successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error deleting user']);
    }

} elseif ($action === 'update_role') {
    $target_id = (int)($_POST['user_id'] ?? 0);
    $new_role = $_POST['new_role'] ?? '';

    // Safety: You cannot change YOUR OWN role
    if ($_SESSION['user_id'] == $target_id) {
        echo json_encode(['success' => false, 'message' => 'Protection Error: You cannot change your own role. Please ask another administrator or initiate a Swap.']);
        exit();
    }

    // Fetch target role
    $stmt = $conn->prepare("SELECT role FROM users WHERE id = ?");
    $stmt->bind_param("i", $target_id);
    $stmt->execute();
    $target_role = $stmt->get_result()->fetch_assoc()['role'] ?? '';

    // Hierarchy Check
    if ($actor_role !== ROLE_OWNER) {
        // Co-partners cannot manage other Co-partners or the Owner
        if ($target_role === ROLE_OWNER || $target_role === ROLE_CO_PARTNER || $new_role === ROLE_OWNER || $new_role === ROLE_CO_PARTNER) {
            echo json_encode(['success' => false, 'message' => 'Access Denied: Only the Owner can manage Co-partners/Owner roles.']);
            exit();
        }
        
        // Admins can only promote Students to Admin
        if ($actor_role === ROLE_ADMIN && ($target_role !== ROLE_STUDENT || $new_role !== ROLE_ADMIN)) {
            echo json_encode(['success' => false, 'message' => 'Hierarchy violation: Admins can only promote students to Admin.']);
            exit();
        }
    }

    // Special Swap Logic for Owner <-> Co-partner
    if ($actor_role === ROLE_OWNER && $new_role === ROLE_OWNER && $target_role === ROLE_CO_PARTNER) {
        // Step 1: Set current owner to co-partner
        $conn->query("UPDATE users SET role = 'co-partner' WHERE id = " . $_SESSION['user_id']);
        // Step 2: Set target to owner
        $conn->query("UPDATE users SET role = 'owner' WHERE id = $target_id");
        echo json_encode(['success' => true, 'message' => 'Owner role transferred! You are now a Co-partner.']);
        exit();
    }

    // Enforce Co-partner Limit (Max 2)
    if ($new_role === ROLE_CO_PARTNER && $target_role !== ROLE_CO_PARTNER) {
        $countRes = $conn->query("SELECT COUNT(*) as c FROM users WHERE role = 'co-partner'");
        $count = $countRes->fetch_assoc()['c'];
        if ($count >= 2) {
            echo json_encode(['success' => false, 'message' => 'Maximum limit of 2 Co-partners reached.']);
            exit();
        }
    }

    $upd = $conn->prepare("UPDATE users SET role = ? WHERE id = ?");
    $upd->bind_param("si", $new_role, $target_id);
    if ($upd->execute()) {
        echo json_encode(['success' => true, 'message' => 'Role updated successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error updating role']);
    }
}
?>
