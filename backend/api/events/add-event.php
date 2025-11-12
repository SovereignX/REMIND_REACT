<?php
/**
 * API - Add an event
 * POST /api/events/add-event.php
 * 
 * Required JSON body:
 * {
 *   "weekday_index": 0,       // 0=Monday, 1=Tuesday, ..., 6=Sunday
 *   "start_time": "09:00",
 *   "event_title": "Meeting",
 *   "event_color": "#b4a7d6",
 *   "duration_hours": 1.5
 * }
 */

require_once '../../config/cors.php';
require_once '../../config/database.php';
require_once '../../config/auth.php';
require_once '../../utils/days.php';
require_once '../../utils/validation.php';

header("Content-Type: application/json; charset=UTF-8");

/**
 * Helper function for JSON responses
 */
function sendResponse($success, $data = [], $httpCode = 200) {
    http_response_code($httpCode);
    echo json_encode(array_merge(['success' => $success], $data));
    exit;
}

/**
 * Data validation function
 */
function validateEventData($data) {
    $errors = [];
    
    // Validate weekday_index
    if (!isset($data['weekday_index'])) {
        $errors[] = "Weekday index is required";
    } elseif (!isValidDayIndex($data['weekday_index'])) {
        $errors[] = "Weekday index must be between 0 and 6 (0=Monday, 6=Sunday)";
    }
    
    if (empty($data['start_time']) || !preg_match('/^([0-1]?[0-9]|2[0-3]):[0-5][0-9]$/', $data['start_time'])) {
        $errors[] = "Start time is required and must be in HH:MM format";
    }
    
    if (empty($data['event_title']) || strlen($data['event_title']) > 255) {
        $errors[] = "Event title is required (max 255 characters)";
    }
    
    if (empty($data['event_color']) || !preg_match('/^#[0-9A-Fa-f]{6}$/', $data['event_color'])) {
        $errors[] = "Color must be in hexadecimal format (#RRGGBB)";
    }
    
    if (!isset($data['duration_hours']) || $data['duration_hours'] <= 0 || $data['duration_hours'] > 24) {
        $errors[] = "Duration must be between 0.5 and 24 hours";
    }
    
    return $errors;
}

// Get JSON data
$json = file_get_contents("php://input");
$data = json_decode($json, true);

// Check JSON validity
if (json_last_error() !== JSON_ERROR_NONE) {
    sendResponse(false, ['error' => 'Invalid JSON format'], 400);
}

// Get authenticated user
$userId = getUserId();
if (!$userId) {
    sendResponse(false, ['error' => 'Authentication required'], 401);
}

// Validate data
$errors = validateEventData($data);
if (!empty($errors)) {
    sendResponse(false, ['errors' => $errors], 400);
}

// ============================================
// CLEAN DATA (XSS SECURITY)
// ============================================

$weekdayIndex = intval($data['weekday_index']);
$startTime = trim($data['start_time']);

// ✅ IMPORTANT: Clean title to prevent XSS
$eventTitle = cleanEventTitle($data['event_title']);

// Check that cleaned title is not empty
if (empty($eventTitle)) {
    sendResponse(false, ['error' => 'Title cannot be empty or contain only HTML tags'], 400);
}

// Check for remaining dangerous patterns
if (containsDangerousChars($eventTitle)) {
    sendResponse(false, ['error' => 'Title contains unauthorized elements'], 400);
}

$eventColor = trim($data['event_color']);
$durationHours = floatval($data['duration_hours']);

// ============================================
// INSERT INTO DATABASE
// ============================================

try {
    $db = getConnection();
    
    $req = $db->prepare(
        "INSERT INTO events (user_id, weekday_index, start_time, event_title, event_color, duration_hours) 
         VALUES (:user_id, :weekday_index, :start_time, :event_title, :event_color, :duration_hours)"
    );
    
    $req->bindParam(':user_id', $userId, PDO::PARAM_INT);
    $req->bindParam(':weekday_index', $weekdayIndex, PDO::PARAM_INT);
    $req->bindParam(':start_time', $startTime, PDO::PARAM_STR);
    $req->bindParam(':event_title', $eventTitle, PDO::PARAM_STR);
    $req->bindParam(':event_color', $eventColor, PDO::PARAM_STR);
    $req->bindParam(':duration_hours', $durationHours);
    
    $req->execute();
    
    $eventId = $db->lastInsertId();
    
    // Log for debug (optional)
    error_log("Event created: ID=$eventId, Day=" . dayIndexToName($weekdayIndex) . " ($weekdayIndex), Time=$startTime");
    
    sendResponse(true, [
        'message' => 'Event added successfully',
        'event' => [
            'event_id' => $eventId,
            'user_id' => $userId,
            'weekday_index' => $weekdayIndex,
            'start_time' => $startTime,
            'event_title' => $eventTitle,
            'event_color' => $eventColor,
            'duration_hours' => $durationHours
        ]
    ], 201);
    
} catch(PDOException $e) {
    error_log("Error add-event: " . $e->getMessage());
    sendResponse(false, ['error' => 'Error adding event'], 500);
}
?>