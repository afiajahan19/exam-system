<?php
require_once '../includes/auth.php';
require_once '../includes/question.php';

// Only teachers can access this page
requireRole('teacher');

$message = '';
$message_type = '';
$ocr_text = '';
$parsed_questions = [];
$exam_rooms = getTeacherExamRoomsForQuestions($_SESSION['user_id']);

// Handle image upload and OCR processing
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['upload_image'])) {
        // Handle image upload
        $validation = validateImageUpload($_FILES['question_image']);
        
        if (!$validation['valid']) {
            $message = $validation['error'];
            $message_type = 'error';
        } else {
            $upload_result = saveUploadedImage($_FILES['question_image']);
            
            if ($upload_result['success']) {
                // Extract text from image
                $ocr_text = extractTextFromImage($upload_result['filepath']);
                $parsed_questions = parseOCRTextToQuestions($ocr_text);
                
                $message = 'Image uploaded and processed successfully! ' . count($parsed_questions) . ' questions detected.';
                $message_type = 'success';
                
                // Store for form submission
                $_SESSION['ocr_text'] = $ocr_text;
                $_SESSION['parsed_questions'] = $parsed_questions;
            } else {
                $message = $upload_result['error'];
                $message_type = 'error';
            }
        }
    }
    
    if (isset($_POST['save_questions'])) {
        // Save parsed questions to database
        $exam_room_id = intval($_POST['exam_room_id']);
        
        if (empty($exam_room_id)) {
            $message = 'Please select an exam room.';
            $message_type = 'error';
        } elseif (isset($_SESSION['ocr_text'])) {
            $created_questions = createQuestionsFromOCR($exam_room_id, $_SESSION['ocr_text']);
            
            if (!empty($created_questions)) {
                $message = 'Successfully created ' . count($created_questions) . ' questions!';
                $message_type = 'success';
                
                // Clear session data
                unset($_SESSION['ocr_text']);
                unset($_SESSION['parsed_questions']);
                $ocr_text = '';
                $parsed_questions = [];
            } else {
                $message = 'Error saving questions. Please try again.';
                $message_type = 'error';
            }
        } else {
            $message = 'Please upload an image first.';
            $message_type = 'error';
        }
    }
    
    if (isset($_POST['clear_data'])) {
        // Clear uploaded data
        unset($_SESSION['ocr_text']);
        unset($_SESSION['parsed_questions']);
        $ocr_text = '';
        $parsed_questions = [];
        $message = 'Data cleared. Please upload a new image.';
        $message_type = 'info';
    }
}

