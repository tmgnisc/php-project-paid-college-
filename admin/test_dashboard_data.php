<?php
require('inc/db_config.php');
require('inc/essentials.php');
adminLogin();

echo "<h2>Dashboard Data Test</h2>";
echo "<pre>";

// Test connection
if (isset($con) && $con) {
    echo "✓ Database connection: OK\n";
} else {
    echo "✗ Database connection: FAILED\n";
    exit;
}

// Test if bookings table exists
$table_check = mysqli_query($con, "SHOW TABLES LIKE 'bookings'");
if ($table_check && mysqli_num_rows($table_check) > 0) {
    echo "✓ Bookings table: EXISTS\n\n";
} else {
    echo "✗ Bookings table: NOT FOUND\n";
    exit;
}

// Test queries
$queries = [
    'Total Bookings' => "SELECT COUNT(*) as total FROM `bookings`",
    'Total Revenue' => "SELECT COALESCE(SUM(total_pay), 0) as total FROM `bookings` WHERE `payment_status`='paid'",
    'Pending Bookings' => "SELECT COUNT(*) as total FROM `bookings` WHERE `booking_status`='pending'",
    'Confirmed Bookings' => "SELECT COUNT(*) as total FROM `bookings` WHERE `booking_status`='booked'",
    'Paid Bookings' => "SELECT COUNT(*) as total FROM `bookings` WHERE `payment_status`='paid'",
];

foreach ($queries as $name => $query) {
    $res = mysqli_query($con, $query);
    if ($res !== false) {
        $row = mysqli_fetch_assoc($res);
        echo "$name: " . ($row['total'] ?? 'NULL') . "\n";
    } else {
        echo "$name: ERROR - " . mysqli_error($con) . "\n";
    }
}

// Show sample data
echo "\n--- Sample Bookings Data ---\n";
$sample_query = "SELECT id, order_id, user_name, room_name, total_pay, booking_status, payment_status, booking_date FROM `bookings` LIMIT 5";
$sample_res = mysqli_query($con, $sample_query);
if ($sample_res && mysqli_num_rows($sample_res) > 0) {
    while ($row = mysqli_fetch_assoc($sample_res)) {
        echo "ID: {$row['id']}, Order: {$row['order_id']}, User: {$row['user_name']}, Room: {$row['room_name']}, Amount: {$row['total_pay']}, Status: {$row['booking_status']}, Payment: {$row['payment_status']}\n";
    }
} else {
    echo "No bookings found in database\n";
}

echo "</pre>";

?>


