<?php
// Error reporting
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

try {
    require('inc/essentials.php');
    require('inc/db_config.php'); // ensure $con is available for queries
    adminLogin();
} catch (Exception $e) {
    die("Error loading essentials: " . $e->getMessage());
} catch (Error $e) {
    die("Fatal error: " . $e->getMessage());
}

// Initialize statistics with default values
$total_bookings = 0;
$total_revenue = 0;
$pending_bookings = 0;
$confirmed_bookings = 0;
$paid_bookings = 0;
$failed_payments = 0;
$today_revenue = 0;
$monthly_revenue = 0;
$this_month_bookings = 0;
$average_booking_value = 0;

// Get statistics - safely execute queries
if (isset($con) && $con) {
    // Total bookings
    $query = "SELECT COUNT(*) as total FROM `bookings`";
    $res = mysqli_query($con, $query);
    if ($res !== false) {
        $row = mysqli_fetch_assoc($res);
        $total_bookings = intval($row['total'] ?? 0);
    }

    // Total revenue (paid bookings only)
    $query = "SELECT COALESCE(SUM(total_pay), 0) as total FROM `bookings` WHERE `payment_status`='paid'";
    $res = mysqli_query($con, $query);
    if ($res !== false) {
        $row = mysqli_fetch_assoc($res);
        $total_revenue = floatval($row['total'] ?? 0);
    }

    // Pending bookings
    $query = "SELECT COUNT(*) as total FROM `bookings` WHERE `booking_status`='pending'";
    $res = mysqli_query($con, $query);
    if ($res !== false) {
        $row = mysqli_fetch_assoc($res);
        $pending_bookings = intval($row['total'] ?? 0);
    }

    // Confirmed bookings
    $query = "SELECT COUNT(*) as total FROM `bookings` WHERE `booking_status`='booked'";
    $res = mysqli_query($con, $query);
    if ($res !== false) {
        $row = mysqli_fetch_assoc($res);
        $confirmed_bookings = intval($row['total'] ?? 0);
    }

    // Paid bookings
    $query = "SELECT COUNT(*) as total FROM `bookings` WHERE `payment_status`='paid'";
    $res = mysqli_query($con, $query);
    if ($res !== false) {
        $row = mysqli_fetch_assoc($res);
        $paid_bookings = intval($row['total'] ?? 0);
    }

    // Failed payments
    $query = "SELECT COUNT(*) as total FROM `bookings` WHERE `payment_status`='failed'";
    $res = mysqli_query($con, $query);
    if ($res !== false) {
        $row = mysqli_fetch_assoc($res);
        $failed_payments = intval($row['total'] ?? 0);
    }

    // Today's revenue
    $query = "SELECT COALESCE(SUM(total_pay), 0) as total FROM `bookings` WHERE `payment_status`='paid' AND DATE(booking_date) = CURDATE()";
    $res = mysqli_query($con, $query);
    if ($res !== false) {
        $row = mysqli_fetch_assoc($res);
        $today_revenue = floatval($row['total'] ?? 0);
    }

    // Monthly revenue
    $query = "SELECT COALESCE(SUM(total_pay), 0) as total FROM `bookings` WHERE `payment_status`='paid' AND MONTH(booking_date) = MONTH(CURDATE()) AND YEAR(booking_date) = YEAR(CURDATE())";
    $res = mysqli_query($con, $query);
    if ($res !== false) {
        $row = mysqli_fetch_assoc($res);
        $monthly_revenue = floatval($row['total'] ?? 0);
    }

    // This month bookings
    $query = "SELECT COUNT(*) as total FROM `bookings` WHERE MONTH(booking_date) = MONTH(CURDATE()) AND YEAR(booking_date) = YEAR(CURDATE())";
    $res = mysqli_query($con, $query);
    if ($res !== false) {
        $row = mysqli_fetch_assoc($res);
        $this_month_bookings = intval($row['total'] ?? 0);
    }

    // Average booking value
    $query = "SELECT COALESCE(AVG(total_pay), 0) as avg_value FROM `bookings` WHERE `payment_status`='paid'";
    $res = mysqli_query($con, $query);
    if ($res !== false) {
        $row = mysqli_fetch_assoc($res);
        $average_booking_value = floatval($row['avg_value'] ?? 0);
    }
}

