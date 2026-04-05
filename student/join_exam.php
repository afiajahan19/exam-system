<?php
require_once '../includes/auth.php';
require_once '../includes/exam_room.php';

// Only students can access this page
requireRole('student');

$message = '';
$message_type = '';
$room_details = null;

// Handle form submission for joining exam room
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['join_room'])) {
    $room_code = trim($_POST['room_code']);
    
    if (empty($room_code)) {
        $message = 'Please enter a room code.';
        $message_type = 'error';
    } else {
        $room_details = getExamRoomByCode($room_code);
        
        if ($room_details) {
            $message = 'Successfully joined the exam room!';
            $message_type = 'success';
        } else {
            $message = 'Invalid room code. Please check and try again.';
            $message_type = 'error';
            $room_details = null;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Join Exam Room - Student Dashboard</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .join-exam-container {
            max-width: 800px;
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
            text-transform: uppercase;
            font-family: monospace;
        }
        
        .btn {
            background: #28a745;
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
            background: #218838;
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
        
        .room-details {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-top: 20px;
        }
        
        .room-details h3 {
            margin-top: 0;
            color: #28a745;
        }
        
        .detail-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid #dee2e6;
        }
        
        .detail-row:last-child {
            border-bottom: none;
        }
        
        .detail-label {
            font-weight: 600;
            color: #495057;
        }
        
        .detail-value {
            color: #212529;
        }
        
        .room-code-display {
            font-family: monospace;
            background: #e9ecef;
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
        
        .start-exam-btn {
            background: #007bff;
            margin-top: 20px;
        }
        
        .start-exam-btn:hover {
            background: #0056b3;
        }
    </style>
</head>
<body>
    <div class="join-exam-container">
        <a href="../dashboard/index.php" class="back-link">← Back to Dashboard</a>
        
        <h1>Join Exam Room</h1>
        
        <!-- Join Exam Room Section -->
        <div class="section-card">
            <h2>Enter Room Code</h2>
            
            <?php if ($message): ?>
                <div class="message <?php echo $message_type; ?>">
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <input type="hidden" name="join_room" value="1">
                
                <div class="form-group">
                    <label for="room_code">Room Code</label>
                    <input type="text" id="room_code" name="room_code" required 
                           placeholder="e.g., EXAM12345" maxlength="20">
                </div>
                
                <button type="submit" class="btn">Join Exam Room</button>
            </form>
        </div>
        
        <!-- Room Details Section -->
        <?php if ($room_details): ?>
            <div class="section-card">
                <div class="room-details">
                    <h3>Exam Room Details</h3>
                    
                    <div class="detail-row">
                        <span class="detail-label">Exam Title:</span>
                        <span class="detail-value"><?php echo htmlspecialchars($room_details['title']); ?></span>
                    </div>
                    
                    <div class="detail-row">
                        <span class="detail-label">Subject:</span>
                        <span class="detail-value"><?php echo htmlspecialchars($room_details['subject']); ?></span>
                    </div>
                    
                    <div class="detail-row">
                        <span class="detail-label">Room Code:</span>
                        <span class="detail-value room-code-display"><?php echo htmlspecialchars($room_details['room_code']); ?></span>
                    </div>
                    
                    <div class="detail-row">
                        <span class="detail-label">Duration:</span>
                        <span class="detail-value"><?php echo $room_details['duration']; ?> minutes</span>
                    </div>
                    
                    <div class="detail-row">
                        <span class="detail-label">Created by:</span>
                        <span class="detail-value"><?php echo htmlspecialchars($room_details['teacher_name']); ?></span>
                    </div>
                    
                    <div class="detail-row">
                        <span class="detail-label">Created on:</span>
                        <span class="detail-value"><?php echo date('M j, Y H:i', strtotime($room_details['created_at'])); ?></span>
                    </div>
                    
                    <button class="btn start-exam-btn" onclick="alert('Exam functionality will be implemented in the next module.')">
                        Start Exam
                    </button>
                </div>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
