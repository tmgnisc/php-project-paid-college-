<?php

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
    $data = filteration($_POST);

    // match password and confirm password
    if ($data['pass'] != $data['cpass']) {
        echo 'pass_mismatch';
        exit;
    }

    // check user exist or not
    $u_exist = select(
        "SELECT * FROM `user_cred` WHERE `email` = ? OR `phonenum`=? LIMIT 1",
        [$data['email'], $data['phonenum']],
        "ss"
    );

    if (mysqli_num_rows($u_exist) != 0) {
        $u_exist_fetch = mysqli_fetch_assoc($u_exist);
        echo ($u_exist_fetch['email'] == $data['email']) ? 'email_already' : 'phone_already';
        exit;
    }

    // upload user image
    // Check if file was uploaded
    if (!isset($_FILES['profile']) || $_FILES['profile']['error'] !== UPLOAD_ERR_OK) {
        error_log("Registration: File upload error - " . ($_FILES['profile']['error'] ?? 'no file'));
        echo 'upd_failed';
        exit;
    }
    
    $img = uploadUserImage($_FILES['profile']);

    if ($img == 'inv_img') {
        echo 'inv_img';
        exit;
    } elseif ($img == 'upd_failed') {
        error_log("Registration: Image upload failed for user: " . $data['email']);
        echo 'upd_failed';
        exit;
    }

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

    // Fix: is_verified is integer (i), not string (s) - should be 'sssssssssi' not 'ssssssssss'
    $result = insert($query, $values, 'sssssssssi');
    if ($result && $result > 0) {
        error_log("Registration successful for user: " . $data['email']);
        echo 1;
    } else {
        // Log error for debugging
        error_log("Registration failed - Insert query returned: " . var_export($result, true));
        error_log("Values: " . print_r($values, true));
        if (isset($GLOBALS['con'])) {
            $con = $GLOBALS['con'];
            error_log("MySQL Error: " . mysqli_error($con));
            error_log("MySQL Errno: " . mysqli_errno($con));
        }
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
