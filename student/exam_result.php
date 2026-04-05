<?php
require_once '../includes/auth.php';
require_once '../includes/exam.php';

// Only students can access this page
requireRole('student');

$exam_attempt = null;
$questions = [];
$student_answers = [];
$score = 0;

// Load exam attempt if attempt_id is provided
if (isset($_GET['attempt_id'])) {
    $attempt_id = intval($_GET['attempt_id']);
    $exam_attempt = getExamAttempt($attempt_id, $_SESSION['user_id']);
    
    if (!$exam_attempt) {
        $message = 'Exam result not found or access denied';
        $message_type = 'error';
    } elseif (!$exam_attempt['submitted_at']) {
        header("Location: exam.php?attempt_id=" . $attempt_id);
        exit();
    } else {
        // Load questions and answers for result display
        $questions = getExamQuestions($exam_attempt['exam_room_id'], false); // No shuffling for results
        $student_answers = getStudentAnswers($attempt_id);
        
        // Calculate score (basic calculation - will be enhanced in evaluation module)
        $total_questions = count($questions);
        $correct_answers = 0;
        
        foreach ($questions as $question) {
            if ($question['question_type'] === 'mcq') {
                $student_answer = isset($student_answers[$question['id']]) ? $student_answers[$question['id']] : null;
                if ($student_answer && $student_answer['selected_option_id']) {
                    // Check if selected option is correct
                    foreach ($question['options'] as $option) {
                        if ($option['id'] == $student_answer['selected_option_id'] && $option['is_correct']) {
                            $correct_answers++;
                            break;
                        }
                    }
                }
            }
            // Descriptive questions will be evaluated manually
        }
        
        $score = $total_questions > 0 ? round(($correct_answers / $total_questions) * 100, 2) : 0;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Exam Result - Student Dashboard</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .result-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .result-header {
            background: white;
            border-radius: 8px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            text-align: center;
        }
        
        .result-header h1 {
            color: #333;
            margin-bottom: 20px;
        }
        
        .score-display {
            font-size: 48px;
            font-weight: bold;
            color: #28a745;
            margin: 20px 0;
        }
        
        .score-display.fair {
            color: #ffc107;
        }
        
        .score-display.poor {
            color: #dc3545;
        }
        
        .exam-info {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin: 30px 0;
        }
        
        .info-card {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            text-align: center;
        }
        
        .info-label {
            font-size: 14px;
            color: #666;
            margin-bottom: 5px;
        }
        
        .info-value {
            font-size: 18px;
            font-weight: bold;
            color: #333;
        }
        
        .questions-review {
            background: white;
            border-radius: 8px;
            padding: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .questions-review h2 {
            color: #333;
            margin-bottom: 30px;
        }
        
        .question-result {
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 25px;
            margin-bottom: 20px;
            background: #fafafa;
        }
        
        .question-result.correct {
            border-left: 5px solid #28a745;
        }
        
        .question-result.incorrect {
            border-left: 5px solid #dc3545;
        }
        
        .question-result.descriptive {
            border-left: 5px solid #17a2b8;
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
        
        .question-status {
            padding: 6px 12px;
            border-radius: 15px;
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
        }
        
        .question-status.correct {
            background: #d4edda;
            color: #155724;
        }
        
        .question-status.incorrect {
            background: #f8d7da;
            color: #721c24;
        }
        
        .question-status.pending {
            background: #d1ecf1;
            color: #0c5460;
        }
        
        .question-text {
            font-size: 18px;
            line-height: 1.6;
            margin: 20px 0;
            color: #333;
        }
        
        .options-review {
            margin: 20px 0;
        }
        
        .option-review {
            display: flex;
            align-items: center;
            padding: 12px;
            margin-bottom: 8px;
            border-radius: 6px;
            background: white;
            border: 1px solid #ddd;
        }
        
        .option-review.correct {
            background: #d4edda;
            border-color: #28a745;
        }
        
        .option-review.selected {
            background: #e3f2fd;
            border-color: #007bff;
        }
        
        .option-review.selected.correct {
            background: #d4edda;
            border-color: #28a745;
        }
        
        .option-review.selected.incorrect {
            background: #f8d7da;
            border-color: #dc3545;
        }
        
        .option-indicator {
            width: 20px;
            height: 20px;
            border-radius: 50%;
            margin-right: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            font-weight: bold;
            color: white;
        }
        
        .option-indicator.correct {
            background: #28a745;
        }
        
        .option-indicator.incorrect {
            background: #dc3545;
        }
        
        .option-indicator.neutral {
            background: #6c757d;
        }
        
        .option-text {
            flex: 1;
            font-size: 16px;
        }
        
        .descriptive-answer {
            margin: 20px 0;
            padding: 20px;
            background: white;
            border: 1px solid #ddd;
            border-radius: 6px;
        }
        
        .answer-label {
            font-weight: bold;
            color: #666;
            margin-bottom: 10px;
        }
        
        .answer-text {
            font-size: 16px;
            line-height: 1.6;
            color: #333;
            white-space: pre-wrap;
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
        
        .actions {
            text-align: center;
            margin-top: 30px;
        }
        
        .btn {
            background: #007bff;
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 6px;
            text-decoration: none;
            display: inline-block;
            margin: 0 10px;
            font-size: 16px;
        }
        
        .btn:hover {
            background: #0056b3;
        }
        
        .btn-success {
            background: #28a745;
        }
        
        .btn-success:hover {
            background: #218838;
        }
        
        .summary-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 20px;
            margin: 30px 0;
        }
        
        .stat-item {
            text-align: center;
        }
        
        .stat-number {
            font-size: 36px;
            font-weight: bold;
            color: #007bff;
        }
        
        .stat-label {
            color: #666;
            margin-top: 5px;
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
        
        @media (max-width: 768px) {
            .result-header {
                padding: 20px;
            }
            
            .score-display {
                font-size: 36px;
            }
            
            .exam-info {
                grid-template-columns: 1fr;
            }
            
            .question-result {
                padding: 20px;
            }
            
            .question-text {
                font-size: 16px;
            }
        }
    </style>
</head>
<body>
    <div class="result-container">
        <?php if ($message): ?>
            <div class="message <?php echo $message_type; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>
        
        <?php if ($exam_attempt): ?>
            <a href="../dashboard/index.php" class="back-link">← Back to Dashboard</a>
            
            <div class="result-header">
                <h1>Exam Result</h1>
                
                <div class="score-display <?php echo $score >= 70 ? '' : ($score >= 50 ? 'fair' : 'poor'); ?>">
                    <?php echo $score; ?>%
                </div>
                
                <div class="exam-info">
                    <div class="info-card">
                        <div class="info-label">Exam Title</div>
                        <div class="info-value"><?php echo htmlspecialchars($exam_attempt['title']); ?></div>
                    </div>
                    <div class="info-card">
                        <div class="info-label">Subject</div>
                        <div class="info-value"><?php echo htmlspecialchars($exam_attempt['subject']); ?></div>
                    </div>
                    <div class="info-card">
                        <div class="info-label">Duration</div>
                        <div class="info-value"><?php echo $exam_attempt['duration']; ?> minutes</div>
                    </div>
                    <div class="info-card">
                        <div class="info-label">Submitted</div>
                        <div class="info-value"><?php echo date('M j, Y H:i', strtotime($exam_attempt['submitted_at'])); ?></div>
                    </div>
                </div>
                
                <div class="summary-stats">
                    <div class="stat-item">
                        <div class="stat-number"><?php echo count($questions); ?></div>
                        <div class="stat-label">Total Questions</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-number"><?php echo count($student_answers); ?></div>
                        <div class="stat-label">Attempted</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-number">
                            <?php 
                            $correct = 0;
                            foreach ($questions as $question) {
                                if ($question['question_type'] === 'mcq') {
                                    $student_answer = isset($student_answers[$question['id']]) ? $student_answers[$question['id']] : null;
                                    if ($student_answer && $student_answer['selected_option_id']) {
                                        foreach ($question['options'] as $option) {
                                            if ($option['id'] == $student_answer['selected_option_id'] && $option['is_correct']) {
                                                $correct++;
                                                break;
                                            }
                                        }
                                    }
                                }
                            }
                            echo $correct;
                            ?>
                        </div>
                        <div class="stat-label">Correct</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-number">
                            <?php 
                            $incorrect = 0;
                            foreach ($questions as $question) {
                                if ($question['question_type'] === 'mcq') {
                                    $student_answer = isset($student_answers[$question['id']]) ? $student_answers[$question['id']] : null;
                                    if ($student_answer && $student_answer['selected_option_id']) {
                                        $is_correct = false;
                                        foreach ($question['options'] as $option) {
                                            if ($option['id'] == $student_answer['selected_option_id'] && $option['is_correct']) {
                                                $is_correct = true;
                                                break;
                                            }
                                        }
                                        if (!$is_correct) $incorrect++;
                                    }
                                }
                            }
                            echo $incorrect;
                            ?>
                        </div>
                        <div class="stat-label">Incorrect</div>
                    </div>
                </div>
            </div>
            
            <div class="questions-review">
                <h2>Question Review</h2>
                
                <?php foreach ($questions as $index => $question): ?>
                    <?php 
                    $student_answer = isset($student_answers[$question['id']]) ? $student_answers[$question['id']] : null;
                    $is_correct = false;
                    
                    if ($question['question_type'] === 'mcq' && $student_answer && $student_answer['selected_option_id']) {
                        foreach ($question['options'] as $option) {
                            if ($option['id'] == $student_answer['selected_option_id'] && $option['is_correct']) {
                                $is_correct = true;
                                break;
                            }
                        }
                    }
                    
                    $status_class = $question['question_type'] === 'descriptive' ? 'descriptive' : ($is_correct ? 'correct' : 'incorrect');
                    $status_text = $question['question_type'] === 'descriptive' ? 'Pending Evaluation' : ($is_correct ? 'Correct' : 'Incorrect');
                    ?>
                    
                    <div class="question-result <?php echo $status_class; ?>">
                        <div class="question-header">
                            <span class="question-number">Question <?php echo $index + 1; ?></span>
                            <span class="question-status <?php echo $question['question_type'] === 'descriptive' ? 'pending' : ($is_correct ? 'correct' : 'incorrect'); ?>">
                                <?php echo $status_text; ?>
                            </span>
                        </div>
                        
                        <div class="question-text">
                            <?php echo htmlspecialchars($question['question_text']); ?>
                        </div>
                        
                        <?php if ($question['question_type'] === 'mcq'): ?>
                            <div class="options-review">
                                <?php foreach ($question['options'] as $option): ?>
                                    <?php 
                                    $is_selected = ($student_answer && $student_answer['selected_option_id'] == $option['id']);
                                    $option_class = '';
                                    $indicator_class = 'neutral';
                                    $indicator_text = '';
                                    
                                    if ($option['is_correct']) {
                                        $option_class = 'correct';
                                        $indicator_class = 'correct';
                                        $indicator_text = '✓';
                                    }
                                    
                                    if ($is_selected) {
                                        $option_class .= ' selected';
                                        if (!$option['is_correct']) {
                                            $option_class .= ' incorrect';
                                            $indicator_class = 'incorrect';
                                            $indicator_text = '✗';
                                        }
                                    }
                                    ?>
                                    
                                    <div class="option-review <?php echo $option_class; ?>">
                                        <div class="option-indicator <?php echo $indicator_class; ?>">
                                            <?php echo $indicator_text; ?>
                                        </div>
                                        <span class="option-text"><?php echo htmlspecialchars($option['option_text']); ?></span>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="descriptive-answer">
                                <div class="answer-label">Your Answer:</div>
                                <div class="answer-text">
                                    <?php echo ($student_answer && !empty($student_answer['descriptive_answer'])) ? htmlspecialchars($student_answer['descriptive_answer']) : 'No answer provided'; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <div class="actions">
                <a href="../dashboard/index.php" class="btn">Back to Dashboard</a>
                <a href="join_exam.php" class="btn btn-success">Join Another Exam</a>
            </div>
            
        <?php else: ?>
            <div class="result-header">
                <h1>Result Not Found</h1>
                <p>The exam result you're looking for is not available or you don't have permission to view it.</p>
                <div class="actions">
                    <a href="../dashboard/index.php" class="btn">Back to Dashboard</a>
                </div>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
