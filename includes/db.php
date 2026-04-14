<?php
$is_local = ($_SERVER['SERVER_NAME'] === 'localhost' || $_SERVER['SERVER_ADDR'] === '127.0.0.1');

if ($is_local) {
    // Local XAMPP Configuration
    $host = 'localhost';
    $user = 'root';
    $pass = ''; // YOUR_LOCAL_PASSWORD
    $db   = 'bca_learning';
} else {
    // Live InfinityFree Configuration
    $host = 'YOUR_HOST_NAME';
    $user = 'YOUR_DB_USER';
    $pass = 'YOUR_LIVE_PASSWORD';
    $db   = 'YOUR_DB_NAME';
}

$conn = new mysqli($host, $user, $pass);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Ensure database exists (Mainly for local, on live you typically create it first)
$conn->query("CREATE DATABASE IF NOT EXISTS `$db` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
$conn->select_db($db);

// Remaining table creation logic (Keeping for safety)
$conn->query("CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role VARCHAR(50) DEFAULT 'student',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

$role_check = $conn->query("SHOW COLUMNS FROM users LIKE 'role'");
if ($role_check && $role_check->num_rows == 0) {
    $conn->query("ALTER TABLE users ADD COLUMN role VARCHAR(50) DEFAULT 'student'");
}

$q_check = $conn->query("SHOW COLUMNS FROM users LIKE 'security_question'");
if ($q_check && $q_check->num_rows == 0) {
    $conn->query("ALTER TABLE users ADD COLUMN security_question VARCHAR(255) DEFAULT 'What is your childhood nickname?'");
    $conn->query("ALTER TABLE users ADD COLUMN sec_answer VARCHAR(255) DEFAULT 'bca'");
}

$conn->query("CREATE TABLE IF NOT EXISTS subjects (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    category VARCHAR(100) DEFAULT 'Uncategorized',
    thumbnail VARCHAR(255)
)");

$conn->query("CREATE TABLE IF NOT EXISTS materials (
    id INT AUTO_INCREMENT PRIMARY KEY,
    subject_id INT NOT NULL,
    video_url VARCHAR(255),
    notes_url VARCHAR(255),
    exam_url VARCHAR(255),
    unit_name VARCHAR(255),
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    uploader_id INT,
    FOREIGN KEY (uploader_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE CASCADE
)");

$conn->query("CREATE TABLE IF NOT EXISTS user_progress (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    material_id INT NOT NULL,
    completed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY (user_id, material_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (material_id) REFERENCES materials(id) ON DELETE CASCADE
)");
?>
