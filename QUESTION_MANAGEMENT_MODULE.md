# Question Management System with OCR Support - Documentation

## Overview

The Question Management System is a comprehensive module that allows teachers to create and manage exam questions through both manual entry and OCR (Optical Character Recognition) processing of uploaded images. This module supports both Multiple Choice Questions (MCQ) and Descriptive questions.

## Features

### Teacher Features
- **Manual Question Entry**: Add questions individually with full control over formatting
- **OCR Question Upload**: Upload question paper images and automatically extract questions
- **Question Management**: View, edit, and delete questions in exam rooms
- **Dual Question Types**: Support for MCQ and Descriptive questions
- **Batch Processing**: Process multiple questions from a single image upload

### OCR Features
- **Image Processing**: Upload JPG, PNG, GIF files (max 5MB)
- **Text Extraction**: Extract text from uploaded images using OCR
- **Smart Parsing**: Automatically detect question types and answer options
- **Question Detection**: Identify numbered questions and multiple choice options
- **Preview System**: Review parsed questions before saving to database

### Security & Access Control
- **Role-Based Access**: Only teachers can add/manage questions
- **Input Validation**: Comprehensive validation for all inputs
- **SQL Injection Prevention**: Prepared statements throughout
- **File Upload Security**: File type and size validation

## Database Schema

### questions Table

| Field | Type | Description |
|-------|------|-------------|
| id | INT AUTO_INCREMENT PRIMARY KEY | Unique identifier |
| exam_room_id | INT NOT NULL | Foreign key to exam_rooms.id |
| question_text | TEXT NOT NULL | Full question text |
| question_type | ENUM('mcq','descriptive') | Type of question |
| created_at | TIMESTAMP DEFAULT CURRENT_TIMESTAMP | Creation timestamp |

### options Table (for MCQ questions)

| Field | Type | Description |
|-------|------|-------------|
| id | INT AUTO_INCREMENT PRIMARY KEY | Unique identifier |
| question_id | INT NOT NULL | Foreign key to questions.id |
| option_text | TEXT NOT NULL | Option text content |
| is_correct | BOOLEAN DEFAULT FALSE | Marks correct answer |
| created_at | TIMESTAMP DEFAULT CURRENT_TIMESTAMP | Creation timestamp |

## File Structure

```
exam-system/
├── includes/
│   ├── question.php          # Question management functions
│   └── exam_room.php         # Exam room functions (updated)
├── teacher/
│   ├── add_question.php      # Manual question entry interface
│   ├── upload_ocr.php         # OCR upload and processing
│   └── exam_rooms.php         # Updated with question counts
├── database_setup.sql        # Updated with new tables
└── assets/
    └── css/
        └── style.css          # Enhanced styling
```

## Installation & Setup

### 1. Database Setup

1. Run the updated `database_setup.sql` in phpMyAdmin
2. This will create the `questions` and `options` tables with proper foreign key constraints

### 2. File Upload Directory

The system automatically creates an `uploads/` directory for storing uploaded images. Ensure proper write permissions.

### 3. OCR Configuration (Optional)

For production use, you may want to integrate real OCR:

```php
// Replace the placeholder function in includes/question.php
function extractTextFromImage($image_path) {
    // Install Tesseract OCR on your server
    // exec("tesseract $image_path stdout", $output);
    // return implode("\n", $output);
}
```

## Usage Instructions

### Manual Question Entry

1. **Navigate**: Dashboard → "Add Questions"
2. **Select Exam Room**: Choose the target exam room from dropdown
3. **Choose Question Type**: Select MCQ or Descriptive
4. **Enter Question**: Type the question text
5. **Add Options (MCQ)**: 
   - Enter 4 answer options
   - Select the correct answer using radio buttons
6. **Submit**: Click "Add Question"

### OCR Question Upload

1. **Navigate**: Dashboard → "OCR Upload"
2. **Upload Image**: Click to upload question paper image
3. **Process OCR**: System extracts text and parses questions
4. **Review Results**: 
   - View extracted questions
   - Check question types and options
   - Review raw OCR text if needed
5. **Select Exam Room**: Choose target exam room
6. **Save Questions**: Click "Save All Questions"

