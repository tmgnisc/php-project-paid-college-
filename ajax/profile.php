<?php
require('../admin/inc/db_config.php');
require('../admin/inc/essentials.php');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

if (!isset($_SESSION['login']) || $_SESSION['login'] !== true) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

$user_id = intval($_SESSION['uId']);

$action = $_POST['action'] ?? '';

if ($action === 'update_profile') {
    $name = trim($_POST['name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $pincode = trim($_POST['pincode'] ?? '');
    $dob = trim($_POST['dob'] ?? '');

    if ($name === '' || $phone === '' || $address === '' || $pincode === '' || $dob === '') {
        echo json_encode(['status' => 'error', 'message' => 'All fields are required']);
        exit;
    }

    $stmt = mysqli_prepare($con, "UPDATE user_cred SET name = ?, phonenum = ?, address = ?, pincode = ?, dob = ? WHERE id = ?");
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, 'sssssi', $name, $phone, $address, $pincode, $dob, $user_id);
        $ok = mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        if ($ok) {
            $_SESSION['uname'] = $name;
            $_SESSION['uPhone'] = $phone;
            echo json_encode(['status' => 'success']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Database error']);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Database error']);
    }
    exit;
}

if ($action === 'update_password') {
    $current = $_POST['current_pass'] ?? '';
    $new = $_POST['new_pass'] ?? '';
    $confirm = $_POST['confirm_pass'] ?? '';

    if ($current === '' || $new === '' || $confirm === '') {
        echo json_encode(['status' => 'error', 'message' => 'All fields are required']);
        exit;
    }
    if ($new !== $confirm) {
        echo json_encode(['status' => 'error', 'message' => 'Passwords do not match']);
        exit;
    }

    $stmt = mysqli_prepare($con, "SELECT password FROM user_cred WHERE id = ? LIMIT 1");
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, 'i', $user_id);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        $row = $res ? mysqli_fetch_assoc($res) : null;
        mysqli_stmt_close($stmt);

        if (!$row || !password_verify($current, $row['password'])) {
            echo json_encode(['status' => 'error', 'message' => 'Current password is incorrect']);
            exit;
        }

        $hash = password_hash($new, PASSWORD_BCRYPT);
        $stmt2 = mysqli_prepare($con, "UPDATE user_cred SET password = ? WHERE id = ?");
        if ($stmt2) {
            mysqli_stmt_bind_param($stmt2, 'si', $hash, $user_id);
            $ok = mysqli_stmt_execute($stmt2);
            mysqli_stmt_close($stmt2);
            if ($ok) {
                echo json_encode(['status' => 'success']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Database error']);
            }
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Database error']);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Database error']);
    }
    exit;
}

if ($action === 'update_image') {
    if (!isset($_FILES['profile_pic'])) {
        echo json_encode(['status' => 'error', 'message' => 'No image uploaded']);
        exit;
    }

    // Get current image
    $current_img = '';
    $stmt = mysqli_prepare($con, "SELECT profile FROM user_cred WHERE id = ? LIMIT 1");
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, 'i', $user_id);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        if ($res && mysqli_num_rows($res) > 0) {
            $row = mysqli_fetch_assoc($res);
            $current_img = $row['profile'];
        }
        mysqli_stmt_close($stmt);
    }

    $new_img = uploadUserImage($_FILES['profile_pic']);
    if ($new_img === 'inv_img') {
        echo json_encode(['status' => 'error', 'message' => 'Invalid image type']);
        exit;
    } elseif ($new_img === 'upd_failed') {
        echo json_encode(['status' => 'error', 'message' => 'Failed to upload image']);
        exit;
    }

    $stmt2 = mysqli_prepare($con, "UPDATE user_cred SET profile = ? WHERE id = ?");
    if ($stmt2) {
        mysqli_stmt_bind_param($stmt2, 'si', $new_img, $user_id);
        $ok = mysqli_stmt_execute($stmt2);
        mysqli_stmt_close($stmt2);
        if ($ok) {
            if (!empty($current_img)) {
                deleteImage($current_img, USERS_FOLDER);
            }
            $_SESSION['uPic'] = $new_img;
            echo json_encode(['status' => 'success', 'image_url' => USERS_IMG_PATH . $new_img]);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Database error']);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Database error']);
    }
    exit;
}

echo json_encode(['status' => 'error', 'message' => 'Invalid action']);


