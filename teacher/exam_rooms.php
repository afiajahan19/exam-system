<?php
require_once '../includes/auth.php';
require_once '../includes/exam_room.php';

// Only teachers can access this page
requireRole('teacher');

$message = '';
$message_type = '';

// Handle form submission for creating exam room
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_room'])) {
    $title = trim($_POST['title']);
    $subject = trim($_POST['subject']);
    $duration = intval($_POST['duration']);
    
    if (empty($title) || empty($subject) || $duration <= 0) {
        $message = 'Please fill in all fields correctly.';
        $message_type = 'error';
    } else {
        if (createExamRoom($title, $subject, $duration, $_SESSION['user_id'])) {
            $message = 'Exam room created successfully!';
            $message_type = 'success';
        } else {
            $message = 'Error creating exam room. Please try again.';
            $message_type = 'error';
        }
    }
}

// Get teacher's exam rooms
$exam_rooms = getTeacherExamRooms($_SESSION['user_id']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Exam Rooms - Teacher Dashboard</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .exam-rooms-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .section-card {
            background: white;
            border-radius: 8px;
            padding: 25px;
            margin-bottom: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
            color: #333;
        }
        
        .form-group input {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        
        .btn {
            background: #007bff;
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            text-decoration: none;
            display: inline-block;
        }
        
        .btn:hover {
            background: #0056b3;
        }
        
        .message {
            padding: 12px;
            border-radius: 4px;
            margin-bottom: 20px;
        }
        
        .message.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .message.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .rooms-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        
        .rooms-table th,
        .rooms-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        
        .rooms-table th {
            background: #f8f9fa;
            font-weight: 600;
        }
        
        .room-code {
            font-family: monospace;
            background: #f8f9fa;
            padding: 4px 8px;
            border-radius: 4px;
            font-weight: bold;
        }
        
        .back-link {
            color: #007bff;
            text-decoration: none;
            margin-bottom: 20px;
            display: inline-block;
        }
        
        .back-link:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="exam-rooms-container">
        <a href="../dashboard/index.php" class="back-link">← Back to Dashboard</a>
        
        <h1>Exam Room Management</h1>
        
        <!-- Create Exam Room Section -->
        <div class="section-card">
            <h2>Create New Exam Room</h2>
            
            <?php if ($message): ?>
                <div class="message <?php echo $message_type; ?>">
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <input type="hidden" name="create_room" value="1">
                
                <div class="form-group">
                    <label for="title">Exam Title</label>
                    <input type="text" id="title" name="title" required 
                           placeholder="e.g., Mathematics Final Exam">
                </div>
                
                <div class="form-group">
                    <label for="subject">Subject</label>
                    <input type="text" id="subject" name="subject" required 
                           placeholder="e.g., Mathematics">
                </div>
                
                <div class="form-group">
                    <label for="duration">Duration (minutes)</label>
                    <input type="number" id="duration" name="duration" required 
                           min="1" max="300" placeholder="e.g., 60">
                </div>
                
                <button type="submit" class="btn">Create Exam Room</button>
            </form>
        </div>
        
        <!-- Exam Rooms List Section -->
        <div class="section-card">
            <h2>Your Exam Rooms</h2>
            
            <?php if (count($exam_rooms) > 0): ?>
                <table class="rooms-table">
                    <thead>
                        <tr>
                            <th>Title</th>
                            <th>Subject</th>
                            <th>Room Code</th>
                            <th>Duration</th>
                            <th>Created</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($exam_rooms as $room): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($room['title']); ?></td>
                                <td><?php echo htmlspecialchars($room['subject']); ?></td>
                                <td><span class="room-code"><?php echo htmlspecialchars($room['room_code']); ?></span></td>
                                <td><?php echo $room['duration']; ?> minutes</td>
                                <td><?php echo date('M j, Y H:i', strtotime($room['created_at'])); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p>You haven't created any exam rooms yet. Use the form above to create your first exam room.</p>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
