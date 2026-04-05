<?php
require_once '../includes/auth.php';
require_once '../includes/exam.php';

// Only students can access this page
requireRole('student');

$message = '';
$message_type = '';
$exam_attempt = null;
$questions = [];
$student_answers = [];

// Handle exam start
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['start_exam'])) {
    $exam_room_id = intval($_POST['exam_room_id']);
    
    // Check if student can attempt exam
    $can_attempt = canAttemptExam($exam_room_id, $_SESSION['user_id']);
    
    if (!$can_attempt['can_attempt']) {
        $message = 'Cannot start exam: ' . $can_attempt['reason'];
        $message_type = 'error';
    } else {
        // Start exam attempt
        $result = startExamAttempt($exam_room_id, $_SESSION['user_id']);
        
        if ($result['success']) {
            // Redirect to exam page with attempt ID
            header("Location: exam.php?attempt_id=" . $result['attempt_id']);
            exit();
        } else {
            $message = $result['error'];
            $message_type = 'error';
        }
    }
}

// Handle answer saving (AJAX)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_answer'])) {
    $attempt_id = intval($_POST['attempt_id']);
    $question_id = intval($_POST['question_id']);
    $selected_option_id = isset($_POST['selected_option_id']) ? intval($_POST['selected_option_id']) : null;
    $descriptive_answer = isset($_POST['descriptive_answer']) ? trim($_POST['descriptive_answer']) : null;
    
    // Verify this is the student's attempt
    $attempt = getExamAttempt($attempt_id, $_SESSION['user_id']);
    if (!$attempt) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Invalid attempt']);
        exit();
    }
    
    // Check if exam is still active
    if (!isExamActive($attempt_id)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Exam time expired']);
        exit();
    }
    
    // Save answer
    $success = saveAnswer($attempt_id, $question_id, $selected_option_id, $descriptive_answer);
    
    header('Content-Type: application/json');
    echo json_encode(['success' => $success]);
    exit();
}

// Handle exam submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_exam'])) {
    $attempt_id = intval($_POST['attempt_id']);
    
    // Verify this is the student's attempt
    $attempt = getExamAttempt($attempt_id, $_SESSION['user_id']);
    if (!$attempt) {
        $message = 'Invalid exam attempt';
        $message_type = 'error';
    } else {
        // Submit exam
        if (submitExam($attempt_id, $_SESSION['user_id'])) {
            header("Location: exam_result.php?attempt_id=" . $attempt_id);
            exit();
        } else {
            $message = 'Failed to submit exam';
            $message_type = 'error';
        }
    }
}

