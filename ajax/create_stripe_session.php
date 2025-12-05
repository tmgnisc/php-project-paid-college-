<?php
// Set content type to JSON
header('Content-Type: application/json');

// Suppress any error output that might break JSON
error_reporting(E_ALL);
ini_set('display_errors', 0);

require('../admin/inc/db_config.php');
require('../admin/inc/essentials.php');
require('../admin/inc/stripe_config.php');
require('../vendor/autoload.php');

session_start();

use Stripe\Stripe;
use Stripe\Checkout\Session;

// Set Stripe API key
Stripe::setApiKey(STRIPE_SECRET_KEY);

// Wrap everything in try-catch to ensure JSON responses
try {
    if (!isset($_SESSION['login']) || $_SESSION['login'] != true) {
        echo json_encode(['status' => 'error', 'message' => 'Please login first']);
        exit;
    }

    if (!isset($_SESSION['room']) || !$_SESSION['room']['available']) {
        echo json_encode(['status' => 'error', 'message' => 'Room not available or booking details incomplete']);
        exit;
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $frm_data = filteration($_POST);
    
    // Validate required fields
    if (empty($frm_data['name']) || empty($frm_data['phonenum']) || empty($frm_data['address']) || 
        empty($frm_data['checkin']) || empty($frm_data['checkout'])) {
        echo json_encode(['status' => 'error', 'message' => 'All fields are required']);
        exit;
    }
    
    // Validate dates
    $checkin_date = new DateTime($frm_data['checkin']);
    $checkout_date = new DateTime($frm_data['checkout']);
    $today_date = new DateTime(date("Y-m-d"));
    
    if ($checkin_date == $checkout_date) {
        echo json_encode(['status' => 'error', 'message' => 'Check-in and check-out dates cannot be the same']);
        exit;
    }
    
    if ($checkout_date < $checkin_date) {
        echo json_encode(['status' => 'error', 'message' => 'Check-out date must be after check-in date']);
        exit;
    }
    
    if ($checkin_date < $today_date) {
        echo json_encode(['status' => 'error', 'message' => 'Check-in date cannot be in the past']);
        exit;
    }
    
    // Calculate days and total amount
    $count_days = date_diff($checkin_date, $checkout_date)->days;
    $total_amount = $_SESSION['room']['price'] * $count_days;
    
    // Generate unique order ID
    $order_id = 'HOTEL-' . uniqid() . '-' . time();
    
    // Check if bookings table exists
    $table_check = mysqli_query($con, "SHOW TABLES LIKE 'bookings'");
    if (mysqli_num_rows($table_check) == 0) {
        echo json_encode(['status' => 'error', 'message' => 'Database tables not set up. Please run the setup script first.']);
        exit;
    }
    
    // Create booking record in database (pending status)
    $booking_query = "INSERT INTO `bookings` (`user_id`, `room_id`, `check_in`, `check_out`, `order_id`, `room_name`, `price`, `total_pay`, `booking_status`, `payment_status`, `user_name`, `phonenum`, `address`) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pending', 'pending', ?, ?, ?)";
    
    // Use direct mysqli to avoid die() in insert function
    $stmt = mysqli_prepare($con, $booking_query);
    if ($stmt) {
        $user_id = $_SESSION['uId'];
        $room_id = $_SESSION['room']['id'];
        $checkin = $frm_data['checkin'];
        $checkout = $frm_data['checkout'];
        $room_name = $_SESSION['room']['name'];
        $room_price = $_SESSION['room']['price'];
        $user_name = $frm_data['name'];
        $phonenum = $frm_data['phonenum'];
        $address = $frm_data['address'];
        
        mysqli_stmt_bind_param($stmt, 'iisssssssss', $user_id, $room_id, $checkin, $checkout, $order_id, $room_name, $room_price, $total_amount, $user_name, $phonenum, $address);
        if (mysqli_stmt_execute($stmt)) {
            $booking_id = mysqli_insert_id($con);
            mysqli_stmt_close($stmt);
        } else {
            $error_msg = mysqli_error($con);
            mysqli_stmt_close($stmt);
            echo json_encode(['status' => 'error', 'message' => 'Failed to create booking: ' . $error_msg]);
            exit;
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Database error: ' . mysqli_error($con)]);
        exit;
    }
    
    // Create Stripe Checkout Session
    try {
        $session = Session::create([
            'payment_method_types' => ['card'],
            'line_items' => [[
                'price_data' => [
                    'currency' => 'usd',
                    'product_data' => [
                        'name' => $_SESSION['room']['name'] . ' - Hotel Booking',
                        'description' => 'Check-in: ' . $frm_data['checkin'] . ' | Check-out: ' . $frm_data['checkout'] . ' | Days: ' . $count_days,
                    ],
                    'unit_amount' => $total_amount * 100, // Convert to cents
                ],
                'quantity' => 1,
            ]],
            'mode' => 'payment',
            'success_url' => SITE_URL . 'payment/success.php?session_id={CHECKOUT_SESSION_ID}&order_id=' . $order_id,
            'cancel_url' => SITE_URL . 'payment/failure.php?order_id=' . $order_id,
            'metadata' => [
                'booking_id' => $booking_id,
                'order_id' => $order_id,
                'user_id' => $_SESSION['uId'],
                'room_id' => $_SESSION['room']['id'],
            ],
        ]);
        
        // Store session ID in database
        $payment_query = "INSERT INTO `payments` (`booking_id`, `order_id`, `stripe_session_id`, `amount`, `currency`, `payment_status`) VALUES (?, ?, ?, ?, ?, ?)";
        $payment_stmt = mysqli_prepare($con, $payment_query);
        if ($payment_stmt) {
            $session_id = $session->id;
            $currency = 'usd';
            $payment_status = 'pending';
            mysqli_stmt_bind_param($payment_stmt, 'isssss', $booking_id, $order_id, $session_id, $total_amount, $currency, $payment_status);
            if (!mysqli_stmt_execute($payment_stmt)) {
                // Log error but don't fail the payment
                error_log("Failed to save payment record: " . mysqli_error($con));
            }
            mysqli_stmt_close($payment_stmt);
        }
        
        // Store order_id in session for later use
        $_SESSION['current_order_id'] = $order_id;
        
        echo json_encode([
            'status' => 'success',
            'session_id' => $session->id,
            'url' => $session->url
        ]);
        
    } catch (Exception $e) {
        // Update booking status to failed (use direct mysqli to avoid die())
        if (isset($booking_id)) {
            $update_stmt = mysqli_prepare($con, "UPDATE `bookings` SET `booking_status`='failed' WHERE `id`=?");
            if ($update_stmt) {
                mysqli_stmt_bind_param($update_stmt, 'i', $booking_id);
                mysqli_stmt_execute($update_stmt);
                mysqli_stmt_close($update_stmt);
            }
        }
        
        echo json_encode([
            'status' => 'error',
            'message' => 'Failed to create payment session: ' . $e->getMessage()
        ]);
    }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
    }
} catch (Exception $e) {
    // Catch any unexpected errors and return as JSON
    echo json_encode([
        'status' => 'error',
        'message' => 'An unexpected error occurred: ' . $e->getMessage()
    ]);
    exit;
} catch (Error $e) {
    // Catch fatal errors
    echo json_encode([
        'status' => 'error',
        'message' => 'A fatal error occurred: ' . $e->getMessage()
    ]);
    exit;
}

?>