### Question Management

1. **View Questions**: Use the "View Questions" tab in add_question.php
2. **Question List**: See all questions in selected exam room
3. **Delete Questions**: Remove unwanted questions with confirmation

## Technical Implementation

### Question Creation Process

```php
function createQuestion($exam_room_id, $question_text, $question_type, $options = []) {
    global $conn;
    
    try {
        $conn->beginTransaction();
        
        // Insert question
        $stmt = $conn->prepare("INSERT INTO questions (exam_room_id, question_text, question_type) VALUES (?, ?, ?)");
        $stmt->execute([$exam_room_id, $question_text, $question_type]);
        $question_id = $conn->lastInsertId();
        
        // Insert MCQ options if applicable
        if ($question_type === 'mcq' && !empty($options)) {
            foreach ($options as $option) {
                $stmt = $conn->prepare("INSERT INTO options (question_id, option_text, is_correct) VALUES (?, ?, ?)");
                $stmt->execute([$question_id, $option['text'], $option['is_correct']]);
            }
        }
        
        $conn->commit();
        return $question_id;
        
    } catch (Exception $e) {
        $conn->rollback();
        return false;
    }
}
```

### OCR Parsing Logic

The OCR parser uses regex patterns to identify:

- **Question Numbers**: `/^(\d+)\.\s*(.+)$/` - Matches "1. Question text"
- **MCQ Options**: `/^[A-D]\)\s*(.+)$/` - Matches "A) Option text"
- **Question Types**: Determines MCQ vs Descriptive based on presence of options

### File Upload Validation

```php
function validateImageUpload($file) {
    $allowed_types = ['image/jpeg', 'image/png', 'image/jpg', 'image/gif'];
    $max_size = 5 * 1024 * 1024; // 5MB
    
    // Validate file existence, size, and type
    if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) {
        return ['valid' => false, 'error' => 'File upload error'];
    }
    
    if ($file['size'] > $max_size) {
        return ['valid' => false, 'error' => 'File size too large (max 5MB)'];
    }
    
    if (!in_array($file['type'], $allowed_types)) {
        return ['valid' => false, 'error' => 'Invalid file type'];
    }
    
    return ['valid' => true];
}
```

## OCR Processing Flow

1. **Image Upload**: User uploads question paper image
2. **Validation**: File type and size validation
3. **Text Extraction**: OCR processing to extract raw text
4. **Text Parsing**: 
   - Split text by question numbers
   - Identify MCQ options using patterns
   - Classify questions as MCQ or Descriptive
5. **Preview Display**: Show parsed questions for review
6. **Database Storage**: Save questions to selected exam room

## Question Types Support

### MCQ (Multiple Choice Questions)
- **Format**: Question text + 4 answer options
- **Options**: A), B), C), D) pattern detection
- **Correct Answer**: Radio button selection for manual entry
- **OCR Detection**: Automatically identifies option structure

### Descriptive Questions
- **Format**: Open-ended questions without predefined options
- **No Options**: Simple text-based questions
- **OCR Detection**: Identified when no option patterns found

## Security Considerations

### Input Validation
- **Question Text**: Required field, length validation
- **Options**: Required for MCQ, at least one correct answer
- **File Upload**: Type, size, and content validation

### Database Security
- **Prepared Statements**: All queries use parameterized statements
- **Transaction Management**: Proper rollback on errors
- **Foreign Key Constraints**: Data integrity enforcement

### Access Control
- **Role Checking**: `requireRole('teacher')` on all pages
- **Ownership Verification**: Teachers can only manage their exam rooms
- **Session Security**: Proper session validation

## UI/UX Features

### Tab-Based Interface
- **Add Question Tab**: Manual question entry form
- **View Questions Tab**: List and manage existing questions
- **OCR Upload Tab**: Dedicated OCR processing interface

### Interactive Elements
- **Dynamic Forms**: Show/hide MCQ options based on question type
- **Real-time Preview**: Image preview before upload
- **Progress Indicators**: Step-by-step OCR process
- **Confirmation Dialogs**: Delete confirmations for safety

