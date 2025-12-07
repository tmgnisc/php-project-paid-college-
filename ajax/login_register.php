<?php
// Enable error reporting for debugging (remove in production)
error_reporting(E_ALL);
ini_set('display_errors', 0); // Don't display, but log
ini_set('log_errors', 1);

// Create a debug log file
$debug_log = __DIR__ . '/../debug_registration.log';

function debug_log($message) {
    global $debug_log;
    $timestamp = date('Y-m-d H:i:s');
    $log_message = "[$timestamp] $message\n";
    file_put_contents($debug_log, $log_message, FILE_APPEND);
    error_log($message); // Also log to PHP error log
}

require('../admin/inc/db_config.php');
require('../admin/inc/essentials.php');

// ❌ SendGrid removed (commented)
// require("../inc/sendgrid/sendgrid-php.php");

// ❌ Email function disabled
/*
function send_mail($uemail,$name,$token)
{
    $email = new \SendGrid\Mail\Mail();
    $email->setFrom("anshika@gmail.com", "My Hotel");
    $email->setSubject("Account Verification Link");

    $email->addTo($uemail,$name);

    $email->addContent(
        "text/html",
        "Click the link to confirm your email: <br>
        <a href='".SITE_URL."email_confirm.php?email=$uemail&token=$token"."'>CLICK ME</a>
        "
    );
    $sendgrid = new \SendGrid(SENDGRID_API_KEY);

    try{
        $sendgrid->send($email);
        return 1;
    }
    catch (Exception $e){
        return 0;
    }
}
*/

if (isset($_POST['register'])) {
    debug_log("=== REGISTRATION ATTEMPT START ===");
    debug_log("POST data received: " . print_r($_POST, true));
    debug_log("FILES data: " . print_r($_FILES, true));
    
    $data = filteration($_POST);
    debug_log("Filtered data: " . print_r($data, true));

    // match password and confirm password
    if ($data['pass'] != $data['cpass']) {
        debug_log("Password mismatch");
        echo 'pass_mismatch';
        exit;
    }

    // check user exist or not
    debug_log("Checking if user exists: email=" . $data['email'] . ", phone=" . $data['phonenum']);
    $u_exist = select(
        "SELECT * FROM `user_cred` WHERE `email` = ? OR `phonenum`=? LIMIT 1",
        [$data['email'], $data['phonenum']],
        "ss"
    );

    if (!$u_exist) {
        debug_log("ERROR: Select query failed - " . mysqli_error($GLOBALS['con']));
        echo 'ins_failed';
        exit;
    }

    if (mysqli_num_rows($u_exist) != 0) {
        $u_exist_fetch = mysqli_fetch_assoc($u_exist);
        $reason = ($u_exist_fetch['email'] == $data['email']) ? 'email_already' : 'phone_already';
        debug_log("User already exists: $reason");
        echo $reason;
        exit;
    }
    debug_log("User does not exist, proceeding with registration");

    // upload user image
    // Check if file was uploaded
    if (!isset($_FILES['profile']) || $_FILES['profile']['error'] !== UPLOAD_ERR_OK) {
        $error_code = $_FILES['profile']['error'] ?? 'no file';
        debug_log("ERROR: File upload error - Code: $error_code");
        echo 'upd_failed';
        exit;
    }
    
    debug_log("Uploading user image...");
    $img = uploadUserImage($_FILES['profile']);
    debug_log("Image upload result: $img");

    if ($img == 'inv_img') {
        debug_log("Invalid image format");
        echo 'inv_img';
        exit;
    } elseif ($img == 'upd_failed') {
        debug_log("ERROR: Image upload failed for user: " . $data['email']);
        echo 'upd_failed';
        exit;
    }
    debug_log("Image uploaded successfully: $img");

    // ❌ Token and email verification disabled
    // $token = bin2hex(random_bytes(16));
    // if(!send_mail($data['email'],$data['name'],$token)){
    //     echo 'mail_failed';
    //     exit;
    // }

    // Instead → automatic verification
    $token = "";           // token not used
    $is_verified = 1;      // user is auto verified

    // password encryption
    $enc_pass = password_hash($data['pass'], PASSWORD_BCRYPT);
    debug_log("Password hashed successfully");

    // insert query
    $query = "INSERT INTO `user_cred`(`name`, `email`, `address`, `phonenum`, `pincode`, `dob`, `profile`, 
    `password`, `token`, `is_verified`) VALUES (?,?,?,?,?,?,?,?,?,?)";

    $values = [
        $data['name'],
        $data['email'],
        $data['address'],
        $data['phonenum'],
        $data['pincode'],
        $data['dob'],
        $img,
        $enc_pass,
        $token,
        $is_verified
    ];
    
    debug_log("Preparing to insert user with values:");
    debug_log("  Name: " . $data['name']);
    debug_log("  Email: " . $data['email']);
    debug_log("  Phone: " . $data['phonenum']);
    debug_log("  Image: $img");
    debug_log("  Token: '$token'");
    debug_log("  Is Verified: $is_verified");
    debug_log("  Query: $query");
    debug_log("  Type string: sssssssssi");

    // Fix: is_verified is integer (i), not string (s) - should be 'sssssssssi' not 'ssssssssss'
    debug_log("Executing insert query...");
    $result = insert($query, $values, 'sssssssssi');
    debug_log("Insert result: " . var_export($result, true));
    
    if (isset($GLOBALS['con'])) {
        $con = $GLOBALS['con'];
        $mysql_error = mysqli_error($con);
        $mysql_errno = mysqli_errno($con);
        if ($mysql_error) {
            debug_log("MySQL Error: $mysql_error (Errno: $mysql_errno)");
        }
    }
    
    if ($result && $result > 0) {
        debug_log("SUCCESS: Registration completed for user: " . $data['email']);
        debug_log("=== REGISTRATION ATTEMPT END - SUCCESS ===");
        echo 1;
    } else {
        debug_log("ERROR: Registration failed - Insert query returned: " . var_export($result, true));
        debug_log("Values array: " . print_r($values, true));
        debug_log("=== REGISTRATION ATTEMPT END - FAILED ===");
        echo 'ins_failed';
    }
}



