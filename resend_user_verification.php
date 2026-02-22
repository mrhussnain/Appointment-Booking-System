<?php
session_start();
require_once __DIR__ . '/includes/db_helper.php';

// Add these use statements at the top
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

require_once 'includes/mail_config.php';
require 'vendor/autoload.php';  // Make sure this path is correct

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $db = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        if ($db->connect_error) {
            throw new Exception("Database connection failed: " . $db->connect_error);
        }

        if (!isset($_POST['username'])) {
            throw new Exception("Username not provided");
        }

        $username = $db->real_escape_string($_POST['username']);
        
        // Log the username being processed
        error_log("Processing verification request for username: " . $username);

        // Get user details
        $query = "SELECT id, username, email, verification_token FROM users WHERE username = ?";
        $stmt = $db->prepare($query);
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = get_stmt_result($stmt);
        $user = $result->fetch_assoc();

        if (!$user) {
            throw new Exception("User not found: " . $username);
        }

        // Generate new verification token
        $verification_token = bin2hex(random_bytes(32));
        
        // Update the token in database
        $update_query = "UPDATE users SET verification_token = ? WHERE id = ?";
        $stmt = $db->prepare($update_query);
        $stmt->bind_param("si", $verification_token, $user['id']);
        
        if (!$stmt->execute()) {
            throw new Exception("Failed to update verification token");
        }

        // Initialize PHPMailer
        $mail = new PHPMailer(true);

        try {
            // Server settings
            $mail->SMTPDebug = 0; // Disable debug output in production
            $mail->isSMTP();
            $mail->Host       = 'mail.mrhussnainofficial.com';->Host = SMTP_HOST;
            $mail->SMTPAuth   = true;
            $mail->Username   = 'book@mrhussnainofficial.com';->Username = SMTP_USER;
            $mail->Password   = 'Usman@123';->Password = SMTP_PASS;
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;->SMTPSecure = SMTP_SECURE;
            $mail->Port       = 465;->Port = SMTP_PORT;

            // SSL Options
            $mail->SMTPOptions = array(
                'ssl' => array(
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'allow_self_signed' => true
                )
            );

            // Recipients
            $mail->setFrom('book@mrhussnainofficial.com', 'Appointment System');->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
            $mail->addAddress($user['email'], $user['username']);

            // Content
            $verification_link = "' . SITE_URL . '/verify_email.php?token=" . $verification_token;
            
            $mail->isHTML(true);
            $mail->Subject = 'Email Verification - Appointment System';
            $mail->Body = "
                <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
                    <h2 style='color: #0d6efd;'>Email Verification</h2>
                    <p>Hello {$user['username']},</p>
                    <p>Please click the button below to verify your email address:</p>
                    <p style='text-align: center;'>
                        <a href='{$verification_link}' 
                           style='display: inline-block; padding: 10px 20px; background: #0d6efd; 
                                  color: white; text-decoration: none; border-radius: 5px;'>
                            Verify Email Address
                        </a>
                    </p>
                    <p>Or copy and paste this link in your browser:</p>
                    <p>{$verification_link}</p>
                    <p>This link will expire in 24 hours.</p>
                </div>";

            $mail->send();

            header('Content-Type: application/json');
            echo json_encode([
                'success' => true, 
                'message' => 'Verification email sent successfully to ' . $user['email']
            ]);

        } catch (Exception $e) {
            throw new Exception("Mailer Error: " . $mail->ErrorInfo);
        }

    } catch (Exception $e) {
        error_log("Verification email error: " . $e->getMessage());
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false, 
            'message' => $e->getMessage()
        ]);
    }
} else {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false, 
        'message' => 'Invalid request method'
    ]);
}
?> 