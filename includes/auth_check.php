<?php
// Include this AFTER session_start() and BEFORE any HTML output on any page
// that needs to know whether a visitor is logged in.

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$isLoggedIn = isset($_SESSION['user_id']);
$currentUserId = $isLoggedIn ? (int) $_SESSION['user_id'] : null;
$currentUsername = $isLoggedIn ? $_SESSION['username'] : null;