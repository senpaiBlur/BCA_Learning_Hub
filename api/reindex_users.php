<?php
require_once __DIR__ . '/../includes/db.php';

echo "<h3>Re-indexing Users and Setting up Admins...</h3>";

// 1. Disable foreign key checks temporarily if any (not strictly needed here but good practice)
$conn->query("SET FOREIGN_KEY_CHECKS = 0");

// 2. Truncate users table to reset ID to 1
if ($conn->query("TRUNCATE TABLE users")) {
    echo "Users table truncated (IDs reset).<br>";
} else {
    echo "Error truncating users: " . $conn->error . "<br>";
    exit();
}

// 3. Define Admins
$admins = [
    [
        'id' => 1,
        'name' => 'Saurabh',
        'email' => 'Saurabh@bca-hub.com',
        'password' => 'Saurabh@2026'
    ],
    [
        'id' => 2,
        'name' => 'Shivam',
        'email' => 'Shivam@bca-hub.com',
        'password' => 'Shivam@2026'
    ],
    [
        'id' => 3,
        'name' => 'Yash',
        'email' => 'Yash@bca-hub.com',
        'password' => 'Yash@2026'
    ]
];

// 4. Insert Admins with specific IDs
foreach ($admins as $admin) {
    $hashed = password_hash($admin['password'], PASSWORD_DEFAULT);
    $stmt = $conn->prepare("INSERT INTO users (id, name, email, password, role) VALUES (?, ?, ?, ?, 'admin')");
    $stmt->bind_param("isss", $admin['id'], $admin['name'], $admin['email'], $hashed);
    
    if ($stmt->execute()) {
        echo "Created Admin ID {$admin['id']}: " . htmlspecialchars($admin['email']) . "<br>";
    } else {
        echo "Failed to create Admin {$admin['id']}: " . $stmt->error . "<br>";
    }
}

// 5. Re-assign all existing materials to Saurabh (ID 1) to prevent orphaned records
$updateMaterials = $conn->query("UPDATE materials SET uploader_id = 1");
if ($updateMaterials) {
    echo "All existing courses re-assigned to Saurabh (Admin ID 1).<br>";
}

// 6. Re-enable foreign key checks
$conn->query("SET FOREIGN_KEY_CHECKS = 1");

echo "<br><b>Operation Complete. Please delete this file!</b>";
?>
