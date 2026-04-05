<?php
require_once '../includes/auth.php';
require_once '../includes/question.php';

// Only teachers can access this page
requireRole('teacher');

$message = '';
$message_type = '';
$exam_rooms = getTeacherExamRoomsForQuestions($_SESSION['user_id']);
$questions = [];

// Handle form submission for adding questions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_question'])) {
        $exam_room_id = intval($_POST['exam_room_id']);
        $question_text = trim($_POST['question_text']);
        $question_type = $_POST['question_type'];
        
        if (empty($exam_room_id) || empty($question_text)) {
            $message = 'Please fill in all required fields.';
            $message_type = 'error';
        } else {
            $options = [];
            
            if ($question_type === 'mcq') {
                // Collect MCQ options
                for ($i = 1; $i <= 4; $i++) {
                    $option_text = trim($_POST["option_$i"]);
                    $is_correct = isset($_POST['correct_answer']) && $_POST['correct_answer'] == $i;
                    
                    if (!empty($option_text)) {
                        $options[] = [
                            'text' => $option_text,
                            'is_correct' => $is_correct
                        ];
                    }
                }
                
                // Check if at least one correct answer is selected
                $has_correct = false;
                foreach ($options as $option) {
                    if ($option['is_correct']) {
                        $has_correct = true;
                        break;
                    }
                }
                
                if (!$has_correct) {
                    $message = 'Please select the correct answer for MCQ questions.';
                    $message_type = 'error';
                }
            }
            
            if (empty($message)) {
                $question_id = createQuestion($exam_room_id, $question_text, $question_type, $options);
                
                if ($question_id) {
                    $message = 'Question added successfully!';
                    $message_type = 'success';
                    
                    // Clear form
                    $_POST['question_text'] = '';
                    $_POST['option_1'] = '';
                    $_POST['option_2'] = '';
                    $_POST['option_3'] = '';
                    $_POST['option_4'] = '';
                    $_POST['correct_answer'] = '';
                } else {
                    $message = 'Error adding question. Please try again.';
                    $message_type = 'error';
                }
            }
        }
    }
    
    if (isset($_POST['delete_question'])) {
        $question_id = intval($_POST['delete_question']);
        
        if (deleteQuestion($question_id)) {
            $message = 'Question deleted successfully!';
            $message_type = 'success';
        } else {
            $message = 'Error deleting question. Please try again.';
            $message_type = 'error';
        }
    }
}