// Get filter parameters for booking list
$filter_status = $_GET['status'] ?? 'all';
$filter_payment = $_GET['payment'] ?? 'all';
$search_term = $_GET['search'] ?? '';

// Build query with filters
$where_conditions = [];
$params = [];
$param_types = '';

if ($filter_status != 'all') {
    $where_conditions[] = "b.booking_status = ?";
    $params[] = $filter_status;
    $param_types .= 's';
}

if ($filter_payment != 'all') {
    $where_conditions[] = "b.payment_status = ?";
    $params[] = $filter_payment;
    $param_types .= 's';
}

if (!empty($search_term)) {
    $where_conditions[] = "(b.order_id LIKE ? OR b.user_name LIKE ? OR b.room_name LIKE ? OR u.email LIKE ?)";
    $search_param = "%$search_term%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $param_types .= 'ssss';
}

$where_clause = !empty($where_conditions) ? "WHERE " . implode(" AND ", $where_conditions) : "";

// Get total count for pagination
$total_count = 0;
$count_query = "SELECT COUNT(*) as total FROM `bookings` b LEFT JOIN `user_cred` u ON b.user_id = u.id $where_clause";
if (!empty($params)) {
    $count_stmt = mysqli_prepare($con, $count_query);
    if ($count_stmt) {
        mysqli_stmt_bind_param($count_stmt, $param_types, ...$params);
        mysqli_stmt_execute($count_stmt);
        $count_res = mysqli_stmt_get_result($count_stmt);
        $row = mysqli_fetch_assoc($count_res);
        $total_count = intval($row['total'] ?? 0);
        mysqli_stmt_close($count_stmt);
    }
} else {
    $count_res = mysqli_query($con, $count_query);
    if ($count_res !== false) {
        $row = mysqli_fetch_assoc($count_res);
        $total_count = intval($row['total'] ?? 0);
    }
}

// Get bookings with filters
$bookings_query = "SELECT b.*, u.email FROM `bookings` b 
                   LEFT JOIN `user_cred` u ON b.user_id = u.id 
                   $where_clause
                   ORDER BY b.booking_date DESC
                   LIMIT 50";

if (!empty($params)) {
    $stmt = mysqli_prepare($con, $bookings_query);
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, $param_types, ...$params);
        mysqli_stmt_execute($stmt);
        $bookings_res = mysqli_stmt_get_result($stmt);
        mysqli_stmt_close($stmt);
    } else {
        $bookings_res = null;
    }
} else {
    $bookings_res = mysqli_query($con, $bookings_query);
}

// Get recent payments (last 10 paid bookings)
$recent_payments_query = "SELECT b.*, u.email FROM `bookings` b 
                          LEFT JOIN `user_cred` u ON b.user_id = u.id 
                          WHERE b.payment_status = 'paid'
                          ORDER BY b.booking_date DESC 
                          LIMIT 10";
