<?php
require_once '../includes/auth.php';

// Require login to access dashboard
requireLogin();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Exam System</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="dashboard-container">
        <header class="dashboard-header">
            <div class="header-content">
                <h1>Exam System Dashboard</h1>
                <div class="user-info">
                    <span>Welcome, <strong><?php echo htmlspecialchars($_SESSION['user_name']); ?></strong></span>
                    <span class="role-badge"><?php echo ucfirst(htmlspecialchars($_SESSION['user_role'])); ?></span>
                    <a href="../auth/logout.php" class="btn btn-logout">Logout</a>
                </div>
            </div>
        </header>

        <main class="dashboard-content">
            <div class="welcome-card">
                <h2>Welcome to Your Dashboard</h2>
                <p>You are logged in as a <strong><?php echo ucfirst(htmlspecialchars($_SESSION['user_role'])); ?></strong>.</p>
                
                <div class="user-details">
                    <h3>Your Information:</h3>
                    <ul>
                        <li><strong>Name:</strong> <?php echo htmlspecialchars($_SESSION['user_name']); ?></li>
                        <li><strong>Email:</strong> <?php echo htmlspecialchars($_SESSION['user_email']); ?></li>
                        <li><strong>Role:</strong> <?php echo ucfirst(htmlspecialchars($_SESSION['user_role'])); ?></li>
                    </ul>
                </div>

                <?php if ($_SESSION['user_role'] == 'admin'): ?>
                    <div class="admin-actions">
                        <h3>Admin Actions:</h3>
                        <p>Admin functionality will be implemented here.</p>
                    </div>
                <?php elseif ($_SESSION['user_role'] == 'teacher'): ?>
                    <div class="teacher-actions">
                        <h3>Teacher Actions:</h3>
                        <div class="action-links">
                            <a href="../teacher/exam_rooms.php" class="btn btn-primary">Manage Exam Rooms</a>
                            <p style="margin-top: 10px; color: #666;">Create and manage exam rooms for your students.</p>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="student-actions">
                        <h3>Student Actions:</h3>
                        <div class="action-links">
                            <a href="../student/join_exam.php" class="btn btn-success">Join Exam Room</a>
                            <p style="margin-top: 10px; color: #666;">Enter a room code to join an exam.</p>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>
</body>
</html>
