<?php
session_start();
require_once __DIR__ . '/includes/db_helper.php';
require_once 'includes/mail_config.php';

$db = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($db->connect_error) {
    die("Connection failed: " . $db->connect_error);
}

$message = '';
$messageType = '';

if (isset($_GET['token'])) {
    $token = $db->real_escape_string($_GET['token']);
    
    // Check if token exists and is not expired
    $query = "SELECT id FROM users WHERE verification_token = ? AND token_expiry > NOW() AND email_verified = 0";
    $stmt = $db->prepare($query);
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = get_stmt_result($stmt);

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        
        // Update user as verified
        $update = "UPDATE users SET email_verified = 1, verification_token = NULL WHERE id = ?";
        $stmt = $db->prepare($update);
        $stmt->bind_param("i", $user['id']);
        
        if ($stmt->execute()) {
            $message = "Email verified successfully! You can now login.";
            $messageType = "success";
        } else {
            $message = "Verification failed. Please try again.";
            $messageType = "danger";
        }
    } else {
        $message = "Invalid or expired verification link.";
        $messageType = "danger";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Verification</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <?php require_once 'includes/theme.php'; echo $themeStyles; ?>
    <style>
        body { 
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            font-family: 'Poppins', sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
        }
        .verification-container {
            max-width: 500px;
            margin: 2rem auto;
            padding: 2rem;
            background: #ffffff;
            border-radius: 15px;
            box-shadow: 0 0 20px rgba(0,0,0,.08);
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="verification-container text-center">
                    <?php if ($message): ?>
                        <div class="alert alert-<?php echo $messageType; ?>">
                            <?php echo $message; ?>
                        </div>
                    <?php endif; ?>
                    
                    <a href="login.php" class="btn btn-primary">Go to Login</a>
                </div>
            </div>
        </div>
    </div>
</body>
</html> 