// Load data from session if available
if (isset($_SESSION['ocr_text'])) {
    $ocr_text = $_SESSION['ocr_text'];
    $parsed_questions = $_SESSION['parsed_questions'] ?? [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>OCR Question Upload - Teacher Dashboard</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .ocr-container {
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
        .form-group select {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }
        
        .file-upload {
            border: 2px dashed #ddd;
            border-radius: 8px;
            padding: 40px;
            text-align: center;
            background: #f8f9fa;
            cursor: pointer;
            transition: border-color 0.3s ease;
        }
        
        .file-upload:hover {
            border-color: #007bff;
        }
        
        .file-upload input[type="file"] {
            display: none;
        }
        
        .file-upload-icon {
            font-size: 48px;
            color: #666;
            margin-bottom: 15px;
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
            margin-right: 10px;
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
        
        .btn-secondary {
            background: #6c757d;
        }
        
        .btn-secondary:hover {
            background: #5a6268;
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
        
        .message.info {
            background: #d1ecf1;
            color: #0c5460;
            border: 1px solid #bee5eb;
        }
        
        .ocr-result {
            background: #f8f9fa;
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
        }
        
        .ocr-text {
            font-family: monospace;
            white-space: pre-wrap;
            background: white;
            padding: 15px;
            border-radius: 4px;
            border: 1px solid #ddd;
            max-height: 300px;
            overflow-y: auto;
        }
        
        .parsed-questions {
            margin-top: 30px;
        }
        
        .question-preview {
            background: white;
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 15px;
        }
        
        .question-header {
            font-weight: 600;
            color: #007bff;
            margin-bottom: 10px;
        }
        
        .question-text {
            margin-bottom: 15px;
        }
        
        .question-options {
            margin-left: 20px;
        }
        
        .question-options li {
            margin-bottom: 5px;
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
        
        .step-indicator {
            display: flex;
            justify-content: space-between;
            margin-bottom: 30px;
        }
        
        .step {
            flex: 1;
            text-align: center;
            padding: 15px;
            background: #f8f9fa;
            border: 1px solid #ddd;
            position: relative;
        }
        
        .step:first-child {
            border-radius: 8px 0 0 8px;
        }
        
        .step:last-child {
            border-radius: 0 8px 8px 0;
        }
        
        .step.active {
            background: #007bff;
            color: white;
            border-color: #007bff;
        }
        
        .step.completed {
            background: #28a745;
            color: white;
            border-color: #28a745;
        }
        
        .step-number {
            display: inline-block;
            width: 30px;
            height: 30px;
            line-height: 30px;
            border-radius: 50%;
            background: rgba(255,255,255,0.3);
            margin-bottom: 10px;
            font-weight: 600;
        }
        
        .step.active .step-number,
        .step.completed .step-number {
            background: rgba(255,255,255,0.9);
            color: #333;
        }
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin: 20px 0;
        }
        
        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 8px;
            text-align: center;
            border: 1px solid #ddd;
        }
        
        .stat-number {
            font-size: 32px;
            font-weight: 700;
            color: #007bff;
        }
        
        .stat-label {
            color: #666;
            margin-top: 5px;
        }
    </style>
</head>
<body>
    <div class="ocr-container">
        <a href="../dashboard/index.php" class="back-link">← Back to Dashboard</a>
        
        <h1>OCR Question Upload</h1>
        
        <!-- Step Indicator -->
        <div class="step-indicator">
            <div class="step <?php echo (!empty($ocr_text)) ? 'completed' : 'active'; ?>">
                <div class="step-number">1</div>
                <div>Upload Image</div>
            </div>
            <div class="step <?php echo (!empty($parsed_questions)) ? 'completed' : ''; echo (empty($ocr_text) && !empty($parsed_questions)) ? 'active' : ''; ?>">
                <div class="step-number">2</div>
                <div>Review Questions</div>
            </div>
            <div class="step <?php echo (!empty($parsed_questions) && isset($_POST['save_questions'])) ? 'completed' : ''; echo (!empty($parsed_questions)) ? 'active' : ''; ?>">
                <div class="step-number">3</div>
                <div>Save to Exam Room</div>
            </div>
        </div>
        
        <?php if ($message): ?>
            <div class="message <?php echo $message_type; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>
        
        <!-- Step 1: Upload Image -->
        <div class="section-card">
            <h2>Step 1: Upload Question Paper Image</h2>
            
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="upload_image" value="1">
                
                <div class="form-group">
                    <div class="file-upload" onclick="document.getElementById('question_image').click()">
                        <div class="file-upload-icon">📷</div>
                        <h3>Click to upload image</h3>
                        <p>Supported formats: JPG, PNG, GIF (Max 5MB)</p>
                        <input type="file" id="question_image" name="question_image" accept="image/*" onchange="previewImage(this)">
                    </div>
                    <div id="image_preview" style="margin-top: 15px;"></div>
                </div>
                
                <button type="submit" class="btn btn-success">Process Image with OCR</button>
            </form>
        </div>
        
        <!-- Step 2: Review Parsed Questions -->
        <?php if (!empty($parsed_questions)): ?>
            <div class="section-card">
                <h2>Step 2: Review Parsed Questions</h2>
                
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-number"><?php echo count($parsed_questions); ?></div>
                        <div class="stat-label">Total Questions</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number"><?php echo count(array_filter($parsed_questions, function($q) { return $q['type'] === 'mcq'; })); ?></div>
                        <div class="stat-label">MCQ Questions</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number"><?php echo count(array_filter($parsed_questions, function($q) { return $q['type'] === 'descriptive'; })); ?></div>
                        <div class="stat-label">Descriptive Questions</div>
                    </div>
                </div>
                
                <div class="parsed-questions">
                    <h3>Extracted Questions Preview</h3>
                    
                    <?php foreach ($parsed_questions as $index => $question): ?>
                        <div class="question-preview">
                            <div class="question-header">Question <?php echo $index + 1; ?> - <?php echo ucfirst($question['type']); ?></div>
                            <div class="question-text"><?php echo htmlspecialchars($question['text']); ?></div>
                            
                            <?php if ($question['type'] === 'mcq' && !empty($question['options'])): ?>
                                <div class="question-options">
                                    <ul>
                                        <?php foreach ($question['options'] as $option): ?>
                                            <li class="<?php echo $option['is_correct'] ? 'correct-answer' : ''; ?>">
                                                <?php echo htmlspecialchars($option['text']); ?>
                                                <?php if ($option['is_correct']): ?>
                                                    <span class="correct-answer">✓</span>
                                                <?php endif; ?>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <div class="ocr-result">
                    <h4>Raw OCR Text</h4>
                    <div class="ocr-text"><?php echo htmlspecialchars($ocr_text); ?></div>
                </div>
            </div>
            
            <!-- Step 3: Save to Exam Room -->
            <div class="section-card">
                <h2>Step 3: Save Questions to Exam Room</h2>
                
                <form method="POST">
                    <input type="hidden" name="save_questions" value="1">
                    
                    <div class="form-group">
                        <label for="exam_room_id">Select Exam Room</label>
                        <select id="exam_room_id" name="exam_room_id" required>
                            <option value="">-- Select Exam Room --</option>
                            <?php foreach ($exam_rooms as $room): ?>
                                <option value="<?php echo $room['id']; ?>">
                                    <?php echo htmlspecialchars($room['title'] . ' - ' . $room['subject']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <button type="submit" class="btn btn-success">Save All Questions</button>
                    <button type="submit" name="clear_data" class="btn btn-secondary" onclick="return confirm('This will clear all uploaded data. Are you sure?')">Clear & Start Over</button>
                </form>
            </div>
        <?php endif; ?>
    </div>
    
    <script>
        function previewImage(input) {
            const preview = document.getElementById('image_preview');
            
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                
                reader.onload = function(e) {
                    preview.innerHTML = `
                        <img src="${e.target.result}" style="max-width: 100%; max-height: 300px; border-radius: 8px; border: 1px solid #ddd;">
                        <p style="margin-top: 10px; color: #666;">File: ${input.files[0].name} (${(input.files[0].size / 1024).toFixed(2)} KB)</p>
                    `;
                };
                
                reader.readAsDataURL(input.files[0]);
            }
        }
        
        // Auto-scroll to results after upload
        <?php if (!empty($parsed_questions)): ?>
            document.addEventListener('DOMContentLoaded', function() {
                const resultsSection = document.querySelector('.parsed-questions');
                if (resultsSection) {
                    resultsSection.scrollIntoView({ behavior: 'smooth' });
                }
            });
        <?php endif; ?>
    </script>
</body>
</html>
