<?php
// includes/functions.php
// Utility functions for notifications (email, SMS)
require './vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;


// Fixed email credentials for PHPMailer
$send_email = 'ecardmanagementsystem@gmail.com'; // <-- Set your Gmail address here
$app_password = 'nylc dcjp lkak acew'; // <-- Set your Gmail app password here

// Send email using PHPMailer SMTP (Gmail)
function sendEmail($to, $subject, $message, $from_name = 'OfficeMS Notification') {
    global $send_email, $app_password;
    $mail = new PHPMailer();
    try {
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = $send_email;
        $mail->Password = $app_password;
        $mail->SMTPSecure = 'tls';
        $mail->Port = 587;
        $mail->setFrom($send_email, $from_name);
        $mail->addAddress($to);
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = $message;
        return $mail->send();
    } catch (Exception $e) {
        // Optionally log error: $mail->ErrorInfo
        return false;
    }
}

// Send SMS using Pindo API
function sendSMS($to, $text) {
    $PINDO_API_KEY = 'eyJhbGciOiJIUzUxMiIsInR5cCI6IkpXVCJ9.eyJleHAiOjE4MzcxNzUzMTIsImlhdCI6MTc0MjQ4MDkxMiwiaWQiOiJ1c2VyXzAxSlBTWjlDMTZCTUtZQzZLSkdWRkhQOTBNIiwicmV2b2tlZF90b2tlbl9jb3VudCI6MH0.KjgMZ0ht_NhUbil_3kIgHHByJSokufd2IZdC9-PYeXdkJkan4Rv8DMi0jlHXfZnyh_52bOizk9nTR3QOEBU5ZA';
    $PINDO_API_URL = 'https://api.pindo.io/v1/sms/';
    // Format phone number to +250...
    if (strpos($to, '+') !== 0) {
        if (strpos($to, '0') === 0) {
            $to = '+250' . substr($to, 1);
        } else {
            // Invalid phone format
            return false;
        }
    }
    $data = [
        'to' => $to,
        'text' => $text,
        'sender' => 'PindoTest'
    ];
    $options = [
        'http' => [
            'header'  => "Content-type: application/json\r\n" .
                         "Authorization: Bearer $PINDO_API_KEY\r\n",
            'method'  => 'POST',
            'content' => json_encode($data),
            'ignore_errors' => true
        ]
    ];
    $context  = stream_context_create($options);
    $result = file_get_contents($PINDO_API_URL, false, $context);
    if ($result === FALSE) {
        return false;
    }
    return json_decode($result, true);
}

// Save notification to database
function saveNotification($connection, $receiver_id, $type, $title, $message) {
    $stmt = $connection->prepare("INSERT INTO notifications (receiver_id, type, title, message) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("isss", $receiver_id, $type, $title, $message);
    $stmt->execute();
    $stmt->close();
} 