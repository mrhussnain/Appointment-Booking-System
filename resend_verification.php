<?php
session_start();
require_once __DIR__ . '/includes/db_helper.php';
require_once 'includes/mail_config.php';

if (!isset($_SESSION['admin_logged_in'])) {
    header('Content-Type: application/json');
    exit(json_encode(['success' => false, 'message' => 'Unauthorized']));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $db = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if ($db->connect_error) {
        header('Content-Type: application/json');
        exit(json_encode(['success' => false, 'message' => 'Database connection failed']));
    }

    $user_id = (int)$_POST['user_id'];

    try {
        // Start transaction
        $db->begin_transaction();

        // Get user details
        $query = "SELECT username, email, verification_token FROM users WHERE id = ? AND email_verified = 0";
        $stmt = $db->prepare($query);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = get_stmt_result($stmt);
        $user = $result->fetch_assoc();

        if (!$user) {
            throw new Exception('User not found or already verified');
        }

        // Generate new token
        $token = bin2hex(random_bytes(32));
        $expiry = date('Y-m-d H:i:s', strtotime('+24 hours'));
        
        // Update token
        $update = "UPDATE users SET verification_token = ?, token_expiry = ? WHERE id = ?";
        $stmt = $db->prepare($update);
        $stmt->bind_param("ssi", $token, $expiry, $user_id);
        $stmt->execute();

        // Send verification email
        if (!sendVerificationEmail($user['email'], $token, $user['username'])) {
            throw new Exception('Failed to send verification email');
        }

        // Log the action
        $admin_action = "Verification email resent by admin";
        $log_query = "INSERT INTO admin_logs (user_id, action, performed_by) VALUES (?, ?, 'admin')";
        $log_stmt = $db->prepare($log_query);
        $log_stmt->bind_param("is", $user_id, $admin_action);
        $log_stmt->execute();

        // Commit transaction
        $db->commit();
        
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'message' => 'Verification email has been sent successfully'
        ]);
        
    } catch (Exception $e) {
        // Rollback on error
        $db->rollback();
        
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
    exit;
}

header('Content-Type: application/json');
echo json_encode(['success' => false, 'message' => 'Invalid request method']);
exit; 