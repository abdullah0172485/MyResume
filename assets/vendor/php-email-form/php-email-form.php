<?php
// php-email-form.php

// Set your receiving email address here:
$receiving_email_address = 'abdullah0172485@gmail.com';

// To enable reCAPTCHA, put your secret key here; otherwise leave empty:
$recaptcha_secret_key = ''; // e.g. '6Lc...your-secret-key...'

// Return plain text for the JS to parse
header('Content-Type: text/plain');

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo 'Method Not Allowed';
    exit;
}

// Helper function: clean input
function clean_input($data) {
    return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
}

// Collect and sanitize form inputs
$name = isset($_POST['name']) ? clean_input($_POST['name']) : '';
$email = isset($_POST['email']) ? filter_var($_POST['email'], FILTER_VALIDATE_EMAIL) : false;
$subject = isset($_POST['subject']) ? clean_input($_POST['subject']) : '';
$message = isset($_POST['message']) ? clean_input($_POST['message']) : '';
$recaptcha_response = isset($_POST['recaptcha-response']) ? $_POST['recaptcha-response'] : '';

// Validate inputs
if (empty($name) || !$email || empty($subject) || empty($message)) {
    http_response_code(400);
    echo 'Please fill in all the required fields with valid information.';
    exit;
}

// Verify reCAPTCHA if key is provided
if (!empty($recaptcha_secret_key)) {
    if (empty($recaptcha_response)) {
        http_response_code(400);
        echo 'reCAPTCHA verification failed: response missing.';
        exit;
    }
    // Verify via Google API
    $verify_url = 'https://www.google.com/recaptcha/api/siteverify';
    $response = file_get_contents($verify_url . '?secret=' . urlencode($recaptcha_secret_key) . '&response=' . urlencode($recaptcha_response));
    $response_keys = json_decode($response, true);

    if (!$response_keys['success']) {
        http_response_code(400);
        echo 'reCAPTCHA verification failed.';
        exit;
    }
}

// Prepare email headers
$headers = "From: {$name} <{$email}>\r\n";
$headers .= "Reply-To: {$email}\r\n";
$headers .= "Content-Type: text/plain; charset=UTF-8\r\n";

// Build email body
$email_body = "You have received a new message from your website contact form.\n\n";
$email_body .= "Name: {$name}\n";
$email_body .= "Email: {$email}\n";
$email_body .= "Subject: {$subject}\n\n";
$email_body .= "Message:\n{$message}\n";

// Send email
$mail_success = mail($receiving_email_address, $subject, $email_body, $headers);

if ($mail_success) {
    echo 'OK';  // This exact string signals success to your JS
} else {
    http_response_code(500);
    echo 'Failed to send email. Please try again later.';
}
