<?php
// ============================================
// COZY REWARDS — VERIFY OTP & ACTIVATE MEMBERSHIP
// Called via fetch() from rewards/join.php (Step 2).
// Checks the entered OTP against the most recent
// activation code requested by this user; if it matches
// and hasn't expired, the account is upgraded to a Cozy
// Rewards member (bonus points, member number, phone &
// birthdate saved).
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
$enteredCode = trim($_POST['code'] ?? '');

if ($enteredCode === '') {
    respond('error', 'Please enter the activation code sent to your mobile.');
}

// Fetch the most recent unverified OTP request for this user
$stmt = $conn->prepare(
    "SELECT id, phone, birthdate, activation_code, attempt_count, expires_at
     FROM otp_verifications
     WHERE user_id = ? AND is_verified = 0
     ORDER BY id DESC LIMIT 1"
);
$stmt->bind_param('i', $userId);
$stmt->execute();
$otp = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$otp) {
    respond('error', 'No pending activation code found. Please request a new one.', ['expired' => true]);
}

// Expired?
if (strtotime($otp['expires_at']) < time()) {
    respond('error', 'This activation code has expired. Please request a new one.', ['expired' => true]);
}

// Too many attempts?
if ((int) $otp['attempt_count'] >= 5) {
    respond('error', 'Too many incorrect attempts. Please request a new activation code.', ['expired' => true]);
}

// Wrong code?
if (!hash_equals($otp['activation_code'], $enteredCode)) {
    $upd = $conn->prepare("UPDATE otp_verifications SET attempt_count = attempt_count + 1 WHERE id = ?");
    $upd->bind_param('i', $otp['id']);
    $upd->execute();
    $upd->close();

    $remaining = 5 - ((int) $otp['attempt_count'] + 1);
    respond('error', 'Incorrect code. ' . max($remaining, 0) . ' attempt(s) remaining.');
}

// --- Correct code: mark verified & activate membership ---
$conn->begin_transaction();
try {
    $markStmt = $conn->prepare("UPDATE otp_verifications SET is_verified = 1 WHERE id = ?");
    $markStmt->bind_param('i', $otp['id']);
    $markStmt->execute();
    $markStmt->close();

    $memberNo = 'CR' . str_pad((string) $userId, 6, '0', STR_PAD_LEFT);
    $welcomeBonus = 50;

    $userStmt = $conn->prepare(
        "UPDATE users
         SET is_rewards_member = 1,
             rewards_points = rewards_points + ?,
             rewards_member_no = ?,
             rewards_joined_at = NOW(),
             phone = ?,
             birthday = ?
         WHERE id = ?"
    );
    $userStmt->bind_param('isssi', $welcomeBonus, $memberNo, $otp['phone'], $otp['birthdate'], $userId);
    $userStmt->execute();
    $userStmt->close();

    $conn->commit();
} catch (Exception $e) {
    $conn->rollback();
    respond('error', 'Something went wrong activating your membership. Please try again.');
}

respond('success', 'Welcome to Cozy Rewards! You just earned ' . $welcomeBonus . ' bonus points.', [
    'redirect' => '../rewards/index.php',
]);
