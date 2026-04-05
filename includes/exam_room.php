<?php
require_once 'db.php';

// Generate unique room code
function generateRoomCode() {
    global $conn;
    
    do {
        $code = 'EXAM' . str_pad(rand(100, 99999), 5, '0', STR_PAD_LEFT);
        
        // Check if code already exists
        $stmt = $conn->prepare("SELECT id FROM exam_rooms WHERE room_code = ?");
        $stmt->execute([$code]);
        $exists = $stmt->fetch();
        
    } while ($exists);
    
    return $code;
}

// Create exam room
function createExamRoom($title, $subject, $duration, $created_by) {
    global $conn;
    
    $room_code = generateRoomCode();
    
    $stmt = $conn->prepare("INSERT INTO exam_rooms (title, subject, room_code, created_by, duration) VALUES (?, ?, ?, ?, ?)");
    return $stmt->execute([$title, $subject, $room_code, $created_by, $duration]);
}

// Get exam rooms created by a teacher
function getTeacherExamRooms($teacher_id) {
    global $conn;
    
    $stmt = $conn->prepare("SELECT * FROM exam_rooms WHERE created_by = ? ORDER BY created_at DESC");
    $stmt->execute([$teacher_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Get exam room by code
function getExamRoomByCode($room_code) {
    global $conn;
    
    $stmt = $conn->prepare("SELECT er.*, u.name as teacher_name FROM exam_rooms er JOIN users u ON er.created_by = u.id WHERE er.room_code = ?");
    $stmt->execute([$room_code]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

// Check if room code exists
function roomCodeExists($room_code) {
    global $conn;
    
    $stmt = $conn->prepare("SELECT id FROM exam_rooms WHERE room_code = ?");
    $stmt->execute([$room_code]);
    return $stmt->fetch() !== false;
}
?>
