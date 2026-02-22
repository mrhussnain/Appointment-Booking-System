<?php
session_start();
require_once __DIR__ . '/includes/db_helper.php';

// Database connection
$db = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($db->connect_error) {
    die("Connection failed: " . $db->connect_error);
}

// Handle password change
if (isset($_POST['change_password']) && isset($_SESSION['admin_logged_in'])) {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    // Get current admin password
    $query = "SELECT password FROM admin_users WHERE username = 'admin'";
    $result = $db->query($query);
    $admin = $result->fetch_assoc();

    if (!password_verify($current_password, $admin['password'])) {
        $password_error = "Current password is incorrect";
    } elseif ($new_password !== $confirm_password) {
        $password_error = "New passwords do not match";
    } elseif (strlen($new_password) < 6) {
        $password_error = "New password must be at least 6 characters long";
    } else {
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        $update_query = "UPDATE admin_users SET password = ? WHERE username = 'admin'";
        $stmt = $db->prepare($update_query);
        $stmt->bind_param("s", $hashed_password);
        
        if ($stmt->execute()) {
            $password_success = "Password updated successfully";
        } else {
            $password_error = "Failed to update password";
        }
    }
}

// Handle appointment deletion
if (isset($_POST['delete_appointment'])) {
    $appointment_id = (int)$_POST['appointment_id'];
    $delete_query = "DELETE FROM appointments WHERE id = ?";
    $stmt = $db->prepare($delete_query);
    $stmt->bind_param("i", $appointment_id);
    $stmt->execute();
    // Redirect to refresh the page
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

// Handle user deletion
if (isset($_POST['delete_user'])) {
    $user_id = (int)$_POST['user_id'];
    
    try {
        // Start transaction
        $db->begin_transaction();
        
        // First delete all appointments for this user
        $delete_appointments = "DELETE FROM appointments WHERE user_id = ?";
        $stmt = $db->prepare($delete_appointments);
        if (!$stmt) {
            throw new Exception("Prepare failed: " . $db->error);
        }
        $stmt->bind_param("i", $user_id);
        if (!$stmt->execute()) {
            throw new Exception("Failed to delete appointments: " . $stmt->error);
        }
        $stmt->close();
        
        // Delete admin logs for this user
        $delete_logs = "DELETE FROM admin_logs WHERE user_id = ?";
        $stmt = $db->prepare($delete_logs);
        if (!$stmt) {
            throw new Exception("Prepare failed: " . $db->error);
        }
        $stmt->bind_param("i", $user_id);
        if (!$stmt->execute()) {
            throw new Exception("Failed to delete admin logs: " . $stmt->error);
        }
        $stmt->close();
        
        // Then delete the user
        $delete_user = "DELETE FROM users WHERE id = ?";
        $stmt = $db->prepare($delete_user);
        if (!$stmt) {
            throw new Exception("Prepare failed: " . $db->error);
        }
        $stmt->bind_param("i", $user_id);
        if (!$stmt->execute()) {
            throw new Exception("Failed to delete user: " . $stmt->error);
        }
        $stmt->close();
        
        // Commit transaction
        $db->commit();
        
        // Redirect to refresh the page
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
        
    } catch (Exception $e) {
        // Rollback transaction on error
        $db->rollback();
        
        // Log the error
        error_log("Error deleting user: " . $e->getMessage());
        
        // Show error message to user
        echo "<script>alert('Error deleting user: " . htmlspecialchars($e->getMessage()) . "');</script>";
        echo "<script>window.location.href = '" . $_SERVER['PHP_SELF'] . "';</script>";
        exit;
    }
}

// Create admin_users table and default admin user if they don't exist
$db->query("CREATE TABLE IF NOT EXISTS admin_users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL,
    password VARCHAR(255) NOT NULL
)");

// Check if admin user exists
$result = $db->query("SELECT id FROM admin_users WHERE username = 'admin'");
if ($result->num_rows === 0) {
    // Create default admin user (username: admin, password: admin123)
    $default_password = password_hash('admin123', PASSWORD_DEFAULT);
    $db->query("INSERT INTO admin_users (username, password) VALUES ('admin', '$default_password')");
}

// Login handling
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $username = $db->real_escape_string($_POST['username']);
    $password = $_POST['password'];

    $query = "SELECT * FROM admin_users WHERE username = ?";
    $stmt = $db->prepare($query);
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = get_stmt_result($stmt);
    $user = $result->fetch_assoc();

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['admin_logged_in'] = true;
        header('Location: admin.php');
        exit;
    } else {
        $error = "Invalid credentials";
    }
}

