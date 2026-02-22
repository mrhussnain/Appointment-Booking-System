<?php
session_start();
require_once __DIR__ . '/includes/db_helper.php';
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$db = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($db->connect_error) {
    die("Connection failed: " . $db->connect_error);
}

$user_id = $_SESSION['user_id'];

// Get user details including verification status
$user_query = "SELECT * FROM users WHERE id = ?";
$stmt = $db->prepare($user_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = get_stmt_result($stmt)->fetch_assoc();

if (!$user) {
    session_destroy();
    header('Location: login.php');
    exit;
}

// Check email verification status
if (!$user['email_verified']) {
    header('Location: verify_email.php');
    exit;
}

// Add this after database connection
if (isset($_POST['cancel_appointment'])) {
    $appointment_id = (int)$_POST['appointment_id'];
    
    // Verify the appointment belongs to the current user
    $check_query = "SELECT id FROM appointments WHERE id = ? AND user_id = ? AND status = 'pending'";
    $stmt = $db->prepare($check_query);
    $stmt->bind_param("ii", $appointment_id, $user_id);
    $stmt->execute();
    $result = get_stmt_result($stmt);
    
    if ($result->num_rows > 0) {
        // Update the appointment status to cancelled
        $update_query = "UPDATE appointments SET status = 'cancelled' WHERE id = ?";
        $stmt = $db->prepare($update_query);
        $stmt->bind_param("i", $appointment_id);
        
        if ($stmt->execute()) {
            // Optional: Add success message
            $_SESSION['message'] = "Appointment cancelled successfully.";
        } else {
            $_SESSION['error'] = "Failed to cancel appointment.";
        }
    } else {
        $_SESSION['error'] = "Invalid appointment or already cancelled.";
    }
    
    // Redirect to refresh the page
    header('Location: dashboard.php');
    exit;
}

// Display messages if they exist
if (isset($_SESSION['message'])) {
    echo '<div class="alert alert-success alert-dismissible fade show" role="alert">
            ' . $_SESSION['message'] . '
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
          </div>';
    unset($_SESSION['message']);
}

if (isset($_SESSION['error'])) {
    echo '<div class="alert alert-danger alert-dismissible fade show" role="alert">
            ' . $_SESSION['error'] . '
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
          </div>';
    unset($_SESSION['error']);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Appointments</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <?php require_once 'includes/theme.php'; echo $themeStyles; ?>
    <style>
        /* Additional styles specific to dashboard.php */
        .status-pending { 
            background: linear-gradient(135deg, #ffc107 0%, #ffdb4d 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            font-weight: 500;
        }
        
        .status-confirmed { 
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            font-weight: 500;
        }
        
        .status-cancelled { 
            background: linear-gradient(135deg, #dc3545 0%, #ff4d4d 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            font-weight: 500;
        }

        .dashboard-header {
            background: var(--gradient-primary);
            color: white;
            padding: 2rem 0;
            margin-bottom: 2rem;
            border-radius: 0 0 15px 15px;
        }

        .appointment-card {
            transition: transform 0.3s ease;
        }

        .appointment-card:hover {
            transform: translateY(-5px);
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

        .btn-info {
            background: linear-gradient(135deg, #0dcaf0 0%, #0d6efd 100%);
            border: none;
            color: white;
        }

        .btn-info:hover {
            background: linear-gradient(135deg, #0bacce 0%, #0b5ed7 100%);
            color: white;
        }

        .text-muted {
            color: #6c757d !important;
        }

        .admin-comment {
            background: rgba(102, 125, 182, 0.1);
            padding: 0.5rem 1rem;
            border-radius: 8px;
            margin-top: 0.5rem;
        }

        .popover {
            max-width: 300px;
        }

        .popover-body {
            padding: 10px;
            white-space: pre-wrap;
        }

        .btn-sm {
            padding: 0.25rem 0.5rem;
            font-size: 0.875rem;
        }

        .btn-info, .btn-secondary {
            color: white;
        }

        .btn-info:hover, .btn-secondary:hover {
            color: white;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <i class="fas fa-calendar-check me-2"></i>
                Appointment System
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : ''; ?>" 
                           href="index.php">
                           <i class="fas fa-home me-1"></i> Home
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>" 
                           href="dashboard.php">
                           <i class="fas fa-calendar me-1"></i> My Appointments
                        </a>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-user-circle me-1"></i> 
                            <?php echo htmlspecialchars($_SESSION['username']); ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="profile.php">
                                <i class="fas fa-user-cog me-2"></i> Profile
                            </a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-danger" href="logout.php">
                                <i class="fas fa-sign-out-alt me-2"></i> Logout
                            </a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <?php if (!$user['email_verified']): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <div class="d-flex align-items-center">
                <i class="fas fa-exclamation-circle me-2"></i>
                <div>
                    <strong>Email Verification Required!</strong><br>
                    You must verify your email address before you can make appointments.
                    <button type="button" class="btn btn-link p-0 ms-2" 
                            onclick="resendVerification('<?php echo htmlspecialchars($_SESSION['username']); ?>')">
                        Resend verification email
                    </button>
                </div>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="container py-5">
        <h2 class="mb-4">My Appointments</h2>
        
        <div class="card">
            <div class="card-body">
                <table class="table">
                    <thead>
                        <tr>
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
                        $query = "SELECT * FROM appointments WHERE user_id = ? ORDER BY appointment_date DESC, appointment_time DESC";
                        $stmt = $db->prepare($query);
                        $stmt->bind_param("i", $user_id);
                        $stmt->execute();
                        $result = get_stmt_result($stmt);
                        
                        while ($row = $result->fetch_assoc()):
                            // Convert time to 12-hour format
                            $time_12hr = date("h:i A", strtotime($row['appointment_time']));
                        ?>
                        <tr>
                            <td><?php echo date('F j, Y', strtotime($row['appointment_date'])); ?></td>
                            <td><?php echo $time_12hr; ?></td>
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
                                <?php if ($row['status'] == 'pending'): ?>
                                    <form method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to cancel this appointment?');">
                                        <input type="hidden" name="appointment_id" value="<?php echo $row['id']; ?>">
                                        <button type="submit" name="cancel_appointment" class="btn btn-sm btn-danger">
                                            <i class="fas fa-times me-1"></i>Cancel
                                        </button>
                                    </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
    $(document).ready(function(){
        // Initialize all popovers
        var popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'))
        var popoverList = popoverTriggerList.map(function (popoverTriggerEl) {
            return new bootstrap.Popover(popoverTriggerEl, {
                container: 'body',
                placement: 'left'
            })
        });

        // Existing resendVerification function
        window.resendVerification = function(username) {
            if (confirm('Would you like to receive a new verification email?')) {
                $.ajax({
                    url: 'resend_user_verification.php',
                    method: 'POST',
                    data: { username: username },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            alert('Verification email has been sent successfully! Please check your inbox.');
                        } else {
                            alert(response.message || 'Failed to send verification email');
                        }
                    },
                    error: function() {
                        alert('An error occurred. Please try again later.');
                    }
                });
            }
        }
    });
    </script>
</body>
</html> 