if (isset($_POST['login'])) {
    $data = filteration($_POST);

    // Fix 1: SELECT must have 3 parameters
    $u_exist = select(
        "SELECT * FROM `user_cred` WHERE `email` = ? OR `name` = ? OR `phonenum` = ? LIMIT 1",
        [$data['email_name_mob'], $data['email_name_mob'], $data['email_name_mob']],
        "sss"
    );

    if (mysqli_num_rows($u_exist) == 0) {
        echo 'inv_email_name_mob';
        exit;
    }

    $u_fetch = mysqli_fetch_assoc($u_exist);

    // Fix 2: Check inactive
    if ($u_fetch['status'] == 0) {
        echo 'inactive';
        exit;
    }

    // Fix 3: Proper password verify
    if (!password_verify($data['pass'], $u_fetch['password'])) {
        echo 'invalid_pass';
        exit;
    }

    // SUCCESS LOGIN
    session_start();
    $_SESSION['login'] = true;
    $_SESSION['uId'] = $u_fetch['id'];
    $_SESSION['uname'] = $u_fetch['name'];
    $_SESSION['uPic'] = $u_fetch['profile'];
    $_SESSION['uPhone'] = $u_fetch['phonenum'];

    echo 1;
}


if (isset($_POST['forgot_pass'])) {

    $data = filteration($_POST);

    // Check if user exists by name or mobile
    $u_exist = select(
        "SELECT * FROM `user_cred` WHERE `name` = ? OR `phonenum` = ? LIMIT 1",
        [$data['name_mob'], $data['name_mob']],
        "ss"
    );

    if (mysqli_num_rows($u_exist) == 0) {
        echo 'inv_name_mob';
    } else {

        $u_fetch = mysqli_fetch_assoc($u_exist);

        // Check inactive user
        if ($u_fetch['status'] == 0) {
            echo 'inactive';
            exit;
        }

        // Hash new password
        $hashed_pass = password_hash($data['pass'], PASSWORD_BCRYPT);

        // Update password
        $update = update(
            "UPDATE `user_cred` SET `password` = ? WHERE `id` = ?",
            [$hashed_pass, $u_fetch['id']],
            "si"
        );

        if ($update) {
            echo 'success';
        } else {
            echo 'error';
        }
    }

    exit;
}


?>
