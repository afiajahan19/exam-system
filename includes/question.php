<?php
require_once 'db.php';
require_once 'exam_room.php';

// Create a new question
function createQuestion($exam_room_id, $question_text, $question_type, $options = []) {
    global $conn;
    
    try {
        // Start transaction
        $conn->beginTransaction();
        
        // Insert question
        $stmt = $conn->prepare("INSERT INTO questions (exam_room_id, question_text, question_type) VALUES (?, ?, ?)");
        $stmt->execute([$exam_room_id, $question_text, $question_type]);
        $question_id = $conn->lastInsertId();
        
        // If it's an MCQ question, insert options
        if ($question_type === 'mcq' && !empty($options)) {
            foreach ($options as $option) {
                $stmt = $conn->prepare("INSERT INTO options (question_id, option_text, is_correct) VALUES (?, ?, ?)");
                $stmt->execute([$question_id, $option['text'], $option['is_correct']]);
            }
        }
        
        // Commit transaction
        $conn->commit();
        return $question_id;
        
    } catch (Exception $e) {
        // Rollback on error
        $conn->rollback();
        return false;
    }
}

// Get questions for a specific exam room
function getQuestionsByExamRoom($exam_room_id) {
    global $conn;
    
    $stmt = $conn->prepare("SELECT * FROM questions WHERE exam_room_id = ? ORDER BY created_at ASC");
    $stmt->execute([$exam_room_id]);
    $questions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get options for MCQ questions
    foreach ($questions as &$question) {
        if ($question['question_type'] === 'mcq') {
            $stmt = $conn->prepare("SELECT * FROM options WHERE question_id = ? ORDER BY id ASC");
            $stmt->execute([$question['id']]);
            $question['options'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
    }
    
    return $questions;
}

// Get exam rooms created by a teacher (for dropdown)
function getTeacherExamRoomsForQuestions($teacher_id) {
    global $conn;
    
    $stmt = $conn->prepare("SELECT id, title, subject FROM exam_rooms WHERE created_by = ? ORDER BY created_at DESC");
    $stmt->execute([$teacher_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Delete a question and its options
function deleteQuestion($question_id) {
    global $conn;
    
    try {
        // Start transaction
        $conn->beginTransaction();
        
        // Delete options first (foreign key will handle this, but being explicit)
        $stmt = $conn->prepare("DELETE FROM options WHERE question_id = ?");
        $stmt->execute([$question_id]);
        
        // Delete question
        $stmt = $conn->prepare("DELETE FROM questions WHERE id = ?");
        $stmt->execute([$question_id]);
        
        // Commit transaction
        $conn->commit();
        return true;
        
    } catch (Exception $e) {
        // Rollback on error
        $conn->rollback();
        return false;
    }
}

// OCR Text Processing Functions
function extractTextFromImage($image_path) {
    // For this implementation, we'll use a placeholder function
    // In a real implementation, you would use Tesseract OCR or similar
    
    // Check if Tesseract is available (placeholder)
    // exec("tesseract $image_path stdout", $output);
    // return implode("\n", $output);
    
    // For demo purposes, return sample text
    return generateSampleOCRText();
}

function generateSampleOCRText() {
    return "1. What is the capital of France?
A) London
B) Berlin
C) Paris
D) Madrid

2. Which of the following is a programming language?
A) HTML
B) Java
C) CSS
D) XML

3. Explain the concept of database normalization.

4. What is the output of 2 + 2 * 3?
A) 10
B) 8
C) 12
D) 6";
}

function parseOCRTextToQuestions($ocr_text) {
    $questions = [];
    $lines = explode("\n", $ocr_text);
    $current_question = null;
    $current_options = [];
    $question_number = 0;
    
    foreach ($lines as $line) {
        $line = trim($line);
        if (empty($line)) continue;
        
        // Detect question start (numbered patterns)
        if (preg_match('/^(\d+)\.\s*(.+)$/', $line, $matches)) {
            // Save previous question if exists
            if ($current_question !== null) {
                $questions[] = [
                    'text' => $current_question,
                    'type' => count($current_options) > 0 ? 'mcq' : 'descriptive',
                    'options' => $current_options
                ];
            }
            
            // Start new question
            $current_question = $matches[2];
            $current_options = [];
            $question_number = $matches[1];
        }
        // Detect MCQ options (A), B), C), D) patterns)
        elseif (preg_match('/^[A-D]\)\s*(.+)$/', $line, $matches)) {
            $option_letter = $line[0];
            $option_text = $matches[1];
            $is_correct = ($option_letter === 'C'); // Assume C is correct for demo
            
            $current_options[] = [
                'text' => $option_text,
                'is_correct' => $is_correct
            ];
        }
        // Handle descriptive questions (continuation of question text)
        elseif ($current_question !== null && !preg_match('/^[A-D]\)/', $line)) {
            // If we have options collected, this might be a new question without number
            if (count($current_options) > 0) {
                // Save previous question
                $questions[] = [
                    'text' => $current_question,
                    'type' => 'mcq',
                    'options' => $current_options
                ];
                $current_question = $line;
                $current_options = [];
            } else {
                // Continue current question text
                $current_question .= ' ' . $line;
            }
        }
    }
    
    // Save last question
    if ($current_question !== null) {
        $questions[] = [
            'text' => $current_question,
            'type' => count($current_options) > 0 ? 'mcq' : 'descriptive',
            'options' => $current_options
        ];
    }
    
    return $questions;
}

function createQuestionsFromOCR($exam_room_id, $ocr_text) {
    $parsed_questions = parseOCRTextToQuestions($ocr_text);
    $created_questions = [];
    
    foreach ($parsed_questions as $question_data) {
        $question_id = createQuestion(
            $exam_room_id,
            $question_data['text'],
            $question_data['type'],
            $question_data['options']
        );
        
        if ($question_id) {
            $created_questions[] = [
                'id' => $question_id,
                'text' => $question_data['text'],
                'type' => $question_data['type']
            ];
        }
    }
    
    return $created_questions;
}

// Validate uploaded image
function validateImageUpload($file) {
    $allowed_types = ['image/jpeg', 'image/png', 'image/jpg', 'image/gif'];
    $max_size = 5 * 1024 * 1024; // 5MB
    
    if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) {
        return ['valid' => false, 'error' => 'File upload error'];
    }
    
    if ($file['size'] > $max_size) {
        return ['valid' => false, 'error' => 'File size too large (max 5MB)'];
    }
    
    if (!in_array($file['type'], $allowed_types)) {
        return ['valid' => false, 'error' => 'Invalid file type. Only JPG, PNG, GIF allowed'];
    }
    
    return ['valid' => true];
}

// Save uploaded image
function saveUploadedImage($file) {
    $upload_dir = 'uploads/';
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }
    
    $filename = uniqid() . '_' . basename($file['name']);
    $filepath = $upload_dir . $filename;
    
    if (move_uploaded_file($file['tmp_name'], $filepath)) {
        return ['success' => true, 'filepath' => $filepath];
    }
    
    return ['success' => false, 'error' => 'Failed to save file'];
}
?>
