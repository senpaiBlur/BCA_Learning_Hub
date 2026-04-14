<?php
require 'includes/db.php';

function describeTable($conn, $table) {
    echo "<h3>Table: $table</h3><pre>";
    $res = $conn->query("DESCRIBE $table");
    while($row = $res->fetch_assoc()) print_r($row);
    echo "</pre>";
}

describeTable($conn, 'users');
describeTable($conn, 'subjects');
describeTable($conn, 'materials');

// Also check for foreign keys
$res = $conn->query("SELECT TABLE_NAME, COLUMN_NAME, CONSTRAINT_NAME, REFERENCED_TABLE_NAME, REFERENCED_COLUMN_NAME FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE WHERE REFERENCED_TABLE_SCHEMA = 'bca_hub' OR TABLE_SCHEMA = 'bca_hub'");
if ($res) {
    echo "<h3>Foreign Keys</h3><pre>";
    while($row = $res->fetch_assoc()) print_r($row);
    echo "</pre>";
}
?>
