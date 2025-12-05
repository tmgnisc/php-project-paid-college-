<?php
// --- Configuration (Use Sandbox Credentials for your Project) ---
// IMPORTANT: Never expose the secret key in the frontend/client-side code!
$TEST_SECRET_KEY = '8gBm/:&EnhH.1/q'; 
$PRODUCT_CODE = 'EPAYTEST';
$ESEWA_PAYMENT_URL = 'https://rc-epay.esewa.com.np/api/epay/main/v2/form'; // Sandbox URL

// --- 1. Set Transaction Data (From your Hotel Booking System) ---
$transaction_uuid = uniqid('HOTEL-TXN-'); // Generate a unique ID for this transaction
$amount = 5000;  // Room Price
$tax_amount = 1000; // Tax
$total_amount = $amount + $tax_amount; 

// Define your system's URLs
$success_url = 'http://localhost/hotel/payment/success.php'; // Change this to your actual success path
$failure_url = 'http://localhost/hotel/payment/failure.php'; // Change this to your actual failure path

// --- 2. Generate the HMAC-SHA256 Signature ---
// The fields must be concatenated in this specific order: total_amount, transaction_uuid, product_code
$message = "total_amount={$total_amount},transaction_uuid={$transaction_uuid},product_code={$PRODUCT_CODE}";

// PHP's hash_hmac function is perfect for this. We use `true` for raw binary output.
$hash_value = hash_hmac('sha256', $message, $TEST_SECRET_KEY, true);

// The signature must be Base64 encoded.
$signature = base64_encode($hash_value); 

// --- 3. Prepare Form Fields ---
$payment_data = [
    'amount' => $amount,
    'tax_amount' => $tax_amount,
    'total_amount' => $total_amount,
    'transaction_uuid' => $transaction_uuid,
    'product_code' => $PRODUCT_CODE,

    'product_service_charge' => 0,
    'product_delivery_charge' => 0,

    'success_url' => $success_url,
    'failure_url' => $failure_url,
    'signed_field_names' => 'total_amount,transaction_uuid,product_code', // Fields used for signature
    'signature' => $signature,
];

// Optional: Store the transaction_uuid in a session/database for later verification
session_start();
$_SESSION['last_esewa_txn'] = $transaction_uuid; 
$_SESSION['last_esewa_amount'] = $total_amount;
?>

<!DOCTYPE html>
<html>
<head>
    <title>Redirecting to eSewa...</title>
</head>
<body onload="document.getElementById('esewaForm').submit()">
    <h1>Please wait, redirecting to eSewa...</h1>

    <form action="<?php echo $ESEWA_PAYMENT_URL; ?>" method="POST" id="esewaForm">
        <?php foreach ($payment_data as $key => $value): ?>
            <input type="hidden" name="<?php echo htmlspecialchars($key); ?>" value="<?php echo htmlspecialchars($value); ?>" />
        <?php endforeach; ?>
    </form>
</body>
</html>