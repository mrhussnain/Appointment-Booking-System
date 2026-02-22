<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php';

function sendVerificationEmail($email, $token, $username, $verification_url) {
    $mail = new PHPMailer(true);

    try {
        // Server settings
        $mail->SMTPDebug = 0;  // Disable debug output
        $mail->isSMTP();
        $mail->Host       = 'mail.mrhussnainofficial.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'book@mrhussnainofficial.com';
        $mail->Password   = 'Usman@123';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port       = 465;

        // SSL Options
        $mail->SMTPOptions = array(
            'ssl' => array(
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            )
        );

        // Recipients
        $mail->setFrom('book@mrhussnainofficial.com', 'Appointment System');
        $mail->addAddress($email, $username);

        // Content
        $mail->isHTML(true);
        $mail->Subject = 'Email Verification - Appointment System';
        $mail->Body = "
            <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
                <h2 style='color: #0d6efd;'>Email Verification</h2>
                <p>Hello {$username},</p>
                <p>Please click the button below to verify your email address:</p>
                <p style='text-align: center;'>
                    <a href='{$verification_url}' 
                       style='display: inline-block; padding: 10px 20px; background: #0d6efd; 
                              color: white; text-decoration: none; border-radius: 5px;'>
                        Verify Email Address
                    </a>
                </p>
                <p>Or copy and paste this link in your browser:</p>
                <p>{$verification_url}</p>
                <p>This link will expire in 24 hours.</p>
            </div>";

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Mail Error: " . $mail->ErrorInfo);
        return false;
    }
} 