<?php
require_once __DIR__ . '/includes/config.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php'; // You'll need to install PHPMailer via composer

function sendVerificationEmail($email, $token, $username) {
    $mail = new PHPMailer(true);

    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host       = 'mail.mrhussnainofficial.com';->Host = SMTP_HOST; // Replace with your SMTP host
        $mail->SMTPAuth   = true;
        $mail->Username   = 'book@mrhussnainofficial.com';->Username = SMTP_USER; // Replace with your email
        $mail->Password   = 'Usman@123';->Password = SMTP_PASS; // Replace with your app password
        $mail->SMTPSecure = 'ssl';->SMTPSecure = SMTP_SECURE;
        $mail->Port       = 465;->Port = SMTP_PORT;
        $mail->Timeout    = 20;
        $mail->SMTPKeepAlive = true;

        // Recipients
        $mail->setFrom('book@mrhussnainofficial.com', 'Appointment System');->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
        $mail->addAddress($email, $username);

        // Content
        $mail->isHTML(true);
        $mail->Subject = 'Verify Your Email Address';
        
        $verificationLink = "' . SITE_URL . '/verify.php?token=" . $token;
        
        $mail->Body = "
            <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
                <h2 style='color: #0d6efd;'>Welcome to Appointment System!</h2>
                <p>Hello $username,</p>
                <p>Thank you for registering. Please click the button below to verify your email address:</p>
                <p style='text-align: center;'>
                    <a href='$verificationLink' 
                       style='background: linear-gradient(135deg, #0d6efd 0%, #0dcaf0 100%);
                              color: white;
                              padding: 12px 24px;
                              text-decoration: none;
                              border-radius: 5px;
                              display: inline-block;'>
                        Verify Email Address
                    </a>
                </p>
                <p>Or copy and paste this link in your browser:</p>
                <p>$verificationLink</p>
                <p>This link will expire in 24 hours.</p>
                <p>If you didn't create an account, you can safely ignore this email.</p>
            </div>";

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Email sending failed: {$mail->ErrorInfo}");
        return false;
    }
}

function sendAppointmentNotification($userEmail, $userName, $appointmentDate, $appointmentTime, $notes) {
    // Send to user
    $userMail = new PHPMailer(true);
    
    try {
        // Server settings
        $userMail->isSMTP();
        $userMail->Host       = 'mail.mrhussnainofficial.com';->Host = SMTP_HOST;
        $userMail->SMTPAuth   = true;
        $userMail->Username   = 'book@mrhussnainofficial.com';->Username = SMTP_USER;
        $userMail->Password   = 'Usman@123';->Password = SMTP_PASS;
        $userMail->SMTPSecure = 'ssl';->SMTPSecure = SMTP_SECURE;
        $userMail->Port       = 465;->Port = SMTP_PORT;
        $userMail->Timeout    = 20;
        $userMail->SMTPKeepAlive = true;

        // User email
        $userMail->setFrom('book@mrhussnainofficial.com', 'Appointment System');->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
        $userMail->addAddress($userEmail, $userName);
        
        // User email content
        $userMail->isHTML(true);
        $userMail->Subject = 'Your Appointment Booking Confirmation';
        $userMail->Body = "
            <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
                <h2 style='color: #0d6efd;'>Appointment Confirmation</h2>
                <p>Hello $userName,</p>
                <p>Your appointment has been booked successfully.</p>
                <p><strong>Date:</strong> " . date('F j, Y', strtotime($appointmentDate)) . "</p>
                <p><strong>Time:</strong> " . date('h:i A', strtotime($appointmentTime)) . "</p>
                " . ($notes ? "<p><strong>Notes:</strong> $notes</p>" : "") . "
                <p>Status: Pending Admin Approval</p>
                <p>You will receive another email once your appointment is confirmed.</p>
            </div>";

        // Send user email
        $userMail->send();

        // Send to admin
        $adminMail = new PHPMailer(true);
        
        // Server settings for admin email
        $adminMail->isSMTP();
        $adminMail->Host       = 'mail.mrhussnainofficial.com';->Host = SMTP_HOST;
        $adminMail->SMTPAuth   = true;
        $adminMail->Username   = 'book@mrhussnainofficial.com';->Username = SMTP_USER;
        $adminMail->Password   = 'Usman@123';->Password = SMTP_PASS;
        $adminMail->SMTPSecure = 'ssl';->SMTPSecure = SMTP_SECURE;
        $adminMail->Port       = 465;->Port = SMTP_PORT;
        $adminMail->Timeout    = 20;
        $adminMail->SMTPKeepAlive = true;

        $adminMail->setFrom('book@mrhussnainofficial.com', 'Appointment System');->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
        $adminMail->addAddress(ADMIN_EMAIL, 'Admin');
        
        $adminMail->isHTML(true);
        $adminMail->Subject = 'New Appointment Booking Received';
        $adminMail->Body = "
            <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
                <h2 style='color: #0d6efd;'>New Appointment Booking</h2>
                <p>A new appointment has been booked.</p>
                <p><strong>User:</strong> $userName ($userEmail)</p>
                <p><strong>Date:</strong> " . date('F j, Y', strtotime($appointmentDate)) . "</p>
                <p><strong>Time:</strong> " . date('h:i A', strtotime($appointmentTime)) . "</p>
                " . ($notes ? "<p><strong>Notes:</strong> $notes</p>" : "") . "
                <p>Please login to the admin panel to approve or reject this appointment.</p>
            </div>";

        // Send admin email
        $adminMail->send();

        return true;
    } catch (Exception $e) {
        error_log("Email sending failed: " . $e->getMessage());
        return false;
    }
}