### Responsive Design
- **Mobile-Friendly**: Works on all device sizes
- **Modern Styling**: Gradient buttons and card-based layouts
- **Visual Feedback**: Success/error messages with color coding

## Performance Considerations

### Database Optimization
- **Indexing**: Proper indexes on foreign keys and frequently queried fields
- **Query Optimization**: Efficient joins for question retrieval
- **Transaction Management**: Minimal database locks

### File Handling
- **Size Limits**: 5MB maximum file size
- **Storage Management**: Unique filenames to prevent conflicts
- **Cleanup**: Consider scheduled cleanup of old upload files

## Troubleshooting

### Common Issues

1. **OCR Not Working**
   - Check if Tesseract OCR is installed (for production)
   - Verify file upload permissions
   - Ensure image quality is sufficient for OCR

2. **Questions Not Saving**
   - Verify database connection
   - Check foreign key constraints
   - Ensure exam room exists

3. **File Upload Errors**
   - Check PHP upload limits in php.ini
   - Verify directory permissions for uploads folder
   - Ensure file type is supported

4. **Parsing Issues**
   - Review OCR text quality
   - Check question numbering format
   - Verify option letter format (A), B), etc.)

### Debug Mode

Enable error reporting for development:

```php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
```

## Future Enhancements

### OCR Improvements
- **Multiple OCR Engines**: Support for Tesseract, Google Vision, etc.
- **Image Preprocessing**: Enhance image quality before OCR
- **Confidence Scoring**: Show OCR confidence levels
- **Manual Correction**: Allow editing of OCR text before parsing

### Question Management
- **Question Categories**: Organize questions by topics
- **Difficulty Levels**: Assign difficulty ratings
- **Question Banks**: Reuse questions across multiple exams
- **Import/Export**: CSV/Excel question import/export

### Advanced Features
- **Question Randomization**: Random question order in exams
- **Time Limits**: Per-question time allocation
- **Rich Text Editor**: Enhanced question formatting
- **Media Support**: Include images/videos in questions

## API Reference

### Core Functions

| Function | Parameters | Returns | Description |
|----------|------------|---------|-------------|
| `createQuestion()` | exam_room_id, question_text, question_type, options | int|false | Creates new question |
| `getQuestionsByExamRoom()` | exam_room_id | array | Gets all questions for exam room |
| `deleteQuestion()` | question_id | boolean | Deletes question and options |
| `createQuestionsFromOCR()` | exam_room_id, ocr_text | array | Creates multiple questions from OCR |

### OCR Functions

| Function | Parameters | Returns | Description |
|----------|------------|---------|-------------|
| `extractTextFromImage()` | image_path | string | Extracts text from image |
| `parseOCRTextToQuestions()` | ocr_text | array | Parses text into question structure |
| `validateImageUpload()` | file | array | Validates uploaded image |

### Utility Functions

| Function | Parameters | Returns | Description |
|----------|------------|---------|-------------|
| `getTeacherExamRoomsForQuestions()` | teacher_id | array | Gets teacher's exam rooms |
| `saveUploadedImage()` | file | array | Saves uploaded file |
| `generateSampleOCRText()` | none | string | Generates sample OCR text for demo |

## Testing

### Test Cases

1. **Manual MCQ Creation**
   - Create MCQ with 4 options
   - Select correct answer
   - Verify database storage

2. **Manual Descriptive Creation**
   - Create descriptive question
   - Verify no options stored
   - Check text formatting

3. **OCR Upload Process**
   - Upload valid image file
   - Verify OCR text extraction
   - Check question parsing accuracy

4. **Question Management**
   - View questions in exam room
   - Delete individual questions
   - Verify question counts update

5. **Access Control**
   - Test teacher access to question pages
   - Verify student access restrictions
   - Check ownership validation

## Conclusion

The Question Management System provides a robust foundation for creating and managing exam questions with both manual entry and automated OCR processing. The module integrates seamlessly with the existing exam room system and provides comprehensive features for teachers to build question banks efficiently.

The OCR functionality, while implemented with a placeholder for demo purposes, is structured to easily integrate with real OCR engines for production use. The system maintains high security standards and provides excellent user experience through modern UI design and intuitive workflows.
