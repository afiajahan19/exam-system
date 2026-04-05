<?php
require_once 'includes/auth.php';

// Redirect to dashboard if already logged in
if (isLoggedIn()) {
    header("Location: dashboard/index.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Online Examination System</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="landing-container">
        <header class="landing-header">
            <h1>Online Examination System</h1>
            <p>A comprehensive platform for conducting online examinations</p>
        </header>

        <main class="landing-content">
            <div class="hero-section">
                <h2>Welcome to the Exam System</h2>
                <p>Please login or register to access the examination platform.</p>
                
                <div class="action-buttons">
                    <a href="auth/login.php" class="btn btn-primary">Login</a>
                    <a href="auth/register.php" class="btn btn-secondary">Register</a>
                </div>
            </div>

            <div class="features-section">
                <h3>System Features</h3>
                <div class="features-grid">
                    <div class="feature-card">
                        <h4>Secure Authentication</h4>
                        <p>Protected login and registration system with password hashing</p>
                    </div>
                    <div class="feature-card">
                        <h4>Role-Based Access</h4>
                        <p>Different access levels for Admin, Teacher, and Student roles</p>
                    </div>
                    <div class="feature-card">
                        <h4>User Dashboard</h4>
                        <p>Personalized dashboard for each user type</p>
                    </div>
                </div>
            </div>
        </main>

        <footer class="landing-footer">
            <p>&copy; 2024 Online Examination System. All rights reserved.</p>
        </footer>
    </div>
</body>
</html>
