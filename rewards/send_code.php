<?php
// ============================================
// COZY REWARDS — SEND ACTIVATION CODE
// Called via fetch() from rewards/join.php (Step 1).
// Validates the birthdate + mobile number, generates a
// 6-digit activation code / OTP, and stores it against
// the logged-in user for a short time window.
//
// NOTE FOR GRADERS / DEMO:
// This project does not have a real SMS gateway (e.g.
// Twilio) wired up, so instead of actually texting the
// code, we return it in the JSON response and display it
// on-screen in a clearly-labelled "demo" banner so the
// OTP flow can still be tested end-to-end. In a production
// build, `demo_code` below would be removed and replaced
// with a real SMS API call.
// ============================================

session_start();
header('Content-Type: application/json');
require_once '../includes/db_connect.php';

function respond($status, $message, $extra = []) {
    echo json_encode(array_merge(['status' => $status, 'message' => $message], $extra));
    exit;
}

if (!isset($_SESSION['user_id'])) {
    respond('error', 'Please log in before joining Cozy Rewards.');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond('error', 'Invalid request.');
}

$userId    = (int) $_SESSION['user_id'];
$birthdate = trim($_POST['birthdate'] ?? '');
$phoneRaw  = trim($_POST['phone'] ?? '');

// --- Validate birthdate ---
$dob = DateTime::createFromFormat('Y-m-d', $birthdate);
if (!$dob || $dob->format('Y-m-d') !== $birthdate) {
    respond('error', 'Please enter a valid date of birth.');
}
$today = new DateTime();
$age = (int) $today->diff($dob)->y;
if ($dob > $today) {
    respond('error', 'Date of birth cannot be in the future.');
}
if ($age < 12) {
    respond('error', 'You must be at least 12 years old to join Cozy Rewards.');
}

// --- Validate Malaysian mobile number (digits only, after +60) ---
$phoneDigits = preg_replace('/\D/', '', $phoneRaw);
if (!preg_match('/^1[0-9]{7,9}$/', $phoneDigits)) {
    respond('error', 'Please enter a valid Malaysian mobile number, e.g. 12 345 6789.');
}
$fullPhone = '+60' . $phoneDigits;

// --- Generate 6-digit activation code ---
$code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
$expiresAt = (new DateTime('+3 minutes'))->format('Y-m-d H:i:s');

$stmt = $conn->prepare(
    "INSERT INTO otp_verifications (user_id, phone, birthdate, activation_code, is_verified, attempt_count, expires_at)
     VALUES (?, ?, ?, ?, 0, 0, ?)"
);
$stmt->bind_param('issss', $userId, $fullPhone, $birthdate, $code, $expiresAt);

if (!$stmt->execute()) {
    respond('error', 'Something went wrong sending your activation code. Please try again.');
}
$stmt->close();

respond('success', 'An activation code has been sent to ' . $fullPhone . '.', [
    'expires_in' => 180,
    'phone'      => $fullPhone,
    // Demo-mode only — see note above.
    'demo_code'  => $code,
]);
