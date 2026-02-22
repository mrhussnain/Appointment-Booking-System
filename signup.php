<?php
session_start();
require_once __DIR__ . '/includes/db_helper.php';
if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

require_once 'includes/mail_config.php';

$db = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($db->connect_error) {
    die("Connection failed: " . $db->connect_error);
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $db->real_escape_string($_POST['username']);
    $email = $db->real_escape_string($_POST['email']);
    $phone = $db->real_escape_string($_POST['phone']);
    $name = $db->real_escape_string($_POST['name']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    // Validate password match
    if ($password !== $confirm_password) {
        $error = "Passwords do not match";
    } 
    // Validate password length
    elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters long";
    } 
    else {
        // Check if username exists
        $check_query = "SELECT id FROM users WHERE username = ? OR email = ?";
        $stmt = $db->prepare($check_query);
        $stmt->bind_param("ss", $username, $email);
        $stmt->execute();
        $result = get_stmt_result($stmt);

        if ($result->num_rows > 0) {
            $error = "Username or email already exists";
        } else {
            // Generate verification token
            $verification_token = bin2hex(random_bytes(32));
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            $query = "INSERT INTO users (username, email, phone, name, password, verification_token) VALUES (?, ?, ?, ?, ?, ?)";
            $stmt = $db->prepare($query);
            $stmt->bind_param("ssssss", $username, $email, $phone, $name, $hashed_password, $verification_token);

            if ($stmt->execute()) {
                // Send verification email with the correct URL
                $verification_url = "https://booknow.mrhussnain.com/verify_email.php?token=" . $verification_token;
                $emailSent = sendVerificationEmail($email, $verification_token, $username, $verification_url);
                
                if ($emailSent) {
                    $success = "Registration successful! Please check your email to verify your account.";
                } else {
                    $error = "Registration successful but failed to send verification email. Please contact support.";
                }
            } else {
                $error = "Registration failed. Please try again.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up - Appointment System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <?php require_once 'includes/theme.php'; echo $themeStyles; ?>
    <style>
        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            font-family: 'Poppins', sans-serif;
            padding: 2rem 0;
        }

        .signup-container {
            max-width: 500px;
            width: 90%;
            margin: 0 auto;
        }

        .signup-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }

        .signup-header {
            background: var(--gradient-primary);
            padding: 2rem;
            text-align: center;
            color: white;
        }

        .signup-header .icon {
            width: 60px;
            height: 60px;
            background: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1rem;
            color: #667db6;
            font-size: 1.5rem;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }

        .signup-body {
            padding: 2rem;
        }

        .form-control {
            border: 2px solid #e9ecef;
            border-radius: 10px;
            padding: 0.75rem;
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        .form-control:focus {
            border-color: #667db6;
            box-shadow: 0 0 0 0.25rem rgba(102, 125, 182, 0.25);
        }

        .input-group-text {
            border: 2px solid #e9ecef;
            border-right: none;
            background: white;
            color: #667db6;
        }

        .btn-signup {
            background: var(--gradient-primary);
            border: none;
            color: white;
            padding: 0.75rem;
            border-radius: 10px;
            font-weight: 500;
            width: 100%;
            margin-top: 1rem;
            transition: all 0.3s ease;
        }

        .btn-signup:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 125, 182, 0.3);
        }

        .login-link {
            text-align: center;
            margin-top: 1.5rem;
            color: #6c757d;
        }

        .login-link a {
            color: #667db6;
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s ease;
        }

        .login-link a:hover {
            color: #0d6efd;
        }

        .alert {
            border-radius: 10px;
            margin-bottom: 1rem;
        }

        @media (max-width: 576px) {
            .signup-container {
                width: 100%;
                padding: 1rem;
            }

            .signup-header {
                padding: 1.5rem;
            }

            .signup-body {
                padding: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <div class="signup-container">
        <div class="signup-card">
            <div class="signup-header">
                <div class="icon">
                    <i class="fas fa-user-plus"></i>
                </div>
                <h4 class="mb-0">Create Account</h4>
                <p class="mb-0">Join us today!</p>
            </div>
            <div class="signup-body">
                <?php if ($error): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="fas fa-exclamation-circle me-2"></i>
                        <?php echo $error; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <?php if ($success): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="fas fa-check-circle me-2"></i>
                        <?php echo $success; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <form method="POST" action="">
                    <div class="mb-3">
                        <label class="form-label">Full Name</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-user"></i></span>
                            <input type="text" name="name" class="form-control" required 
                                   placeholder="Enter your full name">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Username</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-at"></i></span>
                            <input type="text" name="username" class="form-control" required 
                                   placeholder="Choose a username">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                            <input type="email" name="email" class="form-control" required 
                                   placeholder="Enter your email">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Phone</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-phone"></i></span>
                            <input type="tel" name="phone" class="form-control" required 
                                   placeholder="Enter your phone number">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Password</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-lock"></i></span>
                            <input type="password" name="password" class="form-control" required 
                                   placeholder="Create a password">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Confirm Password</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-lock"></i></span>
                            <input type="password" name="confirm_password" class="form-control" required 
                                   placeholder="Confirm your password">
                        </div>
                    </div>
                    <button type="submit" class="btn btn-signup">
                        <i class="fas fa-user-plus me-2"></i>Sign Up
                    </button>
                </form>
                <div class="login-link">
                    Already have an account? <a href="login.php">Login</a>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 