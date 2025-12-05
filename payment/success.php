<?php
session_start();
require('../admin/inc/db_config.php');
require('../admin/inc/essentials.php');
require('../admin/inc/stripe_config.php');
require('../vendor/autoload.php');

use Stripe\Stripe;
use Stripe\Checkout\Session;

// Set Stripe API key
Stripe::setApiKey(STRIPE_SECRET_KEY);

// Initialize variables
$success_message = "Payment successful!";
$booking_confirmed = false;

if (!isset($_GET['session_id']) || !isset($_GET['order_id'])) {
    redirect('index.php');
}

$session_id = filteration($_GET)['session_id'];
$order_id = filteration($_GET)['order_id'];

try {
    // Retrieve the session from Stripe
    $session = Session::retrieve($session_id);
    
    if ($session->payment_status === 'paid') {
        // Get booking details
        $booking_query = "SELECT * FROM `bookings` WHERE `order_id`=? LIMIT 1";
        $booking_res = select($booking_query, [$order_id], 's');
        
        if (mysqli_num_rows($booking_res) > 0) {
            $booking_data = mysqli_fetch_assoc($booking_res);
            
            // Update booking status using direct mysqli
            $update_booking = "UPDATE `bookings` SET `booking_status`='booked', `payment_status`='paid' WHERE `order_id`=?";
            $update_stmt = mysqli_prepare($con, $update_booking);
            if ($update_stmt) {
                mysqli_stmt_bind_param($update_stmt, 's', $order_id);
                mysqli_stmt_execute($update_stmt);
                mysqli_stmt_close($update_stmt);
            }
            
            // Update payment record using direct mysqli
            $payment_intent_id = $session->payment_intent ?? null;
            $update_payment = "UPDATE `payments` SET `stripe_payment_intent_id`=?, `payment_status`='paid', `transaction_id`=?, `payment_date`=NOW() WHERE `order_id`=?";
            $payment_update_stmt = mysqli_prepare($con, $update_payment);
            if ($payment_update_stmt) {
                mysqli_stmt_bind_param($payment_update_stmt, 'sss', $payment_intent_id, $session_id, $order_id);
                mysqli_stmt_execute($payment_update_stmt);
                mysqli_stmt_close($payment_update_stmt);
            }
            
            // Clear session room data
            unset($_SESSION['room']);
            unset($_SESSION['current_order_id']);
            
            $success_message = "Your booking has been confirmed! We look forward to hosting you.";
            $booking_id = $booking_data['id'];
            $booking_confirmed = true;
        } else {
            $success_message = "Payment successful, but booking not found. Please contact support with Order ID: " . htmlspecialchars($order_id);
            $booking_id = null;
            $booking_confirmed = false;
        }
    } else {
        $success_message = "Payment is still processing. Please wait...";
        $booking_id = null;
    }
} catch (Exception $e) {
    $success_message = "Error verifying payment: " . $e->getMessage();
    $booking_id = null;
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Success - <?php echo $settings_r['site_title'] ?? 'Hotel Booking' ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" crossorigin="anonymous">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Merienda:wght@300..900&family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="../css/common.css">
    <style>
        .success-container {
            min-height: 60vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
    </style>
</head>
<body class="bg-light">
    <?php require('../inc/header.php'); ?>
    
    <div class="container success-container">
        <div class="row justify-content-center">
            <div class="col-lg-6 col-md-8">
                <div class="card shadow-sm border-0 rounded-3">
                    <div class="card-body text-center p-5">
                        <div class="mb-4">
                            <i class="bi bi-check-circle-fill text-success" style="font-size: 4rem;"></i>
                        </div>
                        <h2 class="mb-3 text-success">Payment Successful!</h2>
                        <p class="mb-4"><?php echo $success_message; ?></p>
                        <?php if (isset($order_id)): ?>
                        <p class="text-muted mb-4">
                            <strong>Order ID:</strong> <?php echo htmlspecialchars($order_id); ?>
                        </p>
                        <?php endif; ?>
                        <?php if (isset($booking_confirmed) && $booking_confirmed): ?>
                        <div class="alert alert-info mb-4">
                            <i class="bi bi-info-circle"></i> You will be redirected to the home page in <span id="countdown">5</span> seconds...
                        </div>
                        <?php endif; ?>
                        <div class="d-flex gap-3 justify-content-center">
                            <a href="../index.php" class="btn btn-primary">Go to Home</a>
                            <?php if (isset($_SESSION['login']) && $_SESSION['login'] == true): ?>
                            <a href="../bookings.php" class="btn btn-outline-primary">View My Bookings</a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <?php require('../inc/footer.php'); ?>
    
    <?php if (isset($booking_confirmed) && $booking_confirmed): ?>
    <script>
        // Auto-redirect after 5 seconds
        let countdown = 5;
        const countdownElement = document.getElementById('countdown');
        
        const timer = setInterval(function() {
            countdown--;
            if (countdownElement) {
                countdownElement.textContent = countdown;
            }
            if (countdown <= 0) {
                clearInterval(timer);
                window.location.href = '../index.php';
            }
        }, 1000);
    </script>
    <?php endif; ?>
</body>
</html>