$recent_payments_res = mysqli_query($con, $recent_payments_query);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - Dashboard</title>
    <?php require('inc/links.php'); ?>
    <style>
        .stat-card {
            transition: transform 0.2s;
            border-left: 4px solid;
        }
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1) !important;
        }
        .stat-card.revenue { border-left-color: #28a745; }
        .stat-card.bookings { border-left-color: #007bff; }
        .stat-card.pending { border-left-color: #ffc107; }
        .stat-card.confirmed { border-left-color: #17a2b8; }
        .status-badge {
            font-size: 0.85rem;
        }
    </style>
</head>
<body class="bg-light">
    <?php require('inc/header.php'); ?>

    <div class="container-fluid" id="main-content">
        <div class="row">
            <div class="col-lg-10 ms-auto p-4 overflow-hidden">
                <h2 class="mb-4">Dashboard Overview</h2>

                <!-- Statistics Cards -->
                <div class="row mb-4">
                    <div class="col-lg-3 col-md-6 mb-3">
                        <div class="card stat-card revenue border-0 shadow-sm">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="flex-shrink-0">
                                        <i class="bi bi-currency-dollar text-success" style="font-size: 2.5rem;"></i>
                                    </div>
                                    <div class="flex-grow-1 ms-3">
                                        <h6 class="text-muted mb-1">Total Revenue</h6>
                                        <h4 class="mb-0">$<?php echo number_format($total_revenue, 2); ?></h4>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-lg-3 col-md-6 mb-3">
                        <div class="card stat-card bookings border-0 shadow-sm">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="flex-shrink-0">
                                        <i class="bi bi-calendar-check text-primary" style="font-size: 2.5rem;"></i>
                                    </div>
                                    <div class="flex-grow-1 ms-3">
                                        <h6 class="text-muted mb-1">Total Bookings</h6>
                                        <h4 class="mb-0"><?php echo $total_bookings; ?></h4>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-lg-3 col-md-6 mb-3">
                        <div class="card stat-card confirmed border-0 shadow-sm">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="flex-shrink-0">
                                        <i class="bi bi-check-circle text-info" style="font-size: 2.5rem;"></i>
                                    </div>
                                    <div class="flex-grow-1 ms-3">
                                        <h6 class="text-muted mb-1">Confirmed</h6>
                                        <h4 class="mb-0"><?php echo $confirmed_bookings; ?></h4>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-lg-3 col-md-6 mb-3">
                        <div class="card stat-card pending border-0 shadow-sm">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="flex-shrink-0">
                                        <i class="bi bi-hourglass-split text-warning" style="font-size: 2.5rem;"></i>
                                    </div>
                                    <div class="flex-grow-1 ms-3">
                                        <h6 class="text-muted mb-1">Pending</h6>
                                        <h4 class="mb-0"><?php echo $pending_bookings; ?></h4>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Additional Statistics -->
                <div class="row mb-4">
                    <div class="col-lg-3 col-md-6 mb-3">
                        <div class="card border-0 shadow-sm bg-primary text-white">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="flex-shrink-0">
                                        <i class="bi bi-calendar-day" style="font-size: 2rem;"></i>
                                    </div>
                                    <div class="flex-grow-1 ms-3">
                                        <h6 class="mb-1">Today's Revenue</h6>
                                        <h5 class="mb-0">$<?php echo number_format($today_revenue, 2); ?></h5>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-lg-3 col-md-6 mb-3">
                        <div class="card border-0 shadow-sm bg-info text-white">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="flex-shrink-0">
                                        <i class="bi bi-calendar-month" style="font-size: 2rem;"></i>
                                    </div>
                                    <div class="flex-grow-1 ms-3">
                                        <h6 class="mb-1">This Month Revenue</h6>
                                        <h5 class="mb-0">$<?php echo number_format($monthly_revenue, 2); ?></h5>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-3 col-md-6 mb-3">
                        <div class="card border-0 shadow-sm">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="flex-shrink-0">
                                        <i class="bi bi-wallet-fill text-success" style="font-size: 2rem;"></i>
                                    </div>
                                    <div class="flex-grow-1 ms-3">
                                        <h6 class="text-muted mb-1">Paid Bookings</h6>
                                        <h5 class="mb-0"><?php echo $paid_bookings; ?></h5>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-lg-3 col-md-6 mb-3">
                        <div class="card border-0 shadow-sm">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="flex-shrink-0">
                                        <i class="bi bi-graph-up text-primary" style="font-size: 2rem;"></i>
                                    </div>
                                    <div class="flex-grow-1 ms-3">
                                        <h6 class="text-muted mb-1">Avg Booking Value</h6>
                                        <h5 class="mb-0">$<?php echo number_format($average_booking_value, 2); ?></h5>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recent Payments Section -->
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-dark text-white">
                        <h5 class="mb-0"><i class="bi bi-credit-card"></i> Recent Payments</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Order ID</th>
                                        <th>Guest</th>
                                        <th>Room</th>
                                        <th>Amount</th>
                                        <th>Date</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    if ($recent_payments_res && mysqli_num_rows($recent_payments_res) > 0) {
                                        while ($payment = mysqli_fetch_assoc($recent_payments_res)) {
                                            echo "<tr>";
                                            echo "<td><small class='text-muted'>" . htmlspecialchars($payment['order_id']) . "</small></td>";
                                            echo "<td>" . htmlspecialchars($payment['user_name']) . "</td>";
                                            echo "<td>" . htmlspecialchars($payment['room_name']) . "</td>";
                                            echo "<td><strong class='text-success'>$" . number_format($payment['total_pay'], 2) . "</strong></td>";
                                            echo "<td><small>" . date('M d, Y H:i', strtotime($payment['booking_date'])) . "</small></td>";
                                            echo "</tr>";
                                        }
                                    } else {
                                        echo "<tr><td colspan='5' class='text-center py-3'>No recent payments found</td></tr>";
                                    }
                                    ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Bookings List with Filters -->
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-dark text-white">
                        <h5 class="mb-0"><i class="bi bi-calendar-check"></i> All Bookings</h5>
                    </div>
                    <div class="card-body">
                        <!-- Filters -->
                        <form method="GET" class="row g-3 mb-3">
                            <div class="col-md-3">
                                <label class="form-label">Booking Status</label>
                                <select name="status" class="form-select shadow-none">
                                    <option value="all" <?php echo $filter_status == 'all' ? 'selected' : ''; ?>>All Status</option>
                                    <option value="pending" <?php echo $filter_status == 'pending' ? 'selected' : ''; ?>>Pending</option>
                                    <option value="booked" <?php echo $filter_status == 'booked' ? 'selected' : ''; ?>>Confirmed</option>
                                    <option value="cancelled" <?php echo $filter_status == 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Payment Status</label>
                                <select name="payment" class="form-select shadow-none">
                                    <option value="all" <?php echo $filter_payment == 'all' ? 'selected' : ''; ?>>All Payments</option>
                                    <option value="paid" <?php echo $filter_payment == 'paid' ? 'selected' : ''; ?>>Paid</option>
                                    <option value="pending" <?php echo $filter_payment == 'pending' ? 'selected' : ''; ?>>Pending</option>
                                    <option value="failed" <?php echo $filter_payment == 'failed' ? 'selected' : ''; ?>>Failed</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Search</label>
                                <input type="text" name="search" class="form-control shadow-none" placeholder="Order ID, Name, Room, Email..." value="<?php echo htmlspecialchars($search_term); ?>">
                            </div>
                            <div class="col-md-2 d-flex align-items-end">
                                <button type="submit" class="btn btn-dark shadow-none w-100">Filter</button>
                            </div>
                        </form>

                        <!-- Bookings Table -->
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th>Order ID</th>
                                        <th>Guest</th>
                                        <th>Room</th>
                                        <th>Check-in</th>
                                        <th>Check-out</th>
                                        <th>Amount</th>
                                        <th>Booking Status</th>
                                        <th>Payment Status</th>
                                        <th>Date</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    if ($bookings_res && mysqli_num_rows($bookings_res) > 0) {
                                        while ($booking = mysqli_fetch_assoc($bookings_res)) {
                                            $status_badge = '';
                                            $payment_badge = '';
                                            
                                            if ($booking['booking_status'] == 'booked') {
                                                $status_badge = '<span class="badge bg-success status-badge">Confirmed</span>';
                                            } elseif ($booking['booking_status'] == 'pending') {
                                                $status_badge = '<span class="badge bg-warning status-badge">Pending</span>';
                                            } else {
                                                $status_badge = '<span class="badge bg-danger status-badge">Cancelled</span>';
                                            }
                                            
                                            if ($booking['payment_status'] == 'paid') {
                                                $payment_badge = '<span class="badge bg-success status-badge">Paid</span>';
                                            } elseif ($booking['payment_status'] == 'pending') {
                                                $payment_badge = '<span class="badge bg-warning status-badge">Pending</span>';
                                            } else {
                                                $payment_badge = '<span class="badge bg-danger status-badge">Failed</span>';
                                            }
                                            
                                            echo "<tr>";
                                            echo "<td><small class='text-muted'>" . htmlspecialchars($booking['order_id']) . "</small></td>";
                                            echo "<td>";
                                            echo "<div><strong>" . htmlspecialchars($booking['user_name']) . "</strong></div>";
                                            echo "<small class='text-muted'>" . htmlspecialchars($booking['email'] ?? 'N/A') . "</small><br>";
                                            echo "<small class='text-muted'>" . htmlspecialchars($booking['phonenum']) . "</small>";
                                            echo "</td>";
                                            echo "<td>" . htmlspecialchars($booking['room_name']) . "</td>";
                                            echo "<td>" . date('M d, Y', strtotime($booking['check_in'])) . "</td>";
                                            echo "<td>" . date('M d, Y', strtotime($booking['check_out'])) . "</td>";
                                            echo "<td><strong>$" . number_format($booking['total_pay'], 2) . "</strong></td>";
                                            echo "<td>" . $status_badge . "</td>";
                                            echo "<td>" . $payment_badge . "</td>";
                                            echo "<td><small>" . date('M d, Y H:i', strtotime($booking['booking_date'])) . "</small></td>";
                                            echo "<td>";
                                            echo "<button class='btn btn-sm btn-outline-primary' onclick='viewBooking(" . $booking['id'] . ")' title='View Details'>";
                                            echo "<i class='bi bi-eye'></i>";
                                            echo "</button>";
                                            echo "</td>";
                                            echo "</tr>";
                                        }
                                    } else {
                                        echo "<tr><td colspan='10' class='text-center py-4'>No bookings found</td></tr>";
                                    }
                                    ?>
                                </tbody>
                            </table>
                        </div>
                        <div class="mt-3">
                            <small class="text-muted">Showing up to 50 bookings. Total: <?php echo $total_count; ?></small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Booking Details Modal -->
    <div class="modal fade" id="bookingModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Booking Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="bookingDetails">
                    <div class="text-center">
                        <div class="spinner-border" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php require('inc/scripts.php'); ?>
    <script>
        function viewBooking(id) {
            // Fetch booking details via AJAX
            fetch('ajax/get_booking_details.php?id=' + id)
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        const booking = data.booking;
                        let html = `
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <strong>Order ID:</strong><br>
                                    <span class="text-muted">${booking.order_id}</span>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <strong>Booking Date:</strong><br>
                                    <span class="text-muted">${booking.booking_date}</span>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <strong>Guest Name:</strong><br>
                                    <span class="text-muted">${booking.user_name}</span>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <strong>Email:</strong><br>
                                    <span class="text-muted">${booking.email || 'N/A'}</span>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <strong>Phone:</strong><br>
                                    <span class="text-muted">${booking.phonenum}</span>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <strong>Address:</strong><br>
                                    <span class="text-muted">${booking.address}</span>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <strong>Room:</strong><br>
                                    <span class="text-muted">${booking.room_name}</span>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <strong>Check-in:</strong><br>
                                    <span class="text-muted">${booking.check_in}</span>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <strong>Check-out:</strong><br>
                                    <span class="text-muted">${booking.check_out}</span>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <strong>Total Amount:</strong><br>
                                    <span class="text-muted">$${parseFloat(booking.total_pay).toFixed(2)}</span>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <strong>Booking Status:</strong><br>
                                    <span class="badge ${booking.booking_status === 'booked' ? 'bg-success' : booking.booking_status === 'pending' ? 'bg-warning' : 'bg-danger'}">${booking.booking_status}</span>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <strong>Payment Status:</strong><br>
                                    <span class="badge ${booking.payment_status === 'paid' ? 'bg-success' : booking.payment_status === 'pending' ? 'bg-warning' : 'bg-danger'}">${booking.payment_status}</span>
                                </div>
                            </div>
                        `;
                        document.getElementById('bookingDetails').innerHTML = html;
                        new bootstrap.Modal(document.getElementById('bookingModal')).show();
                    } else {
                        alert('Error loading booking details');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error loading booking details');
                });
        }
    </script>
</body>
</html>
