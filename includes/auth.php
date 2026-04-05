<?php
session_start();

// Check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Redirect if not logged in
function requireLogin() {
    if (!isLoggedIn()) {
        header("Location: ../auth/login.php");
        exit();
    }
}

// Check if current user has specific role
function hasRole($role) {
    if (!isLoggedIn()) {
        return false;
    }
    return $_SESSION['user_role'] === $role;
}

// Require specific role to access page
function requireRole($role) {
    requireLogin();
    if (!hasRole($role)) {
        header("Location: ../dashboard/index.php");
        exit();
    }
}

// Get current user data
function getCurrentUser() {
    if (isLoggedIn()) {
        return $_SESSION;
    }
    return null;
}

// Logout function
function logout() {
    session_destroy();
    header("Location: ../auth/login.php");
    exit();
}
?>
