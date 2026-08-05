<?php
// ============================================
// COZY COFFEE CO. — ADMIN AUTH GUARD
// Include this at the very top of every admin
// page that should require login, e.g.:
//   require_once '../includes/admin_auth_check.php';
// If no admin is logged in, redirect back to the
// admin login page.
// ============================================

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['admin_id'])) {
    header('Location: index.php');
    exit;
}