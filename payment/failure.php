<?php
require('../admin/inc/db_config.php');
require('../admin/inc/essentials.php');

session_start();

$order_id = isset($_GET['order_id']) ? filteration($_GET)['order_id'] : null;

// Update booking status to cancelled if order_id exists
if ($order_id) {
    $update_booking = "UPDATE `bookings` SET `booking_status`='cancelled', `payment_status`='failed' WHERE `order_id`=?";
    update($update_booking, [$order_id], 's');
    
    $update_payment = "UPDATE `payments` SET `payment_status`='failed' WHERE `order_id`=?";
    update($update_payment, [$order_id], 's');
}

// Clear session room data
unset($_SESSION['room']);
unset($_SESSION['current_order_id']);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php require('../inc/links.php'); ?>
    <title>Payment Failed - <?php echo $settings_r['site_title'] ?></title>
    <style>
        .failure-container {
            min-height: 60vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
    </style>
</head>
<body class="bg-light">
    <?php require('../inc/header.php'); ?>
    
    <div class="container failure-container">
        <div class="row justify-content-center">
            <div class="col-lg-6 col-md-8">
                <div class="card shadow-sm border-0 rounded-3">
                    <div class="card-body text-center p-5">
                        <div class="mb-4">
                            <i class="bi bi-x-circle-fill text-danger" style="font-size: 4rem;"></i>
                        </div>
                        <h2 class="mb-3 text-danger">Payment Unsuccessful</h2>
                        <p class="mb-4">Your payment was cancelled or failed. Please try again or contact support if the problem persists.</p>
                        <?php if ($order_id): ?>
                        <p class="text-muted mb-4">
                            <strong>Order ID:</strong> <?php echo htmlspecialchars($order_id); ?>
                        </p>
                        <?php endif; ?>
                        <div class="d-flex gap-3 justify-content-center">
                            <a href="rooms.php" class="btn btn-primary">Browse Rooms</a>
                            <a href="index.php" class="btn btn-outline-secondary">Go to Home</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <?php require('../inc/footer.php'); ?>
</body>
</html>
