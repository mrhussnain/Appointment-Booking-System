<?php
session_start();
require_once __DIR__ . '/includes/db_helper.php';
require_once 'mail_config.php';

if (!isset($_SESSION['admin_logged_in'])) {
    http_response_code(403);
    exit('Unauthorized');
}

$db = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($db->connect_error) {
    die("Connection failed: " . $db->connect_error);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $appointment_id = (int)$_POST['appointment_id'];
    $status = $db->real_escape_string($_POST['status']);
    $admin_comments = isset($_POST['admin_comments']) ? $db->real_escape_string($_POST['admin_comments']) : '';

    // Get appointment details
    $query = "SELECT a.*, u.email, u.name FROM appointments a 
              JOIN users u ON a.user_id = u.id 
              WHERE a.id = ?";
    $stmt = $db->prepare($query);
    $stmt->bind_param("i", $appointment_id);
    $stmt->execute();
    $appointment = get_stmt_result($stmt)->fetch_assoc();

    if ($appointment) {
        // Update appointment status
        $update_query = "UPDATE appointments SET status = ?, admin_comments = ? WHERE id = ?";
        $stmt = $db->prepare($update_query);
        $stmt->bind_param("ssi", $status, $admin_comments, $appointment_id);
        
        if ($stmt->execute()) {
            // Send email notification
            $emailSent = sendStatusUpdateNotification(
                $appointment['email'],
                $appointment['name'],
                $appointment['appointment_date'],
                $appointment['appointment_time'],
                $status,
                $admin_comments
            );

            echo json_encode([
                'success' => true,
                'message' => 'Status updated successfully' . ($emailSent ? '' : ' (Email notification failed)')
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to update status']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Appointment not found']);
    }
    exit;
} 