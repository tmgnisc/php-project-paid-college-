<?php
/**
 * Database Setup Script for Bookings and Payments
 * Run this file once to create the necessary database tables
 * Access via: http://localhost/Hotel/admin/inc/setup_booking_tables.php
 */

require('db_config.php');

// Read SQL file
$sql_file = __DIR__ . '/create_booking_tables.sql';
$sql = file_get_contents($sql_file);

if ($sql === false) {
    die("Error: Could not read SQL file: $sql_file");
}

// Execute SQL queries
$queries = array_filter(array_map('trim', explode(';', $sql)));

$success_count = 0;
$error_count = 0;

foreach ($queries as $query) {
    if (empty($query)) {
        continue;
    }
    
    if (mysqli_query($con, $query)) {
        $success_count++;
    } else {
        $error_count++;
        echo "Error: " . mysqli_error($con) . "<br>";
        echo "Query: " . htmlspecialchars($query) . "<br><br>";
    }
}

echo "<h2>Database Setup Complete</h2>";
echo "<p>Successfully executed: $success_count queries</p>";
if ($error_count > 0) {
    echo "<p style='color: red;'>Errors: $error_count queries</p>";
} else {
    echo "<p style='color: green;'>All tables created successfully!</p>";
    echo "<p>You can now delete this file for security purposes.</p>";
}

?>


