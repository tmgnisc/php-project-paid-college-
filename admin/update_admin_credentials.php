<?php
/**
 * Admin Credentials Update Script
 * 
 * This script updates the admin username from "anshika" to "admin"
 * 
 * Usage: Access via browser: http://localhost/Hotel/admin/update_admin_credentials.php
 * 
 * IMPORTANT: Delete this file after use for security!
 */

require('inc/db_config.php');
require('inc/essentials.php');

// Security: Only allow if accessed directly (not from another script)
if (basename($_SERVER['PHP_SELF']) !== 'update_admin_credentials.php') {
    die("Direct access only");
}

$old_username = 'anshika';
$new_username = 'admin';
$new_password = ''; // Leave empty to keep current password, or set a new password here

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update Admin Credentials</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            padding: 50px;
            background-color: #f8f9fa;
        }
        .container {
            max-width: 600px;
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body>
    <div class="container">
        <h2 class="mb-4">Update Admin Credentials</h2>
        
        <?php
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm'])) {
            // Get form values
            $form_new_username = trim($_POST['new_username'] ?? '');
            $form_new_password = trim($_POST['new_password'] ?? '');
            
            if (empty($form_new_username)) {
                echo '<div class="alert alert-danger">Error: New username cannot be empty!</div>';
            } else {
                // Check if admin_cred table exists
                $table_check = mysqli_query($con, "SHOW TABLES LIKE 'admin_cred'");
                
                if (mysqli_num_rows($table_check) == 0) {
                    echo '<div class="alert alert-danger">Error: admin_cred table does not exist!</div>';
                } else {
                    // Check if old admin exists
                    $check_query = "SELECT * FROM `admin_cred` WHERE `admin_name` = ?";
                    $check_stmt = mysqli_prepare($con, $check_query);
                    mysqli_stmt_bind_param($check_stmt, 's', $old_username);
                    mysqli_stmt_execute($check_stmt);
                    $check_result = mysqli_stmt_get_result($check_stmt);
                    
                    if (mysqli_num_rows($check_result) == 0) {
                        echo '<div class="alert alert-warning">';
                        echo 'No admin found with username "' . htmlspecialchars($old_username) . '"';
                        echo '<br>Current admins in database:';
                        echo '<ul>';
                        $all_admins = mysqli_query($con, "SELECT `sr_no`, `admin_name` FROM `admin_cred`");
                        while ($admin = mysqli_fetch_assoc($all_admins)) {
                            echo '<li>ID: ' . $admin['sr_no'] . ' - Username: ' . htmlspecialchars($admin['admin_name']) . '</li>';
                        }
                        echo '</ul>';
                        echo '</div>';
                } else {
                    $admin_data = mysqli_fetch_assoc($check_result);
                    
                    // Update username (and password if provided)
                    if (!empty($form_new_password)) {
                        // Update both username and password
                        $update_query = "UPDATE `admin_cred` SET `admin_name` = ?, `admin_pass` = ? WHERE `admin_name` = ?";
                        $update_stmt = mysqli_prepare($con, $update_query);
                        mysqli_stmt_bind_param($update_stmt, 'sss', $form_new_username, $form_new_password, $old_username);
                    } else {
                        // Update only username (keep current password)
                        $update_query = "UPDATE `admin_cred` SET `admin_name` = ? WHERE `admin_name` = ?";
                        $update_stmt = mysqli_prepare($con, $update_query);
                        mysqli_stmt_bind_param($update_stmt, 'ss', $form_new_username, $old_username);
                    }
                    
                    if ($update_stmt && mysqli_stmt_execute($update_stmt)) {
                        $affected = mysqli_stmt_affected_rows($update_stmt);
                        mysqli_stmt_close($update_stmt);
                        
                        if ($affected > 0) {
                            echo '<div class="alert alert-success">';
                            echo '<h5>✓ Success!</h5>';
                            echo '<p>Admin credentials updated successfully:</p>';
                            echo '<ul>';
                            echo '<li><strong>Old Username:</strong> ' . htmlspecialchars($old_username) . '</li>';
                            echo '<li><strong>New Username:</strong> ' . htmlspecialchars($form_new_username) . '</li>';
                            if (!empty($form_new_password)) {
                                echo '<li><strong>Password:</strong> Updated</li>';
                            } else {
                                echo '<li><strong>Password:</strong> Unchanged (kept current password)</li>';
                            }
                            echo '</ul>';
                            echo '<p class="mt-3"><strong>You can now login with:</strong></p>';
                            echo '<p>Username: <code>' . htmlspecialchars($form_new_username) . '</code></p>';
                            if (!empty($form_new_password)) {
                                echo '<p>Password: <code>' . htmlspecialchars($form_new_password) . '</code></p>';
                            } else {
                                echo '<p>Password: <em>(your current password)</em></p>';
                            }
                            echo '<hr>';
                            echo '<p class="text-danger"><strong>⚠ IMPORTANT: Delete this file (update_admin_credentials.php) for security!</strong></p>';
                            echo '</div>';
                        } else {
                            echo '<div class="alert alert-warning">No rows were updated. The username might already be "' . htmlspecialchars($form_new_username) . '".</div>';
                        }
                        if ($update_stmt) {
                            mysqli_stmt_close($update_stmt);
                        }
                    } else {
                        $error = mysqli_error($con);
                        echo '<div class="alert alert-danger">';
                        echo '<h5>Error updating credentials:</h5>';
                        echo '<p>' . htmlspecialchars($error) . '</p>';
                        echo '</div>';
                    }
                }
            }
            }
        } else {
            // Show current admin info and confirmation form
            $check_query = "SELECT * FROM `admin_cred` WHERE `admin_name` = ?";
            $check_stmt = mysqli_prepare($con, $check_query);
            mysqli_stmt_bind_param($check_stmt, 's', $old_username);
            mysqli_stmt_execute($check_stmt);
            $check_result = mysqli_stmt_get_result($check_stmt);
            
            if (mysqli_num_rows($check_result) > 0) {
                $admin_data = mysqli_fetch_assoc($check_result);
                ?>
                <div class="alert alert-info">
                    <h5>Current Admin Information:</h5>
                    <ul>
                        <li><strong>ID:</strong> <?php echo $admin_data['sr_no']; ?></li>
                        <li><strong>Current Username:</strong> <?php echo htmlspecialchars($admin_data['admin_name']); ?></li>
                        <li><strong>Password:</strong> <em>(hidden for security)</em></li>
                    </ul>
                </div>
                
                <div class="alert alert-warning">
                    <h5>⚠ Action to be performed:</h5>
                    <ul>
                        <li>Change username from <strong>"<?php echo htmlspecialchars($old_username); ?>"</strong> to <strong>"<?php echo htmlspecialchars($new_username); ?>"</strong></li>
                        <li>Password will remain unchanged</li>
                    </ul>
                </div>
                
                <form method="POST">
                    <input type="hidden" name="old_username" value="<?php echo htmlspecialchars($old_username); ?>">
                    <div class="mb-3">
                        <label class="form-label">New Username:</label>
                        <input type="text" class="form-control" name="new_username" value="<?php echo htmlspecialchars($new_username); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">New Password (optional - leave empty to keep current):</label>
                        <input type="password" class="form-control" name="new_password" placeholder="Leave empty to keep current password">
                        <small class="form-text text-muted">If you want to change the password, enter it here. Otherwise leave empty.</small>
                    </div>
                    <div class="mb-3">
                        <button type="submit" name="confirm" class="btn btn-primary">Confirm Update</button>
                        <a href="index.php" class="btn btn-secondary">Cancel</a>
                    </div>
                </form>
                <?php
            } else {
                echo '<div class="alert alert-warning">';
                echo 'No admin found with username "' . htmlspecialchars($old_username) . '"';
                echo '<br><br>Current admins in database:';
                echo '<ul>';
                $all_admins = mysqli_query($con, "SELECT `sr_no`, `admin_name` FROM `admin_cred`");
                $found = false;
                while ($admin = mysqli_fetch_assoc($all_admins)) {
                    $found = true;
                    echo '<li>ID: ' . $admin['sr_no'] . ' - Username: ' . htmlspecialchars($admin['admin_name']) . '</li>';
                }
                if (!$found) {
                    echo '<li>No admins found in database</li>';
                }
                echo '</ul>';
                echo '</div>';
            }
        }
        ?>
        
        <hr class="my-4">
        <div class="text-muted small">
            <p><strong>Note:</strong> After updating, you can login at <a href="index.php">Admin Login</a> with the new username.</p>
            <p class="text-danger"><strong>Security:</strong> Please delete this file after use!</p>
        </div>
    </div>
</body>
</html>