function sendStatusUpdateNotification($userEmail, $userName, $appointmentDate, $appointmentTime, $status, $adminComments = '') {
    $mail = new PHPMailer(true);

    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host       = 'mail.mrhussnainofficial.com';->Host = SMTP_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = 'book@mrhussnainofficial.com';->Username = SMTP_USER;
        $mail->Password   = 'Usman@123';->Password = SMTP_PASS;
        $mail->SMTPSecure = 'ssl';->SMTPSecure = SMTP_SECURE;
        $mail->Port       = 465;->Port = SMTP_PORT;
        $mail->Timeout    = 20;
        $mail->SMTPKeepAlive = true;

        // Recipients
        $mail->setFrom('book@mrhussnainofficial.com', 'Appointment System');->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
        $mail->addAddress($userEmail, $userName);

        // Content
        $mail->isHTML(true);
        $mail->Subject = 'Appointment Status Updated';

        // Set status color
        $statusColor = $status == 'confirmed' ? '#28a745' : ($status == 'cancelled' ? '#dc3545' : '#ffc107');
        
        $mail->Body = "
            <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
                <h2 style='color: #0d6efd;'>Appointment Status Update</h2>
                <p>Hello $userName,</p>
                <p>Your appointment status has been updated.</p>
                
                <div style='background: #f8f9fa; padding: 20px; border-radius: 10px; margin: 20px 0;'>
                    <p><strong>Date:</strong> " . date('F j, Y', strtotime($appointmentDate)) . "</p>
                    <p><strong>Time:</strong> " . date('h:i A', strtotime($appointmentTime)) . "</p>
                    <p><strong>New Status:</strong> <span style='color: $statusColor; font-weight: bold;'>" . ucfirst($status) . "</span></p>
                    " . ($adminComments ? "<p><strong>Admin Comments:</strong> $adminComments</p>" : "") . "
                </div>
                
                " . ($status == 'confirmed' ? "
                <p>Your appointment has been confirmed. Please arrive on time.</p>
                " : ($status == 'cancelled' ? "
                <p>Your appointment has been cancelled. Please book another appointment if needed.</p>
                " : "")) . "
                
                <p>If you have any questions, please contact us.</p>
            </div>";

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Status update email failed: " . $e->getMessage());
        return false;
    }
}
