<?php
/**
 * API - Save complete schedule
 * POST /api/events/save-events.php
 */

require_once '../../config/cors.php';
require_once '../../config/database.php';
require_once '../../config/auth.php';
require_once '../../utils/days.php';
require_once '../../utils/validation.php';

header("Content-Type: application/json; charset=UTF-8");

function sendResponse($success, $data = [], $httpCode = 200) {
    http_response_code($httpCode);
    echo json_encode(array_merge(['success' => $success], $data));
    exit;
}

/**
 * Validate individual event
 */
function validateEvent($event, $index) {
    $errors = [];
    
    // Support both new and old field names
    $weekdayIndex = isset($event['weekday_index']) ? $event['weekday_index'] : 
                    (isset($event['day_index']) ? $event['day_index'] : null);
    
    if ($weekdayIndex === null) {
        $errors[] = "Event $index: weekday index is required";
    } elseif (!isValidDayIndex($weekdayIndex)) {
        $errors[] = "Event $index: invalid weekday index (must be between 0 and 6)";
    }
    
    $startTime = isset($event['start_time']) ? $event['start_time'] : 
                 (isset($event['time']) ? $event['time'] : null);
    
    if (empty($startTime) || !preg_match('/^([0-1]?[0-9]|2[0-3]):[0-5][0-9]$/', $startTime)) {
        $errors[] = "Event $index: invalid time format";
    }
    
    $eventTitle = isset($event['event_title']) ? $event['event_title'] : 
                  (isset($event['title']) ? $event['title'] : null);
    
    if (empty($eventTitle) || strlen($eventTitle) > 255) {
        $errors[] = "Event $index: invalid title";
    }
    
    $eventColor = isset($event['event_color']) ? $event['event_color'] : 
                  (isset($event['color']) ? $event['color'] : null);
    
    if (empty($eventColor) || !preg_match('/^#[0-9A-Fa-f]{6}$/', $eventColor)) {
        $errors[] = "Event $index: invalid color format";
    }
    
    $durationHours = isset($event['duration_hours']) ? $event['duration_hours'] : 
                     (isset($event['duration']) ? $event['duration'] : null);
    
    if ($durationHours === null || $durationHours <= 0 || $durationHours > 24) {
        $errors[] = "Event $index: invalid duration";
    }
    
    return $errors;
}

// Get JSON data
$json = file_get_contents("php://input");
$data = json_decode($json, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    sendResponse(false, ['error' => 'Invalid JSON format'], 400);
}

if (!isset($data['events']) || !is_array($data['events'])) {
    sendResponse(false, ['error' => 'The "events" field must be an array'], 400);
}

$userId = getUserId();
if (!$userId) {
    sendResponse(false, ['error' => 'Authentication required'], 401);
}

// Validate all events
$allErrors = [];
foreach ($data['events'] as $index => $event) {
    $eventErrors = validateEvent($event, $index + 1);
    $allErrors = array_merge($allErrors, $eventErrors);
}

if (!empty($allErrors)) {
    sendResponse(false, ['errors' => $allErrors], 400);
}

try {
    $db = getConnection();
    
    // Start transaction
    $db->beginTransaction();
    
    // Delete all existing user events
    $deleteReq = $db->prepare("DELETE FROM events WHERE user_id = :user_id");
    $deleteReq->bindParam(':user_id', $userId, PDO::PARAM_INT);
    $deleteReq->execute();
    
    $deletedCount = $deleteReq->rowCount();
    error_log("Deleted $deletedCount events for user $userId");
    
    // Prepare insert query
    $insertReq = $db->prepare(
        "INSERT INTO events (user_id, weekday_index, start_time, event_title, event_color, duration_hours) 
         VALUES (:user_id, :weekday_index, :start_time, :event_title, :event_color, :duration_hours)"
    );
    
    $insertedEvents = [];
    
    // Insert all new events
    foreach ($data['events'] as $event) {
        // Support both new and old field names
        $weekdayIndex = intval(isset($event['weekday_index']) ? $event['weekday_index'] : $event['day_index']);
        $startTime = trim(isset($event['start_time']) ? $event['start_time'] : $event['time']);
        
        // ✅ CLEAN the title
        $eventTitle = cleanEventTitle(isset($event['event_title']) ? $event['event_title'] : $event['title']);
        
        // Check that cleaned title is not empty
        if (empty($eventTitle)) {
            $db->rollBack();
            sendResponse(false, ['error' => 'A title cannot be empty or contain only HTML tags'], 400);
        }
        
        if (containsDangerousChars($eventTitle)) {
            $db->rollBack();
            sendResponse(false, ['error' => 'A title contains unauthorized elements'], 400);
        }
        
        $eventColor = trim(isset($event['event_color']) ? $event['event_color'] : $event['color']);
        $durationHours = floatval(isset($event['duration_hours']) ? $event['duration_hours'] : $event['duration']);
        
        $insertReq->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $insertReq->bindParam(':weekday_index', $weekdayIndex, PDO::PARAM_INT);
        $insertReq->bindParam(':start_time', $startTime, PDO::PARAM_STR);
        $insertReq->bindParam(':event_title', $eventTitle, PDO::PARAM_STR);
        $insertReq->bindParam(':event_color', $eventColor, PDO::PARAM_STR);
        $insertReq->bindParam(':duration_hours', $durationHours);
        
        $insertReq->execute();
        
        // Get generated ID and store event
        $newId = $db->lastInsertId();
        $insertedEvents[] = [
            'event_id' => (int)$newId,
            'id' => (int)$newId, // backward compatibility
            'user_id' => $userId,
            'weekday_index' => $weekdayIndex,
            'day_index' => $weekdayIndex, // backward compatibility
            'start_time' => $startTime,
            'time' => $startTime, // backward compatibility
            'event_title' => $eventTitle,
            'title' => $eventTitle, // backward compatibility
            'event_color' => $eventColor,
            'color' => $eventColor, // backward compatibility
            'duration_hours' => $durationHours,
            'duration' => $durationHours // backward compatibility
        ];
    }
    
    // Commit transaction
    $db->commit();
    
    error_log("Schedule saved: " . count($insertedEvents) . " events inserted");
    
    sendResponse(true, [
        'message' => 'Schedule saved successfully',
        'deleted_count' => $deletedCount,
        'inserted_count' => count($insertedEvents),
        'events' => $insertedEvents
    ], 201);
    
} catch(PDOException $e) {
    // Rollback transaction on error
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    error_log("Error save-events: " . $e->getMessage());
    sendResponse(false, ['error' => 'Error saving schedule'], 500);
}
?>