<?php
require('inc/links.php');
require('inc/header.php');

// Ensure user is logged in
if (!isset($_SESSION['login']) || $_SESSION['login'] !== true) {
    redirect('index.php');
}

$user_id = $_SESSION['uId'];

// Fetch current user data
$user = null;
$stmt = mysqli_prepare($con, "SELECT name, email, address, phonenum, pincode, dob, profile FROM user_cred WHERE id = ? LIMIT 1");
if ($stmt) {
    mysqli_stmt_bind_param($stmt, 'i', $user_id);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    if ($res && mysqli_num_rows($res) > 0) {
        $user = mysqli_fetch_assoc($res);
    }
    mysqli_stmt_close($stmt);
}

// Fallback if something went wrong
if (!$user) {
    $user = [
        'name' => $_SESSION['uname'],
        'email' => '',
        'address' => '',
        'phonenum' => $_SESSION['uPhone'] ?? '',
        'pincode' => '',
        'dob' => '',
        'profile' => $_SESSION['uPic'] ?? '',
    ];
}

$profile_img = USERS_IMG_PATH . ($user['profile'] ?: 'IMG_00000.jpeg');
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile</title>
    <style>
        .profile-avatar {
            width: 110px;
            height: 110px;
            object-fit: cover;
            border-radius: 50%;
            border: 2px solid #ddd;
        }
    </style>
</head>
<body class="bg-light">
<div class="container py-5">
    <div class="row g-4">
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center">
                    <img id="profileAvatar" src="<?php echo htmlspecialchars($profile_img); ?>" alt="Profile" class="profile-avatar mb-3">
                    <h5 id="profileName" class="mb-1"><?php echo htmlspecialchars($user['name']); ?></h5>
                    <p class="text-muted mb-0"><?php echo htmlspecialchars($user['email']); ?></p>
                    <p class="text-muted small mb-0"><?php echo htmlspecialchars($user['phonenum']); ?></p>
                </div>
            </div>

            <div class="card border-0 shadow-sm mt-3">
                <div class="card-body">
                    <h6 class="fw-bold mb-3">Change Profile Picture</h6>
                    <form id="imageForm">
                        <div class="mb-3">
                            <input type="file" name="profile_pic" accept=".jpg,.jpeg,.png,.webp" class="form-control shadow-none" required>
                        </div>
                        <button class="btn btn-dark w-100 shadow-none" type="submit">Update Picture</button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <div id="profileAlert"></div>

            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body">
                    <h5 class="fw-bold mb-3">Profile Details</h5>
                    <form id="profileForm">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Name</label>
                                <input type="text" name="name" value="<?php echo htmlspecialchars($user['name']); ?>" class="form-control shadow-none" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Phone</label>
                                <input type="text" name="phone" value="<?php echo htmlspecialchars($user['phonenum']); ?>" class="form-control shadow-none" required>
                            </div>
                            <div class="col-md-12">
                                <label class="form-label">Address</label>
                                <textarea name="address" rows="2" class="form-control shadow-none" required><?php echo htmlspecialchars($user['address']); ?></textarea>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Pincode</label>
                                <input type="text" name="pincode" value="<?php echo htmlspecialchars($user['pincode']); ?>" class="form-control shadow-none" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Date of Birth</label>
                                <input type="date" name="dob" value="<?php echo htmlspecialchars($user['dob']); ?>" class="form-control shadow-none" required>
                            </div>
                        </div>
                        <div class="mt-3 text-end">
                            <button type="submit" class="btn btn-dark shadow-none">Save Changes</button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <h5 class="fw-bold mb-3">Change Password</h5>
                    <form id="passwordForm">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label">Current Password</label>
                                <input type="password" name="current_pass" class="form-control shadow-none" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">New Password</label>
                                <input type="password" name="new_pass" class="form-control shadow-none" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Confirm Password</label>
                                <input type="password" name="confirm_pass" class="form-control shadow-none" required>
                            </div>
                        </div>
                        <div class="mt-3 text-end">
                            <button type="submit" class="btn btn-outline-dark shadow-none">Update Password</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require('inc/footer.php'); ?>

<script>
const alertBox = document.getElementById('profileAlert');
function showAlert(message, type = 'success') {
  const cls = type === 'success' ? 'alert-success' : 'alert-danger';
  alertBox.innerHTML = `<div class="alert ${cls} alert-dismissible fade show" role="alert">
    ${message}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
  </div>`;
}

document.getElementById('profileForm').addEventListener('submit', async (e) => {
  e.preventDefault();
  const fd = new FormData(e.target);
  fd.append('action', 'update_profile');
  const res = await fetch('ajax/profile.php', { method: 'POST', body: fd });
  const data = await res.json();
  if (data.status === 'success') {
    document.getElementById('profileName').innerText = fd.get('name');
    showAlert('Profile updated successfully.');
  } else {
    showAlert(data.message || 'Failed to update profile', 'error');
  }
});

document.getElementById('passwordForm').addEventListener('submit', async (e) => {
  e.preventDefault();
  const fd = new FormData(e.target);
  fd.append('action', 'update_password');
  const res = await fetch('ajax/profile.php', { method: 'POST', body: fd });
  const data = await res.json();
  if (data.status === 'success') {
    e.target.reset();
    showAlert('Password updated successfully.');
  } else {
    showAlert(data.message || 'Failed to update password', 'error');
  }
});

document.getElementById('imageForm').addEventListener('submit', async (e) => {
  e.preventDefault();
  const fd = new FormData(e.target);
  fd.append('action', 'update_image');
  const res = await fetch('ajax/profile.php', { method: 'POST', body: fd });
  const data = await res.json();
  if (data.status === 'success') {
    if (data.image_url) {
      document.getElementById('profileAvatar').src = data.image_url + '?t=' + Date.now();
    }
    showAlert('Profile picture updated.');
    e.target.reset();
  } else {
    showAlert(data.message || 'Failed to update picture', 'error');
  }
});
</script>
</body>
</html>