// Handle appointment status updates
if (isset($_SESSION['admin_logged_in']) && isset($_POST['update_status'])) {
    $id = (int)$_POST['appointment_id'];
    $status = $db->real_escape_string($_POST['status']);
    
    $query = "UPDATE appointments SET status = ? WHERE id = ?";
    $stmt = $db->prepare($query);
    $stmt->bind_param("si", $status, $id);
    $stmt->execute();
    
    header('Location: admin.php');
    exit;
}

// Logout handling
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: admin.php');
    exit;
}

// Handle time slot management
if (isset($_SESSION['admin_logged_in'])) {
    if (isset($_POST['add_slot'])) {
        $slot_time = $db->real_escape_string($_POST['slot_time']);
        // Convert to 12-hour format for storage
        $time_24hr = date("H:i:s", strtotime($slot_time));
        $query = "INSERT INTO time_slots (slot_time) VALUES (?)";
        $stmt = $db->prepare($query);
        $stmt->bind_param("s", $time_24hr);
        $stmt->execute();
    }

    if (isset($_POST['toggle_slot'])) {
        $slot_id = (int)$_POST['slot_id'];
        $is_active = (int)$_POST['is_active'];
        $query = "UPDATE time_slots SET is_active = ? WHERE id = ?";
        $stmt = $db->prepare($query);
        $stmt->bind_param("ii", $is_active, $slot_id);
        $stmt->execute();
    }

    if (isset($_POST['delete_slot'])) {
        $slot_id = (int)$_POST['slot_id'];
        $query = "DELETE FROM time_slots WHERE id = ?";
        $stmt = $db->prepare($query);
        $stmt->bind_param("i", $slot_id);
        $stmt->execute();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Appointment System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <?php require_once 'includes/theme.php'; echo $themeStyles; ?>
    <style>
        /* Additional styles specific to admin.php */
        .admin-header {
            background: var(--gradient-primary);
            color: white;
            padding: 2rem 0;
            margin-bottom: 2rem;
            border-radius: 0 0 15px 15px;
        }

        .stats-card {
            transition: transform 0.3s ease;
            background: #ffffff;
        }

        .stats-card:hover {
            transform: translateY(-5px);
        }

        .stats-icon {
            font-size: 2rem;
            background: var(--gradient-primary);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .card-header[role="button"] {
            cursor: pointer;
        }

        .card-header[role="button"] .fa-chevron-down {
            transition: transform 0.3s ease;
        }

        .card-header[role="button"][aria-expanded="true"] .fa-chevron-down {
            transform: rotate(180deg);
        }

        .table th {
            color: #667db6;
            font-weight: 600;
            border-bottom-width: 2px;
            border-bottom-color: rgba(102, 125, 182, 0.1);
        }

        .table td {
            vertical-align: middle;
            border-bottom-color: rgba(102, 125, 182, 0.05);
        }

        .btn-light {
            background: var(--gradient-light);
            border: none;
        }

        .btn-light:hover {
            background: linear-gradient(135deg, #e9ecef 0%, #dee2e6 100%);
        }

        .popover {
            max-width: 300px;
            background: #ffffff;
            border: none;
            box-shadow: 0 0 20px rgba(0,0,0,.1);
            border-radius: 10px;
        }

        .popover-body {
            padding: 1rem;
            color: #495057;
        }

        .table-responsive {
            overflow-x: auto;
            border-radius: 10px;
        }

        .verification-buttons .btn {
            margin: 0.25rem;
            min-width: 100px;
        }

        .modal-body {
            padding: 1.5rem;
        }

        .admin-section {
            margin-bottom: 2rem;
        }

        .time-slot-manager {
            background: #ffffff;
            border-radius: 15px;
            padding: 1.5rem;
            box-shadow: 0 0 20px rgba(0,0,0,.08);
        }

        .time-slot-list {
            max-height: 300px;
            overflow-y: auto;
        }

        .time-slot-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0.5rem;
            border-bottom: 1px solid rgba(102, 125, 182, 0.1);
        }

        .time-slot-item:last-child {
            border-bottom: none;
        }

        @media (max-width: 768px) {
            .table td {
                min-width: 100px;
            }
            .table td:nth-child(6) {  /* Appointments column */
                min-width: 200px;
            }
            .table td:nth-child(7) {  /* Verification column */
                min-width: 140px;
            }
            .verification-buttons {
                display: flex;
                flex-direction: column;
            }
            .verification-buttons .btn {
                margin: 0.25rem 0;
            }
        }

        .btn-sm {
            padding: 0.25rem 0.5rem;
            font-size: 0.875rem;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="admin.php">
                <i class="fas fa-user-shield me-2"></i>
                Admin Dashboard
            </a>
            <?php if (isset($_SESSION['admin_logged_in'])): ?>
            <div class="ms-auto">
                <a href="logout.php" class="btn btn-light">
                    <i class="fas fa-sign-out-alt me-2"></i>Logout
                </a>
            </div>
            <?php endif; ?>
        </div>
    </nav>

    <div class="container py-4">
        <?php if (!isset($_SESSION['admin_logged_in'])): ?>
            <!-- Login Form -->
            <div class="row justify-content-center">
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h4 class="mb-0">Admin Login</h4>
                        </div>
                        <div class="card-body">
                            <?php if (isset($error)): ?>
                                <div class="alert alert-danger"><?php echo $error; ?></div>
                            <?php endif; ?>
                            <form method="POST">
                                <div class="mb-3">
                                    <label class="form-label">Username</label>
                                    <input type="text" name="username" class="form-control" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Password</label>
                                    <input type="password" name="password" class="form-control" required>
                                </div>
                                <button type="submit" name="login" class="btn btn-primary">Login</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <div class="admin-header text-center">
                <h1 class="mb-0">Admin Dashboard</h1>
                <p class="mb-0">Manage appointments and users</p>
            </div>

            <?php if (isset($_SESSION['admin_logged_in'])): ?>
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center" 
                     role="button" 
                     data-bs-toggle="collapse" 
                     data-bs-target="#passwordSection"
                     aria-expanded="false">
                    <h5 class="mb-0">
                        <i class="fas fa-key me-2"></i>Change Admin Password
                    </h5>
                    <i class="fas fa-chevron-down"></i>
                </div>
                <div id="passwordSection" class="collapse">
                    <div class="card-body">
                        <?php if (isset($password_success)): ?>
                            <div class="alert alert-success"><?php echo $password_success; ?></div>
                        <?php endif; ?>
                        <?php if (isset($password_error)): ?>
                            <div class="alert alert-danger"><?php echo $password_error; ?></div>
                        <?php endif; ?>
                        
                        <form method="POST" class="row g-3" onsubmit="return confirmPasswordChange()">
                            <div class="col-md-4">
                                <label class="form-label">Current Password</label>
                                <input type="password" name="current_password" class="form-control" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">New Password</label>
                                <input type="password" name="new_password" class="form-control" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Confirm New Password</label>
                                <input type="password" name="confirm_password" class="form-control" required>
                            </div>
                            <div class="col-12">
                                <button type="submit" name="change_password" class="btn btn-primary">
                                    <i class="fas fa-save me-2"></i>Update Password
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Time Slot Management Card -->
            <div class="card mb-4">
                <div class="card-header" role="button" data-bs-toggle="collapse" data-bs-target="#timeSlotsCollapse">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">
                            <i class="fas fa-clock me-2"></i>Time Slot Management
                        </h5>
                        <i class="fas fa-chevron-down"></i>
                    </div>
                </div>
                <div id="timeSlotsCollapse" class="collapse show">
                    <div class="card-body">
                        <!-- Add New Time Slot -->
                        <form method="POST" class="mb-4">
                            <div class="row align-items-end">
                                <div class="col-md-4">
                                    <label class="form-label">New Time Slot</label>
                                    <input type="time" name="slot_time" class="form-control" required>
                                </div>
                                <div class="col-md-2">
                                    <button type="submit" name="add_slot" class="btn btn-primary w-100">
                                        <i class="fas fa-plus me-2"></i>Add Slot
                                    </button>
                                </div>
                            </div>
                        </form>

                        <!-- Time Slots List -->
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Time Slot</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $slots_query = "SELECT * FROM time_slots ORDER BY slot_time";
                                    $slots_result = $db->query($slots_query);
                                    while ($slot = $slots_result->fetch_assoc()):
                                        // Convert 24hr to 12hr format
                                        $time_12hr = date("h:i A", strtotime($slot['slot_time']));
                                    ?>
                                    <tr>
                                        <td><?php echo $time_12hr; ?></td>
                                        <td>
                                            <?php if ($slot['is_active']): ?>
                                                <span class="badge bg-success">Active</span>
                                            <?php else: ?>
                                                <span class="badge bg-danger">Inactive</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <form method="POST" class="d-inline">
                                                <input type="hidden" name="slot_id" value="<?php echo $slot['id']; ?>">
                                                <input type="hidden" name="is_active" value="<?php echo $slot['is_active'] ? '0' : '1'; ?>">
                                                <button type="submit" name="toggle_slot" class="btn btn-sm <?php echo $slot['is_active'] ? 'btn-warning' : 'btn-success'; ?>">
                                                    <i class="fas <?php echo $slot['is_active'] ? 'fa-ban' : 'fa-check'; ?> me-1"></i>
                                                    <?php echo $slot['is_active'] ? 'Disable' : 'Enable'; ?>
                                                </button>
                                            </form>
                                            <form method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this time slot?');">
                                                <input type="hidden" name="slot_id" value="<?php echo $slot['id']; ?>">
                                                <button type="submit" name="delete_slot" class="btn btn-sm btn-danger">
                                                    <i class="fas fa-trash me-1"></i>Delete
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Appointments Management -->
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center"
                     role="button" 
                     data-bs-toggle="collapse" 
                     data-bs-target="#appointmentsSection"
                     aria-expanded="true">
                    <h5 class="mb-0">
                        <i class="fas fa-calendar-check me-2"></i>Recent Appointments
                    </h5>
                    <i class="fas fa-chevron-down"></i>
                </div>
                <div id="appointmentsSection" class="collapse show">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Date</th>
                                        <th>Time</th>
                                        <th>Status</th>
                                        <th>Notes</th>
                                        <th>Admin Comments</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $query = "SELECT a.*, u.name, u.email, u.phone 
                                             FROM appointments a 
                                             LEFT JOIN users u ON a.user_id = u.id 
                                             ORDER BY a.appointment_date DESC, a.appointment_time DESC";
                                    $result = $db->query($query);
                                    while ($row = $result->fetch_assoc()):
                                    ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($row['name'] ?? 'N/A'); ?></td>
                                        <td><?php echo date('F j, Y', strtotime($row['appointment_date'])); ?></td>
                                        <td><?php echo $row['appointment_time']; ?></td>
                                        <td>
                                            <span class="badge bg-<?php 
                                                echo $row['status'] == 'confirmed' ? 'success' : 
                                                    ($row['status'] == 'pending' ? 'warning' : 'danger'); 
                                            ?>">
                                                <?php echo ucfirst($row['status']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if (!empty($row['notes'])): ?>
                                                <button type="button" class="btn btn-sm btn-info" 
                                                        data-bs-toggle="popover" 
                                                        data-bs-content="<?php echo htmlspecialchars($row['notes']); ?>"
                                                        data-bs-trigger="focus">
                                                    <i class="fas fa-sticky-note me-1"></i>View Notes
                                                </button>
                                            <?php else: ?>
                                                <span class="text-muted">No notes</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if (!empty($row['admin_comments'])): ?>
                                                <button type="button" class="btn btn-sm btn-secondary" 
                                                        data-bs-toggle="popover" 
                                                        data-bs-content="<?php echo htmlspecialchars($row['admin_comments']); ?>"
                                                        data-bs-trigger="focus">
                                                    <i class="fas fa-comment me-1"></i>View Comments
                                                </button>
                                            <?php else: ?>
                                                <span class="text-muted">No comments</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="btn-group">
                                                <button class="btn btn-sm btn-success btn-action" 
                                                        onclick="updateStatus(<?php echo $row['id']; ?>, 'confirmed')">
                                                    <i class="fas fa-check"></i>
                                                </button>
                                                <button class="btn btn-sm btn-warning btn-action" 
                                                        onclick="updateStatus(<?php echo $row['id']; ?>, 'pending')">
                                                    <i class="fas fa-clock"></i>
                                                </button>
                                                <button class="btn btn-sm btn-danger btn-action" 
                                                        onclick="updateStatus(<?php echo $row['id']; ?>, 'cancelled')">
                                                    <i class="fas fa-times"></i>
                                                </button>
                                                <form method="POST" class="d-inline" 
                                                      onsubmit="return confirm('Are you sure you want to delete this appointment?');">
                                                    <input type="hidden" name="appointment_id" value="<?php echo $row['id']; ?>">
                                                    <button type="submit" name="delete_appointment" 
                                                            class="btn btn-sm btn-outline-danger btn-action">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Users Management -->
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center"
                     role="button" 
                     data-bs-toggle="collapse" 
                     data-bs-target="#usersSection"
                     aria-expanded="true">
                    <h5 class="mb-0">
                        <i class="fas fa-users me-2"></i>Registered Users
                    </h5>
                    <i class="fas fa-chevron-down"></i>
                </div>
                <div id="usersSection" class="collapse show">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Username</th>
                                        <th>Name</th>
                                        <th>Email</th>
                                        <th>Phone</th>
                                        <th>Joined</th>
                                        <th>Appointments</th>
                                        <th>Email Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $users_query = "SELECT u.*, 
                                        (SELECT COUNT(*) FROM appointments a WHERE a.user_id = u.id) as total_appointments,
                                        (SELECT COUNT(*) FROM appointments a WHERE a.user_id = u.id AND a.status = 'confirmed') as confirmed_appointments
                                        FROM users u ORDER BY u.created_at DESC";
                                    $users_result = $db->query($users_query);
                                    while ($user = $users_result->fetch_assoc()):
                                    ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($user['username']); ?></td>
                                        <td><?php echo htmlspecialchars($user['name']); ?></td>
                                        <td><?php echo htmlspecialchars($user['email']); ?></td>
                                        <td><?php echo htmlspecialchars($user['phone']); ?></td>
                                        <td><?php echo date('Y-m-d', strtotime($user['created_at'])); ?></td>
                                        <td>
                                            <span class="badge bg-primary">
                                                Total: <?php echo $user['total_appointments']; ?>
                                            </span>
                                            <span class="badge bg-success ms-1">
                                                Confirmed: <?php echo $user['confirmed_appointments']; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if ($user['email_verified']): ?>
                                                <button type="button" 
                                                        class="btn btn-sm btn-success" 
                                                        onclick="toggleVerification(<?php echo $user['id']; ?>, 0)">
                                                    <i class="fas fa-check-circle me-1"></i>Verified
                                                </button>
                                            <?php else: ?>
                                                <div class="d-flex gap-1">
                                                    <button type="button" 
                                                            class="btn btn-sm btn-warning" 
                                                            onclick="toggleVerification(<?php echo $user['id']; ?>, 1)">
                                                        <i class="fas fa-check me-1"></i>Verify Now
                                                    </button>
                                                    <button type="button" 
                                                            class="btn btn-sm btn-info" 
                                                            onclick="resendVerification(<?php echo $user['id']; ?>)">
                                                        <i class="fas fa-envelope me-1"></i>Resend Email
                                                    </button>
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="btn-group">
                                                <button type="button" class="btn btn-sm btn-info" 
                                                        onclick="viewUserAppointments(<?php echo $user['id']; ?>)">
                                                    <i class="fas fa-eye me-1"></i>View Details
                                                </button>
                                                <form method="POST" class="d-inline" 
                                                      onsubmit="return confirmUserDeletion('<?php echo htmlspecialchars($user['name']); ?>');">
                                                    <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                    <button type="submit" name="delete_user" 
                                                            class="btn btn-sm btn-danger">
                                                        <i class="fas fa-trash me-1"></i>Delete
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- User Appointments Modal -->
            <div class="modal fade" id="userAppointmentsModal" tabindex="-1">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">User Appointments</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <div id="userAppointmentsContent"></div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function updateStatus(appointmentId, status) {
            $('#appointmentId').val(appointmentId);
            $('#newStatus').val(status);
            $('#adminComments').val(''); // Clear previous comments
            
            // Set modal title based on status
            const statusText = status.charAt(0).toUpperCase() + status.slice(1);
            $('.modal-title').text(statusText + ' Appointment');
            
            $('#statusUpdateModal').modal('show');
        }

        function viewUserAppointments(userId) {
            $.get('get_user_appointments.php', { user_id: userId }, function(data) {
                $('#userAppointmentsContent').html(data);
                $('#userAppointmentsModal').modal('show');
            });
        }

        function confirmPasswordChange() {
            return confirm('Are you sure you want to change the admin password?');
        }

        function toggleVerification(userId, status) {
            const action = status === 1 ? 'verify' : 'remove verification from';
            const message = `Are you sure you want to ${action} this user?`;
            
            if (confirm(message)) {
                $.ajax({
                    url: 'toggle_verification.php',
                    method: 'POST',
                    data: {
                        user_id: userId,
                        status: status
                    },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            location.reload();
                        } else {
                            alert(response.message || 'An error occurred while updating verification status');
                        }
                    },
                    error: function() {
                        location.reload(); // Reload even on error since the operation might have succeeded
                    }
                });
            }
        }

        function resendVerification(userId) {
            if (confirm('Are you sure you want to resend verification email?')) {
                $.ajax({
                    url: 'resend_verification.php',
                    method: 'POST',
                    data: { user_id: userId },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            alert('Verification email has been sent successfully!');
                        } else {
                            alert(response.message || 'Failed to send verification email');
                        }
                    },
                    error: function(xhr) {
                        try {
                            const response = JSON.parse(xhr.responseText);
                            alert(response.message || 'Failed to send verification email');
                        } catch(e) {
                            alert('An error occurred while sending verification email');
                        }
                    }
                });
            }
        }

        function confirmUserDeletion(userName) {
            return confirm(`Are you sure you want to delete user "${userName}"?\nThis will also delete all their appointments and cannot be undone.`);
        }

        $(document).ready(function(){
            // Initialize popovers
            const popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'))
            const popoverList = popoverTriggerList.map(function (popoverTriggerEl) {
                return new bootstrap.Popover(popoverTriggerEl, {
                    html: true,
                    placement: 'left'
                })
            });

            // Load saved collapse states
            $('.collapse').each(function() {
                const collapseId = $(this).attr('id');
                const isCollapsed = localStorage.getItem(collapseId) === 'collapsed';
                
                if (isCollapsed) {
                    $(this).removeClass('show');
                    const chevron = $(this).siblings('.card-header').find('.fa-chevron-down');
                    chevron.css('transform', 'rotate(0deg)');
                }
            });

            // Handle collapse state and chevron rotation
            $('.collapse').on('show.bs.collapse hide.bs.collapse', function(e) {
                const collapseId = $(this).attr('id');
                const chevron = $(this).siblings('.card-header').find('.fa-chevron-down');
                
                if (e.type === 'show') {
                    chevron.css('transform', 'rotate(180deg)');
                    localStorage.removeItem(collapseId); // Remove collapsed state
                } else {
                    chevron.css('transform', 'rotate(0deg)');
                    localStorage.setItem(collapseId, 'collapsed'); // Save collapsed state
                }
            });

            // Add transition for smooth rotation
            $('.fa-chevron-down').css('transition', 'transform 0.3s');

            // Add this to your existing document.ready function
            $('#statusUpdateForm').on('submit', function(e) {
                e.preventDefault();
                
                const appointmentId = $('#appointmentId').val();
                const status = $('#newStatus').val();
                const comments = $('#adminComments').val();
                
                $.post('update_status.php', {
                    appointment_id: appointmentId,
                    status: status,
                    admin_comments: comments
                }, function(response) {
                    if (response.success) {
                        location.reload();
                    } else {
                        alert('Failed to update status: ' + (response.message || 'Unknown error'));
                    }
                }, 'json')
                .fail(function(jqXHR, textStatus, errorThrown) {
                    alert('Error: ' + textStatus);
                });
                
                $('#statusUpdateModal').modal('hide');
            });
        });
    </script>

    <!-- Add this modal before the closing body tag -->
    <div class="modal fade" id="statusUpdateModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Update Appointment Status</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="statusUpdateForm">
                        <input type="hidden" id="appointmentId">
                        <input type="hidden" id="newStatus">
                        
                        <div class="mb-3">
                            <label class="form-label">Comments for User (Optional)</label>
                            <textarea class="form-control" id="adminComments" rows="3" 
                                placeholder="Add any comments or instructions for the user..."></textarea>
                        </div>
                        
                        <div class="text-end">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary">Update Status</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <p class="text-center">
                Developed by <a href="https://mrhussnainofficial.com" target="_blank">Hussnain Raza</a>
            </p>
        </div>
    </footer>
</body>
</html>
