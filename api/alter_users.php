<?php
require_once 'includes/db.php';

$res = $conn->query("ALTER TABLE users ADD COLUMN IF NOT EXISTS last_login TIMESTAMP NULL DEFAULT NULL AFTER created_at");

if ($res) {
    echo "Column last_login added successfully!";
} else {
    echo "Error adding column: " . $conn->error;
}
?>
