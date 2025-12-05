<?php
// Start session first before any includes
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once('admin/inc/db_config.php');
require_once('admin/inc/essentials.php');

// Check if user is logged in
if (!isset($_SESSION['login']) || $_SESSION['login'] != true) {
    redirect('index.php');
}

$user_id = $_SESSION['uId'] ?? 0;

// Additional check - if user_id is 0, something is wrong
if ($user_id == 0) {
    redirect('index.php');
}

// Get user's bookings - use direct mysqli to avoid die() in select function
$bookings_res = null;
if (isset($con) && $con && $user_id > 0) {
    $bookings_query = "SELECT b.* FROM `bookings` b 
                       WHERE b.user_id = ? 
                       ORDER BY b.booking_date DESC";
    $stmt = mysqli_prepare($con, $bookings_query);
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, 'i', $user_id);
        if (mysqli_stmt_execute($stmt)) {
            $bookings_res = mysqli_stmt_get_result($stmt);
        } else {
            error_log("Bookings query error: " . mysqli_error($con));
        }
        mysqli_stmt_close($stmt);
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php require('inc/links.php'); ?>
    <title><?php echo $settings_r['site_title'] ?> - My Bookings</title>
    <style>
        .booking-card {
            transition: transform 0.2s;
        }
        .booking-card:hover {
            transform: translateY(-3px);
        }
    </style>
</head>
<body class="bg-light">
    <?php require('inc/header.php'); ?>
    
    <div class="container my-5">
        <div class="row">
            <div class="col-12 mb-4 px-4">
                <h2 class="fw-bold">My Bookings</h2>
                <div style="font-size: 14px;">
                    <a href="index.php" class="text-secondary text-decoration-none">HOME</a>
                    <span class="text-secondary"> > </span>
                    <a href="#" class="text-secondary text-decoration-none">My Bookings</a>
                </div>
            </div>
        </div>
        
        <div class="row">
            <?php 
            if ($bookings_res && mysqli_num_rows($bookings_res) > 0) {
                while ($booking = mysqli_fetch_assoc($bookings_res)) {
                    $status_badge = '';
                    $payment_badge = '';
                    
                    if ($booking['booking_status'] == 'booked') {
                        $status_badge = '<span class="badge bg-success">Confirmed</span>';
                    } elseif ($booking['booking_status'] == 'pending') {
                        $status_badge = '<span class="badge bg-warning">Pending</span>';
                    } else {
                        $status_badge = '<span class="badge bg-danger">Cancelled</span>';
                    }
                    
                    if ($booking['payment_status'] == 'paid') {
                        $payment_badge = '<span class="badge bg-success">Paid</span>';
                    } elseif ($booking['payment_status'] == 'pending') {
                        $payment_badge = '<span class="badge bg-warning">Pending</span>';
                    } else {
                        $payment_badge = '<span class="badge bg-danger">Failed</span>';
                    }
                    
                    // Get room image
                    $room_thumb = ROOMS_IMG_PATH . "thumbnail.jpg";
                    $thumb_q = mysqli_query($con, "SELECT * FROM `room_images` 
                      WHERE `room_id`='{$booking['room_id']}' 
                      AND `thumb` = '1'");
                    
                    if (mysqli_num_rows($thumb_q) > 0) {
                        $thumb_res = mysqli_fetch_assoc($thumb_q);
                        $room_thumb = ROOMS_IMG_PATH . $thumb_res['image'];
                    }
                    
                    $checkin = new DateTime($booking['check_in']);
                    $checkout = new DateTime($booking['check_out']);
                    $nights = $checkin->diff($checkout)->days;
                    $booking_date_formatted = date('M d, Y H:i', strtotime($booking['booking_date']));
                    
                    // Use room_name from booking table (already stored there)
                    $display_room_name = $booking['room_name'] ?? 'Room';
                    
                    echo <<<booking
                    <div class="col-lg-6 col-md-12 mb-4">
                        <div class="card booking-card border-0 shadow-sm h-100">
                            <div class="row g-0">
                                <div class="col-md-4">
                                    <img src="$room_thumb" class="img-fluid rounded-start" style="height: 100%; object-fit: cover;" alt="$display_room_name">
                                </div>
                                <div class="col-md-8">
                                    <div class="card-body">
                                        <h5 class="card-title">$display_room_name</h5>
                                        <p class="card-text mb-2">
                                            <strong>Order ID:</strong> <small class="text-muted">{$booking['order_id']}</small>
                                        </p>
                                        <p class="card-text mb-2">
                                            <i class="bi bi-calendar-check"></i> <strong>Check-in:</strong> {$checkin->format('M d, Y')}
                                        </p>
                                        <p class="card-text mb-2">
                                            <i class="bi bi-calendar-x"></i> <strong>Check-out:</strong> {$checkout->format('M d, Y')}
                                        </p>
                                        <p class="card-text mb-2">
                                            <i class="bi bi-moon-stars"></i> <strong>Nights:</strong> $nights night(s)
                                        </p>
                                        <p class="card-text mb-2">
                                            <i class="bi bi-currency-dollar"></i> <strong>Total Amount:</strong> \${$booking['total_pay']}
                                        </p>
                                        <div class="d-flex gap-2 mb-2">
                                            <span>Status: $status_badge</span>
                                            <span>Payment: $payment_badge</span>
                                        </div>
                                        <p class="card-text">
                                            <small class="text-muted">
                                                <i class="bi bi-clock"></i> Booked on: $booking_date_formatted
                                            </small>
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    booking;
                }
            } else {
                echo <<<nobookings
                <div class="col-12">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body text-center p-5">
                            <i class="bi bi-inbox" style="font-size: 4rem; color: #ccc;"></i>
                            <h4 class="mt-3 text-muted">No Bookings Found</h4>
                            <p class="text-muted">You haven't made any bookings yet.</p>
                            <a href="rooms.php" class="btn btn-primary mt-3">Browse Rooms</a>
                        </div>
                    </div>
                </div>
                nobookings;
            }
            ?>
        </div>
    </div>
    
    <?php require('inc/footer.php'); ?>
</body>
</html>

