<?php
require('../inc/db_config.php');
require('../inc/essentials.php');
adminLogin();

header('Content-Type: application/json');

if (!isset($_GET['id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Booking ID required']);
    exit;
}

$booking_id = intval($_GET['id']);

$query = "SELECT b.*, u.email FROM `bookings` b 
          LEFT JOIN `user_cred` u ON b.user_id = u.id 
          WHERE b.id = ? LIMIT 1";

$stmt = mysqli_prepare($con, $query);
if ($stmt) {
    mysqli_stmt_bind_param($stmt, 'i', $booking_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if ($result && mysqli_num_rows($result) > 0) {
        $booking = mysqli_fetch_assoc($result);
        // Format dates
        $booking['check_in'] = date('M d, Y', strtotime($booking['check_in']));
        $booking['check_out'] = date('M d, Y', strtotime($booking['check_out']));
        $booking['booking_date'] = date('M d, Y H:i', strtotime($booking['booking_date']));
        
        echo json_encode(['status' => 'success', 'booking' => $booking]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Booking not found']);
    }
    mysqli_stmt_close($stmt);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Database error']);
}

?>


