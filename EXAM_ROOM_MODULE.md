# Exam Room Module Documentation

## Overview

The Exam Room Module is a core feature of the Online Examination System that allows teachers to create exam rooms and students to join them using unique room codes.

## Features

### Teacher Features
- **Create Exam Rooms**: Teachers can create exam rooms with title, subject, and duration
- **View Created Rooms**: Teachers can see all exam rooms they've created with details
- **Unique Room Codes**: Automatic generation of unique room codes (EXAM + random numbers)

### Student Features
- **Join Exam Rooms**: Students can join exam rooms by entering the room code
- **Room Validation**: System validates room codes and displays exam details
- **Exam Details**: Students can view exam information before starting

### Security Features
- **Role-Based Access**: Only teachers can create rooms, only students can join
- **Session Protection**: All routes are protected using authentication middleware
- **Input Validation**: Server-side validation for all form inputs

## Database Schema

### exam_rooms Table

| Field | Type | Description |
|-------|------|-------------|
| id | INT AUTO_INCREMENT PRIMARY KEY | Unique identifier |
| title | VARCHAR(255) NOT NULL | Exam title |
| subject | VARCHAR(100) NOT NULL | Subject name |
| room_code | VARCHAR(20) NOT NULL UNIQUE | Unique room code |
| created_by | INT NOT NULL | Foreign key to users.id |
| duration | INT NOT NULL | Duration in minutes |
| created_at | TIMESTAMP DEFAULT CURRENT_TIMESTAMP | Creation timestamp |

## File Structure

```
exam-system/
├── includes/
│   ├── auth.php              # Updated with role-based functions
│   └── exam_room.php         # Exam room operations
├── teacher/
│   └── exam_rooms.php        # Teacher exam room management
├── student/
│   └── join_exam.php         # Student join exam interface
├── database_setup.sql        # Updated with exam_rooms table
└── assets/
    └── css/
        └── style.css         # Updated with new styles
```

## Installation & Setup

### 1. Database Setup

1. Run the updated `database_setup.sql` in phpMyAdmin
2. This will create the `exam_rooms` table with proper foreign key constraints

### 2. File Upload

All files are already in place. The module integrates seamlessly with the existing authentication system.

### 3. Access the Module

- **Teachers**: Login → Dashboard → "Manage Exam Rooms"
- **Students**: Login → Dashboard → "Join Exam Room"

## Usage Instructions

### For Teachers

1. **Creating Exam Rooms**
   - Navigate to "Manage Exam Rooms" from the dashboard
   - Fill in the exam title, subject, and duration
   - Click "Create Exam Room"
   - The system will generate a unique room code automatically

2. **Viewing Created Rooms**
   - All created rooms are displayed in a table
   - Room codes are shown in a highlighted format
   - Creation timestamp is displayed for each room

### For Students

1. **Joining Exam Rooms**
   - Navigate to "Join Exam Room" from the dashboard
   - Enter the room code provided by the teacher
   - Click "Join Exam Room"

2. **Viewing Exam Details**
   - Upon successful join, exam details are displayed
   - Students can see exam title, subject, duration, and teacher information
   - "Start Exam" button (placeholder for future exam functionality)

## Technical Implementation

### Room Code Generation

```php
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
```

### Access Control

```php
// Only teachers can create rooms
requireRole('teacher');

// Only students can join rooms  
requireRole('student');
```

### Database Operations

- **Prepared Statements**: All database queries use prepared statements to prevent SQL injection
- **Foreign Key Constraints**: Proper relationships between users and exam rooms
- **Indexing**: Optimized indexes for room_code and created_by fields

## Security Considerations

1. **Input Validation**: All user inputs are validated and sanitized
2. **SQL Injection Prevention**: Prepared statements used throughout
3. **XSS Prevention**: Output escaped with `htmlspecialchars()`
4. **Session Security**: Proper session validation and role checking
5. **Access Control**: Role-based access control enforced at all levels

## Future Enhancements

1. **Exam Functionality**: Actual exam taking interface
2. **Time Tracking**: Automatic timer for exam duration
3. **Question Management**: Integration with question bank
4. **Results Storage**: Saving and displaying exam results
5. **Room Status**: Active/inactive room management
6. **Student Tracking**: Track which students joined each room

## Troubleshooting

### Common Issues

1. **Room Code Not Working**
   - Verify the room code is entered correctly (case-insensitive)
   - Check if the room exists in the database
   - Ensure the teacher has created the room successfully

2. **Access Denied Errors**
   - Verify user role is correct in the database
   - Check if user is properly logged in
   - Ensure session is active

3. **Database Errors**
   - Check if the exam_rooms table exists
   - Verify foreign key constraints are properly set
   - Ensure database connection is working

## API Reference

### Core Functions

| Function | Parameters | Returns | Description |
|----------|------------|---------|-------------|
| `generateRoomCode()` | none | string | Generates unique room code |
| `createExamRoom()` | title, subject, duration, created_by | boolean | Creates new exam room |
| `getTeacherExamRooms()` | teacher_id | array | Gets teacher's exam rooms |
| `getExamRoomByCode()` | room_code | array|null | Gets room details by code |
| `roomCodeExists()` | room_code | boolean | Checks if room code exists |

### Authentication Functions

| Function | Parameters | Returns | Description |
|----------|------------|---------|-------------|
| `hasRole()` | role | boolean | Checks if user has specific role |
| `requireRole()` | role | void | Enforces role-based access |

## Testing

### Test Cases

1. **Teacher Creates Room**
   - Login as teacher
   - Navigate to exam rooms
   - Create room with valid data
   - Verify room appears in list

2. **Student Joins Room**
   - Login as student
   - Navigate to join exam
   - Enter valid room code
   - Verify room details displayed

3. **Invalid Room Code**
   - Enter non-existent room code
   - Verify error message displayed

4. **Access Control**
   - Try accessing teacher pages as student
   - Try accessing student pages as teacher
   - Verify proper redirects

## Conclusion

The Exam Room Module provides a robust foundation for the online examination system. It implements secure room creation and joining functionality with proper access controls and validation. The module is ready for integration with additional examination features in future development phases.
