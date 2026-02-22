<?php
session_start();
require_once __DIR__ . '/includes/db_helper.php';
require_once 'includes/mail_config.php';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

$db = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($db->connect_error) {
    die("Connection failed: " . $db->connect_error);
}

// Handle token verification first
if (isset($_GET['token'])) {
    $token = $_GET['token'];
    
    // Find user by token
    $check_query = "SELECT id, email_verified, username FROM users WHERE verification_token = ?";
    $check_stmt = $db->prepare($check_query);
    $check_stmt->bind_param("s", $token);
    $check_stmt->execute();
    $check_result = get_stmt_result($check_stmt);
    $user_check = $check_result->fetch_assoc();
    
    if ($user_check) {
        if ($user_check['email_verified']) {
            $_SESSION['success_message'] = "Email already verified!";
            header('Location: login.php');
            exit;
        } else {
            // Update user to verified status
            $verify_query = "UPDATE users SET email_verified = 1, verification_token = NULL WHERE id = ?";
            $stmt = $db->prepare($verify_query);
            $stmt->bind_param("i", $user_check['id']);
            
            if ($stmt->execute()) {
                $_SESSION['success_message'] = "Email verified successfully! Please login to continue.";
                header('Location: login.php');
                exit;
            } else {
                $error_message = "Database error during verification. Please try again.";
            }
        }
    } else {
        $error_message = "Invalid or expired verification token. Please request a new verification email.";
    }
}

// If no token or verification failed, check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Get logged-in user details for the verification page
$user_id = (int)$_SESSION['user_id'];
$query = "SELECT email_verified, email, username FROM users WHERE id = ?";
$stmt = $db->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = get_stmt_result($stmt);
$user = $result->fetch_assoc();

// Check if user exists
if (!$user) {
    session_destroy();
    $_SESSION['error_message'] = "User account not found. Please register or login again.";
    header('Location: login.php');
    exit;
}

if ($user['email_verified']) {
    header('Location: dashboard.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Verification Required</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <?php require_once 'includes/theme.php'; echo $themeStyles; ?>
    <style>
        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            font-family: 'Poppins', sans-serif;
        }
        .verification-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
        }
        .icon-warning {
            font-size: 4rem;
            color: #ffc107;
        }
        .alert i {
            font-size: 1.2rem;
        }
        .alert-success {
            background-color: #d4edda;
            border-color: #c3e6cb;
            color: #155724;
        }
        .alert-danger {
            background-color: #f8d7da;
            border-color: #f5c6cb;
            color: #721c24;
        }
    </style>
</head>
<body>
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card verification-card">
                    <div class="card-body text-center p-5">
                        <?php if (isset($success_message)): ?>
                            <div class="alert alert-success mb-4">
                                <i class="fas fa-check-circle me-2"></i>
                                <?php echo $success_message; ?>
                            </div>
                        <?php elseif (isset($error_message)): ?>
                            <div class="alert alert-danger mb-4">
                                <i class="fas fa-exclamation-circle me-2"></i>
                                <?php echo $error_message; ?>
                            </div>
                        <?php else: ?>
                            <i class="fas fa-envelope-open-text icon-warning mb-4"></i>
                            <h3 class="mb-4">Email Verification Required</h3>
                            <p class="mb-4">
                                You must verify your email address before you can make appointments.
                                Please check your inbox for the verification link.
                            </p>
                            <button class="btn btn-primary btn-lg mb-3" onclick="resendVerification()">
                                <i class="fas fa-paper-plane me-2"></i>Resend Verification Email
                            </button>
                            <div class="mt-3">
                                <a href="logout.php" class="btn btn-link text-muted">Logout</a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        function resendVerification() {
            $.ajax({
                url: 'resend_user_verification.php',
                method: 'POST',
                data: { username: '<?php echo htmlspecialchars($user['username'] ?? ''); ?>' },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        alert('Verification email has been sent successfully! Please check your inbox.');
                    } else {
                        alert(response.message || 'Failed to send verification email');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Ajax error:', error);
                    alert('An error occurred while sending the verification email. Please try again later.');
                }
            });
        }
    </script>
</body>
</html> 
</html> 