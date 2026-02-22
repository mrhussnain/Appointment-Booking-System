<?php
session_start();
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

    // Create admin_logs table if it doesn't exist
    $db->query("CREATE TABLE IF NOT EXISTS admin_logs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT,
        action VARCHAR(255),
        performed_by VARCHAR(50),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id)
    )");

    $user_id = (int)$_POST['user_id'];
    $status = (int)$_POST['status'];

    try {
        // Start transaction
        $db->begin_transaction();

        // Update verification status
        $query = "UPDATE users SET 
                    email_verified = ?, 
                    verification_token = " . ($status ? "NULL" : "UUID()") . " 
                  WHERE id = ?";
        $stmt = $db->prepare($query);
        $stmt->bind_param("ii", $status, $user_id);
        $stmt->execute();

        // Log the verification action
        $admin_action = $status 
            ? "Email verification manually approved by admin" 
            : "Email verification removed by admin";
        
        $log_query = "INSERT INTO admin_logs (user_id, action, performed_by) VALUES (?, ?, 'admin')";
        $log_stmt = $db->prepare($log_query);
        $log_stmt->bind_param("is", $user_id, $admin_action);
        $log_stmt->execute();

        // Commit transaction
        $db->commit();
        
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'message' => $status ? 'User verified successfully' : 'Verification removed successfully'
        ]);
    } catch (Exception $e) {
        // Rollback on error
        $db->rollback();
        
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'message' => 'Failed to update verification status: ' . $e->getMessage()
        ]);
    }
    exit;
}

header('Content-Type: application/json');
echo json_encode(['success' => false, 'message' => 'Invalid request method']);
exit; 