// Load questions for selected exam room
if (isset($_GET['exam_room_id']) && !empty($_GET['exam_room_id'])) {
    $exam_room_id = intval($_GET['exam_room_id']);
    $questions = getQuestionsByExamRoom($exam_room_id);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Questions - Teacher Dashboard</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .question-container {
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
        
        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }
        
        .form-group textarea {
            min-height: 100px;
            resize: vertical;
        }
        
        .options-container {
            border: 1px solid #ddd;
            border-radius: 4px;
            padding: 15px;
            margin-top: 15px;
            background: #f8f9fa;
        }
        
        .option-group {
            display: flex;
            align-items: center;
            margin-bottom: 15px;
            gap: 10px;
        }
        
        .option-group input[type="radio"] {
            width: auto;
            margin: 0;
        }
        
        .option-group input[type="text"] {
            flex: 1;
        }
        
        .question-type-toggle {
            display: flex;
            gap: 20px;
            margin-bottom: 20px;
        }
        
        .question-type-toggle label {
            display: flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
        }
        
        .question-type-toggle input[type="radio"] {
            width: auto;
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
        
        .btn-success {
            background: #28a745;
        }
        
        .btn-success:hover {
            background: #218838;
        }
        
        .btn-danger {
            background: #dc3545;
        }
        
        .btn-danger:hover {
            background: #c82333;
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
        
        .questions-list {
            margin-top: 30px;
        }
        
        .question-item {
            background: #f8f9fa;
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 15px;
        }
        
        .question-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 15px;
        }
        
        .question-meta {
            font-size: 12px;
            color: #666;
            margin-bottom: 10px;
        }
        
        .question-text {
            font-weight: 600;
            margin-bottom: 15px;
        }
        
        .options-list {
            list-style: none;
            padding: 0;
        }
        
        .options-list li {
            padding: 8px 0;
            border-bottom: 1px solid #eee;
        }
        
        .options-list li:last-child {
            border-bottom: none;
        }
        
        .correct-answer {
            color: #28a745;
            font-weight: 600;
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
        
        .nav-tabs {
            display: flex;
            border-bottom: 1px solid #ddd;
            margin-bottom: 20px;
        }
        
        .nav-tab {
            padding: 12px 24px;
            background: #f8f9fa;
            border: 1px solid #ddd;
            border-bottom: none;
            cursor: pointer;
            text-decoration: none;
            color: #333;
        }
        
        .nav-tab.active {
            background: white;
            border-bottom: 1px solid white;
            margin-bottom: -1px;
            color: #007bff;
            font-weight: 600;
        }
        
        .tab-content {
            display: none;
        }
        
        .tab-content.active {
            display: block;
        }
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
    </style>
</head>
<body>
    <div class="question-container">
        <a href="../dashboard/index.php" class="back-link">← Back to Dashboard</a>
        
        <h1>Question Management</h1>
        
        <!-- Navigation Tabs -->
        <div class="nav-tabs">
            <a href="#add-question" class="nav-tab active" onclick="showTab('add-question')">Add Question</a>
            <a href="#view-questions" class="nav-tab" onclick="showTab('view-questions')">View Questions</a>
            <a href="../teacher/upload_ocr.php" class="nav-tab">OCR Upload</a>
        </div>
        
        <!-- Add Question Tab -->
        <div id="add-question" class="tab-content active">
            <div class="section-card">
                <h2>Add New Question</h2>
                
                <?php if ($message): ?>
                    <div class="message <?php echo $message_type; ?>">
                        <?php echo htmlspecialchars($message); ?>
                    </div>
                <?php endif; ?>
                
                <form method="POST" action="">
                    <input type="hidden" name="add_question" value="1">
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="exam_room_id">Select Exam Room</label>
                            <select id="exam_room_id" name="exam_room_id" required onchange="loadQuestions(this.value)">
                                <option value="">-- Select Exam Room --</option>
                                <?php foreach ($exam_rooms as $room): ?>
                                    <option value="<?php echo $room['id']; ?>" <?php echo (isset($_POST['exam_room_id']) && $_POST['exam_room_id'] == $room['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($room['title'] . ' - ' . $room['subject']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label>Question Type</label>
                            <div class="question-type-toggle">
                                <label>
                                    <input type="radio" name="question_type" value="mcq" <?php echo (isset($_POST['question_type']) && $_POST['question_type'] === 'mcq') ? 'checked' : 'checked'; ?> onchange="toggleQuestionType(this.value)">
                                    MCQ (Multiple Choice)
                                </label>
                                <label>
                                    <input type="radio" name="question_type" value="descriptive" <?php echo (isset($_POST['question_type']) && $_POST['question_type'] === 'descriptive') ? 'checked' : ''; ?> onchange="toggleQuestionType(this.value)">
                                    Descriptive
                                </label>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="question_text">Question Text</label>
                        <textarea id="question_text" name="question_text" required placeholder="Enter your question here..."><?php echo isset($_POST['question_text']) ? htmlspecialchars($_POST['question_text']) : ''; ?></textarea>
                    </div>
                    
                    <!-- MCQ Options -->
                    <div id="mcq-options" class="options-container" style="display: <?php echo (!isset($_POST['question_type']) || $_POST['question_type'] === 'mcq') ? 'block' : 'none'; ?>;">
                        <h4>Answer Options</h4>
                        
                        <?php for ($i = 1; $i <= 4; $i++): ?>
                            <div class="option-group">
                                <input type="radio" name="correct_answer" value="<?php echo $i; ?>" <?php echo (isset($_POST['correct_answer']) && $_POST['correct_answer'] == $i) ? 'checked' : ''; ?>>
                                <label>Option <?php echo $i; ?>:</label>
                                <input type="text" name="option_<?php echo $i; ?>" placeholder="Enter option <?php echo $i; ?>" value="<?php echo isset($_POST["option_$i"]) ? htmlspecialchars($_POST["option_$i"]) : ''; ?>">
                            </div>
                        <?php endfor; ?>
                        
                        <small style="color: #666;">Select the radio button next to the correct answer.</small>
                    </div>
                    
                    <button type="submit" class="btn btn-success">Add Question</button>
                </form>
            </div>
        </div>
        
        <!-- View Questions Tab -->
        <div id="view-questions" class="tab-content">
            <div class="section-card">
                <h2>Questions in Exam Room</h2>
                
                <?php if (isset($_GET['exam_room_id']) && !empty($_GET['exam_room_id'])): ?>
                    <?php if (!empty($questions)): ?>
                        <div class="questions-list">
                            <?php foreach ($questions as $question): ?>
                                <div class="question-item">
                                    <div class="question-header">
                                        <div>
                                            <div class="question-meta">
                                                Type: <?php echo ucfirst($question['question_type']); ?> | 
                                                Created: <?php echo date('M j, Y H:i', strtotime($question['created_at'])); ?>
                                            </div>
                                            <div class="question-text">
                                                <?php echo htmlspecialchars($question['question_text']); ?>
                                            </div>
                                            
                                            <?php if ($question['question_type'] === 'mcq' && isset($question['options'])): ?>
                                                <ul class="options-list">
                                                    <?php foreach ($question['options'] as $option): ?>
                                                        <li class="<?php echo $option['is_correct'] ? 'correct-answer' : ''; ?>">
                                                            <?php echo htmlspecialchars($option['option_text']); ?>
                                                            <?php if ($option['is_correct']): ?>
                                                                <span class="correct-answer">✓ Correct Answer</span>
                                                            <?php endif; ?>
                                                        </li>
                                                    <?php endforeach; ?>
                                                </ul>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <form method="POST" action="" onsubmit="return confirm('Are you sure you want to delete this question?');">
                                            <input type="hidden" name="delete_question" value="<?php echo $question['id']; ?>">
                                            <button type="submit" class="btn btn-danger" style="padding: 8px 16px; font-size: 12px;">Delete</button>
                                        </form>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p>No questions found for this exam room. Add your first question using the form above.</p>
                    <?php endif; ?>
                <?php else: ?>
                    <p>Please select an exam room from the "Add Question" tab to view questions.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <script>
        function showTab(tabId) {
            // Hide all tabs
            document.querySelectorAll('.tab-content').forEach(tab => {
                tab.classList.remove('active');
            });
            
            // Remove active class from all nav tabs
            document.querySelectorAll('.nav-tab').forEach(tab => {
                tab.classList.remove('active');
            });
            
            // Show selected tab
            document.getElementById(tabId).classList.add('active');
            document.querySelector(`[href="#${tabId}"]`).classList.add('active');
        }
        
        function toggleQuestionType(type) {
            const mcqOptions = document.getElementById('mcq-options');
            mcqOptions.style.display = type === 'mcq' ? 'block' : 'none';
        }
        
        function loadQuestions(examRoomId) {
            if (examRoomId) {
                window.location.href = '?exam_room_id=' + examRoomId + '#view-questions';
            }
        }
        
        // Handle delete question
        document.addEventListener('DOMContentLoaded', function() {
            const deleteForms = document.querySelectorAll('form');
            deleteForms.forEach(form => {
                if (form.querySelector('input[name="delete_question"]')) {
                    form.addEventListener('submit', function(e) {
                        if (!confirm('Are you sure you want to delete this question?')) {
                            e.preventDefault();
                        }
                    });
                }
            });
        });
    </script>
</body>
</html>
