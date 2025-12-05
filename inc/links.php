<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" crossorigin="anonymous">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Merienda:wght@300..900&family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">

<link rel="stylesheet" href="css/common.css">

<?php
// Start session only if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
 
require_once('admin/inc/db_config.php');
require_once('admin/inc/essentials.php');

$contact_q = "SELECT * FROM `contact_details` WHERE `sr_no`=?";
$setting_q = "SELECT * FROM `settings` WHERE `sr_no`=?";
$values = [1];
$contact_r = mysqli_fetch_assoc(select($contact_q, $values, 'i'));
$settings_r = mysqli_fetch_assoc(select($setting_q, $values, 'i'));

if($settings_r['shutdown']){
    echo<<<alertbar
    <div class='bg-danger text-center p-2 fw-bold'>
    <i class="bi bi-exclamation-triangle-fill"></i>
    Bookings are temporarily closed!
    </div>
    alertbar;
}

?>