<?php
/**
 * Secure contact form handler for yogeshwebdeveloper.com
 * Sends notification to owner + auto-reply to customer
 */

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('Referrer-Policy: strict-origin-when-cross-origin');

// CORS - allow only your domain in production
$allowedOrigins = [
    'https://yogeshwebdeveloper.com',
    'https://www.yogeshwebdeveloper.com',
    'http://localhost:5173',
    'http://127.0.0.1:5173',
];
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
if (in_array($origin, $allowedOrigins, true)) {
    header("Access-Control-Allow-Origin: $origin");
    header('Access-Control-Allow-Methods: POST, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, X-Requested-With');
}

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
    exit;
}

// Rate limiting by IP
$ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
$rateFile = sys_get_temp_dir() . '/ywd_contact_' . md5($ip) . '.txt';
$now = time();
if (file_exists($rateFile)) {
    $last = (int) file_get_contents($rateFile);
    if ($now - $last < 60) {
        http_response_code(429);
        echo json_encode(['success' => false, 'message' => 'Too many requests. Please wait a minute and try again.']);
        exit;
    }
}
file_put_contents($rateFile, (string) $now);

$raw = file_get_contents('php://input');
$data = json_decode($raw, true);

if (!is_array($data)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid request.']);
    exit;
}

// Honeypot anti-spam
if (!empty($data['website'])) {
    http_response_code(200);
    echo json_encode(['success' => true, 'message' => 'Thank you!']);
    exit;
}

function clean($value, $max = 500) {
    $value = strip_tags((string) $value);
    $value = preg_replace('/[<>"\'`;(){}[\]\\\\]/', '', $value);
    return trim(mb_substr($value, 0, $max));
}

$name = clean($data['name'] ?? '', 80);
$email = clean($data['email'] ?? '', 120);
$phone = clean($data['phone'] ?? '', 20);
$inquiryType = clean($data['inquiryType'] ?? '', 100);
$message = clean($data['message'] ?? '', 2000);

if (strlen($name) < 2) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Please enter a valid name.']);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Please enter a valid email address.']);
    exit;
}

if ($phone !== '' && !preg_match('/^(\+91)?[6-9]\d{9}$/', preg_replace('/[\s-]/', '', $phone))) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Please enter a valid 10-digit Indian mobile number.']);
    exit;
}

if (strlen($message) < 10) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Message must be at least 10 characters.']);
    exit;
}

if (empty($inquiryType)) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Please select a service.']);
    exit;
}

$ownerEmail = 'ygupta13@gmail.com';
$fromEmail = 'contact@yogeshwebdeveloper.com';
$siteName = 'Yogesh Web Developer';

// --- Email to owner ---
$ownerSubject = "New Contact Query - $siteName";
$ownerBody = "New contact form submission from yogeshwebdeveloper.com\n\n"
    . "Name: $name\n"
    . "Email: $email\n"
    . "Phone: $phone\n"
    . "Service: $inquiryType\n"
    . "Message:\n$message\n\n"
    . "Submitted: " . date('Y-m-d H:i:s') . " IST\n"
    . "IP: $ip\n";

$ownerHeaders = "From: $siteName <$fromEmail>\r\n"
    . "Reply-To: $name <$email>\r\n"
    . "X-Mailer: PHP/" . phpversion() . "\r\n"
    . "Content-Type: text/plain; charset=UTF-8\r\n";

$ownerSent = @mail($ownerEmail, $ownerSubject, $ownerBody, $ownerHeaders);

// --- Auto-reply to customer ---
$customerSubject = "Thank you for contacting Yogesh Web Developer";
$customerBody = "Dear $name,\n\n"
    . "Thank you for reaching out to Yogesh Web Developer (yogeshwebdeveloper.com).\n\n"
    . "We have received your inquiry regarding: $inquiryType\n\n"
    . "Our team will review your message and get back to you within 24 hours.\n\n"
    . "Your submitted details:\n"
    . "Email: $email\n"
    . "Phone: $phone\n\n"
    . "If you have any urgent query, feel free to call us at +91 83779 56442 or WhatsApp us.\n\n"
    . "Best regards,\n"
    . "Yogesh Web Developer\n"
    . "contact@yogeshwebdeveloper.com\n"
    . "https://yogeshwebdeveloper.com\n";

$customerHeaders = "From: $siteName <$fromEmail>\r\n"
    . "Reply-To: $fromEmail\r\n"
    . "X-Mailer: PHP/" . phpversion() . "\r\n"
    . "Content-Type: text/plain; charset=UTF-8\r\n";

$customerSent = @mail($email, $customerSubject, $customerBody, $customerHeaders);

if ($ownerSent) {
    echo json_encode([
        'success' => true,
        'message' => 'Thank you! Your message has been sent successfully. A confirmation email has been sent to your inbox. We will get back to you within 24 hours.',
        'autoReply' => $customerSent,
    ]);
} else {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Unable to send email at the moment. Please email us directly at contact@yogeshwebdeveloper.com or call +91 83779 56442.',
    ]);
}
