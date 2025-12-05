<?php
require('../admin/inc/db_config.php');
require('../admin/inc/essentials.php');
require('../admin/inc/stripe_config.php');
require('../vendor/autoload.php');

use Stripe\Stripe;
use Stripe\Webhook;
use Stripe\Exception\SignatureVerificationException;

// Set Stripe API key
Stripe::setApiKey(STRIPE_SECRET_KEY);

// Get the raw POST body
$payload = @file_get_contents('php://input');
$sig_header = $_SERVER['HTTP_STRIPE_SIGNATURE'] ?? '';

if (empty(STRIPE_WEBHOOK_SECRET)) {
    http_response_code(400);
    echo json_encode(['error' => 'Webhook secret not configured']);
    exit;
}

try {
    // Verify webhook signature
    $event = Webhook::constructEvent($payload, $sig_header, STRIPE_WEBHOOK_SECRET);
} catch (\UnexpectedValueException $e) {
    // Invalid payload
    http_response_code(400);
    echo json_encode(['error' => 'Invalid payload']);
    exit;
} catch (SignatureVerificationException $e) {
    // Invalid signature
    http_response_code(400);
    echo json_encode(['error' => 'Invalid signature']);
    exit;
}

// Handle the event
switch ($event->type) {
    case 'checkout.session.completed':
        $session = $event->data->object;
        
        if ($session->payment_status === 'paid') {
            $order_id = $session->metadata->order_id ?? null;
            $booking_id = $session->metadata->booking_id ?? null;
            
            if ($order_id) {
                // Update booking status
                $update_booking = "UPDATE `bookings` SET `booking_status`='booked', `payment_status`='paid' WHERE `order_id`=?";
                update($update_booking, [$order_id], 's');
                
                // Update payment record
                $payment_intent_id = $session->payment_intent ?? null;
                $update_payment = "UPDATE `payments` SET `stripe_payment_intent_id`=?, `payment_status`='paid', `transaction_id`=?, `payment_date`=NOW() WHERE `order_id`=?";
                update($update_payment, [$payment_intent_id, $session->id, $order_id], 'sss');
            }
        }
        break;
        
    case 'payment_intent.succeeded':
        $payment_intent = $event->data->object;
        // Additional handling if needed
        break;
        
    case 'payment_intent.payment_failed':
        $payment_intent = $event->data->object;
        // Handle failed payment
        if (isset($payment_intent->metadata->order_id)) {
            $order_id = $payment_intent->metadata->order_id;
            $update_booking = "UPDATE `bookings` SET `booking_status`='cancelled', `payment_status`='failed' WHERE `order_id`=?";
            update($update_booking, [$order_id], 's');
            
            $update_payment = "UPDATE `payments` SET `payment_status`='failed' WHERE `order_id`=?";
            update($update_payment, [$order_id], 's');
        }
        break;
        
    default:
        // Unexpected event type
        http_response_code(400);
        echo json_encode(['error' => 'Unexpected event type']);
        exit;
}

// Return a response to acknowledge receipt of the event
http_response_code(200);
echo json_encode(['received' => true]);

?>