// Load exam attempt if attempt_id is provided
if (isset($_GET['attempt_id'])) {
    $attempt_id = intval($_GET['attempt_id']);
    $exam_attempt = getExamAttempt($attempt_id, $_SESSION['user_id']);
    
    if (!$exam_attempt) {
        $message = 'Exam attempt not found or access denied';
        $message_type = 'error';
    } elseif ($exam_attempt['submitted_at']) {
        $message = 'This exam has already been submitted';
        $message_type = 'info';
        $exam_attempt = null;
    } else {
        // Check if exam is still active
        if (!isExamActive($attempt_id)) {
            // Auto-submit expired exam
            submitExam($attempt_id, $_SESSION['user_id']);
            header("Location: exam_result.php?attempt_id=" . $attempt_id);
            exit();
        }
        
        // Load questions and answers
        $questions = getExamQuestions($exam_attempt['exam_room_id'], true);
        $student_answers = getStudentAnswers($attempt_id);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Exam - Student Dashboard</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .exam-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .exam-header {
            background: white;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .exam-info h1 {
            margin: 0 0 10px 0;
            color: #333;
        }
        
        .exam-meta {
            color: #666;
            margin: 0;
        }
        
        .timer-container {
            text-align: center;
            background: #007bff;
            color: white;
            padding: 15px 25px;
            border-radius: 8px;
            min-width: 150px;
        }
        
        .timer-label {
            font-size: 12px;
            margin-bottom: 5px;
        }
        
        .timer-display {
            font-size: 24px;
            font-weight: bold;
            font-family: monospace;
        }
        
        .timer-display.warning {
            background: #ffc107;
            color: #333;
            padding: 5px 10px;
            border-radius: 4px;
        }
        
        .timer-display.danger {
            background: #dc3545;
            padding: 5px 10px;
            border-radius: 4px;
        }
        
        .questions-container {
            background: white;
            border-radius: 8px;
            padding: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .question-card {
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 25px;
            margin-bottom: 30px;
            background: #fafafa;
        }
        
        .question-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 20px;
        }
        
        .question-number {
            background: #007bff;
            color: white;
            padding: 8px 15px;
            border-radius: 20px;
            font-weight: bold;
            font-size: 14px;
        }
        
        .question-type {
            background: #28a745;
            color: white;
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 12px;
            text-transform: uppercase;
        }
        
        .question-text {
            font-size: 18px;
            line-height: 1.6;
            margin: 20px 0;
            color: #333;
        }
        
        .options-container {
            margin: 20px 0;
        }
        
        .option-item {
            display: flex;
            align-items: center;
            padding: 15px;
            margin-bottom: 10px;
            background: white;
            border: 1px solid #ddd;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .option-item:hover {
            background: #f8f9fa;
            border-color: #007bff;
        }
        
        .option-item.selected {
            background: #e3f2fd;
            border-color: #007bff;
        }
        
        .option-item input[type="radio"] {
            margin-right: 15px;
            width: 18px;
            height: 18px;
        }
        
        .option-text {
            flex: 1;
            font-size: 16px;
            line-height: 1.5;
        }
        
        .descriptive-answer {
            margin: 20px 0;
        }
        
        .descriptive-answer textarea {
            width: 100%;
            min-height: 150px;
            padding: 15px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 16px;
            line-height: 1.5;
            resize: vertical;
        }
        
        .exam-actions {
            position: fixed;
            bottom: 20px;
            right: 20px;
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.15);
            text-align: center;
        }
        
        .submit-btn {
            background: #28a745;
            color: white;
            padding: 15px 30px;
            border: none;
            border-radius: 6px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            transition: background 0.3s ease;
        }
        
        .submit-btn:hover {
            background: #218838;
        }
        
        .submit-btn:disabled {
            background: #6c757d;
            cursor: not-allowed;
        }
        
        .progress-indicator {
            margin-bottom: 15px;
            font-size: 14px;
            color: #666;
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
        
        .message {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        .message.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .message.info {
            background: #d1ecf1;
            color: #0c5460;
            border: 1px solid #bee5eb;
        }
        
        .start-exam-container {
            max-width: 600px;
            margin: 50px auto;
            text-align: center;
        }
        
        .start-exam-card {
            background: white;
            border-radius: 8px;
            padding: 40px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .start-exam-card h2 {
            color: #333;
            margin-bottom: 30px;
        }
        
        .exam-details {
            text-align: left;
            margin: 30px 0;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 6px;
        }
        
        .exam-details h3 {
            margin-top: 0;
            color: #007bff;
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
        
        .btn-start {
            background: #28a745;
            color: white;
            padding: 15px 40px;
            border: none;
            border-radius: 6px;
            font-size: 18px;
            font-weight: bold;
            cursor: pointer;
            transition: background 0.3s ease;
        }
        
        .btn-start:hover {
            background: #218838;
        }
        
        .auto-save-indicator {
            position: fixed;
            top: 20px;
            right: 20px;
            background: #28a745;
            color: white;
            padding: 8px 15px;
            border-radius: 20px;
            font-size: 12px;
            opacity: 0;
            transition: opacity 0.3s ease;
        }
        
        .auto-save-indicator.show {
            opacity: 1;
        }
        
        @media (max-width: 768px) {
            .exam-header {
                flex-direction: column;
                gap: 20px;
                text-align: center;
            }
            
            .exam-actions {
                position: static;
                margin-top: 30px;
                width: 100%;
                box-sizing: border-box;
            }
            
            .question-card {
                padding: 20px;
            }
            
            .question-text {
                font-size: 16px;
            }
        }
    </style>
</head>
<body>
    <div class="exam-container">
        <?php if ($message): ?>
            <div class="message <?php echo $message_type; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>
        
        <?php if ($exam_attempt): ?>
            <!-- Exam in Progress -->
            <a href="../dashboard/index.php" class="back-link">← Back to Dashboard</a>
            
            <div class="exam-header">
                <div class="exam-info">
                    <h1><?php echo htmlspecialchars($exam_attempt['title']); ?></h1>
                    <p class="exam-meta">
                        Subject: <?php echo htmlspecialchars($exam_attempt['subject']); ?> | 
                        Room Code: <?php echo htmlspecialchars($exam_attempt['room_code']); ?>
                    </p>
                </div>
                
                <div class="timer-container">
                    <div class="timer-label">Time Remaining</div>
                    <div id="timer" class="timer-display">--:--</div>
                </div>
            </div>
            
            <div class="questions-container">
                <form id="examForm">
                    <input type="hidden" name="attempt_id" value="<?php echo $exam_attempt['id']; ?>">
                    
                    <?php foreach ($questions as $index => $question): ?>
                        <div class="question-card">
                            <div class="question-header">
                                <span class="question-number">Question <?php echo $index + 1; ?></span>
                                <span class="question-type"><?php echo $question['question_type']; ?></span>
                            </div>
                            
                            <div class="question-text">
                                <?php echo htmlspecialchars($question['question_text']); ?>
                            </div>
                            
                            <?php if ($question['question_type'] === 'mcq'): ?>
                                <div class="options-container">
                                    <?php foreach ($question['options'] as $option): ?>
                                        <div class="option-item <?php echo (isset($student_answers[$question['id']]) && $student_answers[$question['id']]['selected_option_id'] == $option['id']) ? 'selected' : ''; ?>" 
                                             onclick="selectOption(this, <?php echo $question['id']; ?>, <?php echo $option['id']; ?>)">
                                            <input type="radio" 
                                                   name="question_<?php echo $question['id']; ?>" 
                                                   value="<?php echo $option['id']; ?>"
                                                   <?php echo (isset($student_answers[$question['id']]) && $student_answers[$question['id']]['selected_option_id'] == $option['id']) ? 'checked' : ''; ?>>
                                            <span class="option-text"><?php echo htmlspecialchars($option['option_text']); ?></span>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <div class="descriptive-answer">
                                    <textarea name="descriptive_<?php echo $question['id']; ?>" 
                                              placeholder="Enter your answer here..."
                                              onblur="saveDescriptiveAnswer(<?php echo $question['id']; ?>, this.value)"><?php echo isset($student_answers[$question['id']]['descriptive_answer']) ? htmlspecialchars($student_answers[$question['id']]['descriptive_answer']) : ''; ?></textarea>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </form>
            </div>
            
            <div class="exam-actions">
                <div class="progress-indicator">
                    <?php 
                    $answered = count($student_answers);
                    $total = count($questions);
                    echo "Answered: $answered / $total questions";
                    ?>
                </div>
                <form method="POST" onsubmit="return confirm('Are you sure you want to submit your exam? You cannot change your answers after submission.');">
                    <input type="hidden" name="attempt_id" value="<?php echo $exam_attempt['id']; ?>">
                    <input type="hidden" name="submit_exam" value="1">
                    <button type="submit" class="submit-btn">Submit Exam</button>
                </form>
            </div>
            
            <div id="autoSaveIndicator" class="auto-save-indicator">
                Answer saved
            </div>
            
        <?php else: ?>
            <!-- Start Exam Form -->
            <div class="start-exam-container">
                <a href="../dashboard/index.php" class="back-link">← Back to Dashboard</a>
                
                <div class="start-exam-card">
                    <h2>Start Exam</h2>
                    
                    <?php if (isset($_GET['exam_room_id'])): ?>
                        <?php 
                        $exam_room_id = intval($_GET['exam_room_id']);
                        $exam_room = getExamRoomByCode($exam_room_id);
                        $questions = getQuestionsByExamRoom($exam_room_id);
                        ?>
                        
                        <?php if ($exam_room && !empty($questions)): ?>
                            <div class="exam-details">
                                <h3>Exam Information</h3>
                                <div class="detail-row">
                                    <span class="detail-label">Title:</span>
                                    <span class="detail-value"><?php echo htmlspecialchars($exam_room['title']); ?></span>
                                </div>
                                <div class="detail-row">
                                    <span class="detail-label">Subject:</span>
                                    <span class="detail-value"><?php echo htmlspecialchars($exam_room['subject']); ?></span>
                                </div>
                                <div class="detail-row">
                                    <span class="detail-label">Duration:</span>
                                    <span class="detail-value"><?php echo $exam_room['duration']; ?> minutes</span>
                                </div>
                                <div class="detail-row">
                                    <span class="detail-label">Questions:</span>
                                    <span class="detail-value"><?php echo count($questions); ?></span>
                                </div>
                                <div class="detail-row">
                                    <span class="detail-label">Teacher:</span>
                                    <span class="detail-value"><?php echo htmlspecialchars($exam_room['teacher_name']); ?></span>
                                </div>
                            </div>
                            
                            <form method="POST">
                                <input type="hidden" name="exam_room_id" value="<?php echo $exam_room_id; ?>">
                                <input type="hidden" name="start_exam" value="1">
                                <button type="submit" class="btn-start">Start Exam</button>
                            </form>
                        <?php else: ?>
                            <p>Exam not found or no questions available.</p>
                        <?php endif; ?>
                    <?php else: ?>
                        <p>Please access this page through the exam room join page.</p>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
    
    <?php if ($exam_attempt): ?>
    <script>
        // Timer functionality
        let timeRemaining = <?php echo getRemainingTime($exam_attempt['id']); ?>;
        const timerElement = document.getElementById('timer');
        
        function updateTimer() {
            if (timeRemaining <= 0) {
                timerElement.innerHTML = "Time's Up!";
                timerElement.className = 'timer-display danger';
                // Auto-submit exam
                submitExam();
                return;
            }
            
            const hours = Math.floor(timeRemaining / 3600);
            const minutes = Math.floor((timeRemaining % 3600) / 60);
            const seconds = timeRemaining % 60;
            
            let display;
            if (hours > 0) {
                display = `${hours.toString().padStart(2, '0')}:${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
            } else {
                display = `${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
            }
            
            timerElement.innerHTML = display;
            
            // Change color based on time remaining
            if (timeRemaining <= 300) { // 5 minutes
                timerElement.className = 'timer-display danger';
            } else if (timeRemaining <= 600) { // 10 minutes
                timerElement.className = 'timer-display warning';
            }
            
            timeRemaining--;
        }
        
        setInterval(updateTimer, 1000);
        updateTimer(); // Initial call
        
        // Answer saving functions
        function selectOption(element, questionId, optionId) {
            // Update UI
            const options = element.parentElement.querySelectorAll('.option-item');
            options.forEach(opt => opt.classList.remove('selected'));
            element.classList.add('selected');
            
            // Update radio button
            const radio = element.querySelector('input[type="radio"]');
            radio.checked = true;
            
            // Save answer
            saveAnswer(questionId, optionId, null);
        }
        
        function saveDescriptiveAnswer(questionId, answer) {
            saveAnswer(questionId, null, answer);
        }
        
        function saveAnswer(questionId, selectedOptionId, descriptiveAnswer) {
            const formData = new FormData();
            formData.append('save_answer', '1');
            formData.append('attempt_id', <?php echo $exam_attempt['id']; ?>);
            formData.append('question_id', questionId);
            if (selectedOptionId) {
                formData.append('selected_option_id', selectedOptionId);
            }
            if (descriptiveAnswer !== null) {
                formData.append('descriptive_answer', descriptiveAnswer);
            }
            
            fetch('exam.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showAutoSaveIndicator();
                    updateProgress();
                }
            })
            .catch(error => {
                console.error('Error saving answer:', error);
            });
        }
        
        function showAutoSaveIndicator() {
            const indicator = document.getElementById('autoSaveIndicator');
            indicator.classList.add('show');
            setTimeout(() => {
                indicator.classList.remove('show');
            }, 2000);
        }
        
        function updateProgress() {
            // This would update the answered count
            // For simplicity, we'll just reload the page or use AJAX to get the count
        }
        
        function submitExam() {
            const form = document.createElement('form');
            form.method = 'POST';
            form.innerHTML = `
                <input type="hidden" name="attempt_id" value="<?php echo $exam_attempt['id']; ?>">
                <input type="hidden" name="submit_exam" value="1">
            `;
            document.body.appendChild(form);
            form.submit();
        }
        
        // Prevent accidental navigation
        window.addEventListener('beforeunload', function(e) {
            e.preventDefault();
            e.returnValue = '';
        });
        
        // Auto-save all answers periodically
        setInterval(() => {
            // This could be enhanced to save all unsaved answers
            console.log('Auto-save check');
        }, 30000); // Every 30 seconds
    </script>
    <?php endif; ?>
</body>
</html>
