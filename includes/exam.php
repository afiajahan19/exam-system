<?php
require_once 'db.php';
require_once 'question.php';
require_once 'exam_room.php';

// Start exam attempt
function startExamAttempt($exam_room_id, $student_id) {
    global $conn;
    
    try {
        // Check if student already attempted this exam
        $stmt = $conn->prepare("SELECT id FROM exam_attempts WHERE exam_room_id = ? AND student_id = ?");
        $stmt->execute([$exam_room_id, $student_id]);
        $existing = $stmt->fetch();
        
        if ($existing) {
            return ['success' => false, 'error' => 'You have already attempted this exam'];
        }
        
        // Create new attempt
        $stmt = $conn->prepare("INSERT INTO exam_attempts (exam_room_id, student_id) VALUES (?, ?)");
        $stmt->execute([$exam_room_id, $student_id]);
        $attempt_id = $conn->lastInsertId();
        
        return ['success' => true, 'attempt_id' => $attempt_id];
        
    } catch (Exception $e) {
        return ['success' => false, 'error' => 'Failed to start exam attempt'];
    }
}

// Get exam attempt details
function getExamAttempt($attempt_id, $student_id = null) {
    global $conn;
    
    $sql = "SELECT ea.*, er.title, er.subject, er.duration, er.room_code, u.name as student_name 
            FROM exam_attempts ea 
            JOIN exam_rooms er ON ea.exam_room_id = er.id 
            JOIN users u ON ea.student_id = u.id 
            WHERE ea.id = ?";
    
    $params = [$attempt_id];
    
    if ($student_id) {
        $sql .= " AND ea.student_id = ?";
        $params[] = $student_id;
    }
    
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

// Get questions for exam with shuffling
function getExamQuestions($exam_room_id, $shuffle = true) {
    global $conn;
    
    $questions = getQuestionsByExamRoom($exam_room_id);
    
    if ($shuffle) {
        // Shuffle questions
        shuffle($questions);
        
        // Shuffle MCQ options for each question
        foreach ($questions as &$question) {
            if ($question['question_type'] === 'mcq' && isset($question['options'])) {
                // Store original correct answer
                $correct_option_text = '';
                foreach ($question['options'] as $option) {
                    if ($option['is_correct']) {
                        $correct_option_text = $option['option_text'];
                        break;
                    }
                }
                
                // Shuffle options
                shuffle($question['options']);
                
                // Update is_correct flag based on shuffled order
                foreach ($question['options'] as &$option) {
                    $option['is_correct'] = ($option['option_text'] === $correct_option_text);
                }
            }
        }
    }
    
    return $questions;
}

// Save answer
function saveAnswer($attempt_id, $question_id, $selected_option_id = null, $descriptive_answer = null) {
    global $conn;
    
    try {
        // Check if answer already exists
        $stmt = $conn->prepare("SELECT id FROM answers WHERE attempt_id = ? AND question_id = ?");
        $stmt->execute([$attempt_id, $question_id]);
        $existing = $stmt->fetch();
        
        if ($existing) {
            // Update existing answer
            $stmt = $conn->prepare("UPDATE answers SET selected_option_id = ?, descriptive_answer = ? WHERE id = ?");
            $stmt->execute([$selected_option_id, $descriptive_answer, $existing['id']]);
        } else {
            // Insert new answer
            $stmt = $conn->prepare("INSERT INTO answers (attempt_id, question_id, selected_option_id, descriptive_answer) VALUES (?, ?, ?, ?)");
            $stmt->execute([$attempt_id, $question_id, $selected_option_id, $descriptive_answer]);
        }
        
        return true;
        
    } catch (Exception $e) {
        return false;
    }
}

// Get student answers for an attempt
function getStudentAnswers($attempt_id) {
    global $conn;
    
    $stmt = $conn->prepare("SELECT * FROM answers WHERE attempt_id = ?");
    $stmt->execute([$attempt_id]);
    $answers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Convert to associative array by question_id
    $answers_by_question = [];
    foreach ($answers as $answer) {
        $answers_by_question[$answer['question_id']] = $answer;
    }
    
    return $answers_by_question;
}

// Submit exam
function submitExam($attempt_id, $student_id = null) {
    global $conn;
    
    try {
        // Update submitted_at time
        $sql = "UPDATE exam_attempts SET submitted_at = CURRENT_TIMESTAMP WHERE id = ?";
        $params = [$attempt_id];
        
        if ($student_id) {
            $sql .= " AND student_id = ?";
            $params[] = $student_id;
        }
        
        $stmt = $conn->prepare($sql);
        $stmt->execute($params);
        
        // Calculate and save score (placeholder - will be implemented in evaluation module)
        // For now, set score to 0
        $stmt = $conn->prepare("UPDATE exam_attempts SET score = 0 WHERE id = ?");
        $stmt->execute([$attempt_id]);
        
        return true;
        
    } catch (Exception $e) {
        return false;
    }
}

// Check if exam is still active (time not expired)
function isExamActive($attempt_id) {
    global $conn;
    
    $stmt = $conn->prepare("SELECT ea.started_at, er.duration 
                            FROM exam_attempts ea 
                            JOIN exam_rooms er ON ea.exam_room_id = er.id 
                            WHERE ea.id = ? AND ea.submitted_at IS NULL");
    $stmt->execute([$attempt_id]);
    $result = $stmt->fetch();
    
    if (!$result) {
        return false;
    }
    
    $started_time = strtotime($result['started_at']);
    $duration_minutes = $result['duration'];
    $expiry_time = $started_time + ($duration_minutes * 60);
    
    return time() < $expiry_time;
}

// Get remaining time in seconds
function getRemainingTime($attempt_id) {
    global $conn;
    
    $stmt = $conn->prepare("SELECT ea.started_at, er.duration 
                            FROM exam_attempts ea 
                            JOIN exam_rooms er ON ea.exam_room_id = er.id 
                            WHERE ea.id = ? AND ea.submitted_at IS NULL");
    $stmt->execute([$attempt_id]);
    $result = $stmt->fetch();
    
    if (!$result) {
        return 0;
    }
    
    $started_time = strtotime($result['started_at']);
    $duration_minutes = $result['duration'];
    $expiry_time = $started_time + ($duration_minutes * 60);
    $remaining = $expiry_time - time();
    
    return max(0, $remaining);
}

// Get student's exam attempts
function getStudentExamAttempts($student_id) {
    global $conn;
    
    $stmt = $conn->prepare("SELECT ea.*, er.title, er.subject, er.room_code 
                            FROM exam_attempts ea 
                            JOIN exam_rooms er ON ea.exam_room_id = er.id 
                            WHERE ea.student_id = ? 
                            ORDER BY ea.started_at DESC");
    $stmt->execute([$student_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Check if student can attempt exam
function canAttemptExam($exam_room_id, $student_id) {
    global $conn;
    
    // Check if student already attempted
    $stmt = $conn->prepare("SELECT id FROM exam_attempts WHERE exam_room_id = ? AND student_id = ?");
    $stmt->execute([$exam_room_id, $student_id]);
    $existing = $stmt->fetch();
    
    if ($existing) {
        return ['can_attempt' => false, 'reason' => 'already_attempted'];
    }
    
    // Check if exam room exists and has questions
    $exam_room = getExamRoomByCode($exam_room_id);
    if (!$exam_room) {
        return ['can_attempt' => false, 'reason' => 'exam_not_found'];
    }
    
    $questions = getQuestionsByExamRoom($exam_room['id']);
    if (empty($questions)) {
        return ['can_attempt' => false, 'reason' => 'no_questions'];
    }
    
    return ['can_attempt' => true, 'exam_room' => $exam_room];
}

// Auto-submit expired exams
function autoSubmitExpiredExams() {
    global $conn;
    
    $stmt = $conn->prepare("SELECT ea.id, ea.student_id 
                            FROM exam_attempts ea 
                            JOIN exam_rooms er ON ea.exam_room_id = er.id 
                            WHERE ea.submitted_at IS NULL 
                            AND TIMESTAMPADD(MINUTE, er.duration, ea.started_at) < CURRENT_TIMESTAMP");
    $stmt->execute();
    $expired_attempts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $submitted_count = 0;
    foreach ($expired_attempts as $attempt) {
        if (submitExam($attempt['id'])) {
            $submitted_count++;
        }
    }
    
    return $submitted_count;
}

// Format time for display
function formatExamTime($seconds) {
    $hours = floor($seconds / 3600);
    $minutes = floor(($seconds % 3600) / 60);
    $secs = $seconds % 60;
    
    if ($hours > 0) {
        return sprintf("%02d:%02d:%02d", $hours, $minutes, $secs);
    } else {
        return sprintf("%02d:%02d", $minutes, $secs);
    }
}

// Get exam statistics
function getExamStatistics($exam_room_id) {
    global $conn;
    
    $stats = [];
    
    // Total attempts
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM exam_attempts WHERE exam_room_id = ?");
    $stmt->execute([$exam_room_id]);
    $stats['total_attempts'] = $stmt->fetch()['total'];
    
    // Submitted attempts
    $stmt = $conn->prepare("SELECT COUNT(*) as submitted FROM exam_attempts WHERE exam_room_id = ? AND submitted_at IS NOT NULL");
    $stmt->execute([$exam_room_id]);
    $stats['submitted_attempts'] = $stmt->fetch()['submitted'];
    
    // Average score (placeholder - will be calculated in evaluation module)
    $stmt = $conn->prepare("SELECT AVG(score) as avg_score FROM exam_attempts WHERE exam_room_id = ? AND score IS NOT NULL");
    $stmt->execute([$exam_room_id]);
    $avg_score = $stmt->fetch()['avg_score'];
    $stats['average_score'] = $avg_score ? round($avg_score, 2) : 0;
    
    return $stats;
}
?>
