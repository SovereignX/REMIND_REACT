<?php
/**
 * API - Update an event
 * PUT /api/events/update-event.php
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

// Get JSON data
$json = file_get_contents("php://input");
$data = json_decode($json, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    sendResponse(false, ['error' => 'Invalid JSON format'], 400);
}

// Support both old 'id' and new 'event_id' for backward compatibility
if (!isset($data['event_id']) && !isset($data['id'])) {
    sendResponse(false, ['error' => 'Event ID required'], 400);
}

$eventId = isset($data['event_id']) ? intval($data['event_id']) : intval($data['id']);

if (!is_numeric($eventId)) {
    sendResponse(false, ['error' => 'Event ID must be numeric'], 400);
}

$userId = getUserId();
if (!$userId) {
    sendResponse(false, ['error' => 'Authentication required'], 401);
}

try {
    $db = getConnection();
    
    // Check that event exists and belongs to user
    $checkReq = $db->prepare(
        "SELECT event_id 
         FROM events 
         WHERE event_id = :event_id AND user_id = :user_id"
    );
    $checkReq->bindParam(':event_id', $eventId, PDO::PARAM_INT);
    $checkReq->bindParam(':user_id', $userId, PDO::PARAM_INT);
    $checkReq->execute();
    
    if (!$checkReq->fetch()) {
        sendResponse(false, ['error' => 'Event not found or access not authorized'], 404);
    }
    
    // Build update query
    $updateFields = [];
    $params = [':event_id' => $eventId, ':user_id' => $userId];
    
    // Validate and add fields to update
    // Support both new and old field names for backward compatibility
    $weekdayIndex = isset($data['weekday_index']) ? $data['weekday_index'] : 
                    (isset($data['day_index']) ? $data['day_index'] : null);
    
    if ($weekdayIndex !== null) {
        if (!isValidDayIndex($weekdayIndex)) {
            sendResponse(false, ['error' => 'Invalid weekday index (must be between 0 and 6)'], 400);
        }
        $updateFields[] = "weekday_index = :weekday_index";
        $params[':weekday_index'] = intval($weekdayIndex);
    }
    
    $startTime = isset($data['start_time']) ? $data['start_time'] : 
                 (isset($data['time']) ? $data['time'] : null);
    
    if ($startTime !== null) {
        if (!preg_match('/^([0-1]?[0-9]|2[0-3]):[0-5][0-9]$/', $startTime)) {
            sendResponse(false, ['error' => 'Invalid time format (HH:MM expected)'], 400);
        }
        $updateFields[] = "start_time = :start_time";
        $params[':start_time'] = trim($startTime);
    }
    
    $eventTitle = isset($data['event_title']) ? $data['event_title'] : 
                  (isset($data['title']) ? $data['title'] : null);
    
    if ($eventTitle !== null) {
        // ✅ CLEAN the title
        $cleanedTitle = cleanEventTitle($eventTitle);
        
        if (empty($cleanedTitle)) {
            sendResponse(false, ['error' => 'Invalid title (empty or contains only tags)'], 400);
        }
        
        if (strlen($cleanedTitle) > 255) {
            sendResponse(false, ['error' => 'Invalid title (max 255 characters)'], 400);
        }
        
        if (containsDangerousChars($cleanedTitle)) {
            sendResponse(false, ['error' => 'Title contains unauthorized elements'], 400);
        }
        
        $updateFields[] = "event_title = :event_title";
        $params[':event_title'] = $cleanedTitle;
    }
    
    $eventColor = isset($data['event_color']) ? $data['event_color'] : 
                  (isset($data['color']) ? $data['color'] : null);
    
    if ($eventColor !== null) {
        if (!preg_match('/^#[0-9A-Fa-f]{6}$/', $eventColor)) {
            sendResponse(false, ['error' => 'Invalid color format (#RRGGBB expected)'], 400);
        }
        $updateFields[] = "event_color = :event_color";
        $params[':event_color'] = trim($eventColor);
    }
    
    $durationHours = isset($data['duration_hours']) ? $data['duration_hours'] : 
                     (isset($data['duration']) ? $data['duration'] : null);
    
    if ($durationHours !== null) {
        $duration = floatval($durationHours);
        if ($duration <= 0 || $duration > 24) {
            sendResponse(false, ['error' => 'Invalid duration (between 0.5 and 24 hours)'], 400);
        }
        $updateFields[] = "duration_hours = :duration_hours";
        $params[':duration_hours'] = $duration;
    }
    
    // Check that there's at least one field to update
    if (empty($updateFields)) {
        sendResponse(false, ['error' => 'No fields to update'], 400);
    }
    
    // Execute update
    $sql = "UPDATE events SET " . implode(", ", $updateFields) . 
           " WHERE event_id = :event_id AND user_id = :user_id";
    $req = $db->prepare($sql);
    $req->execute($params);
    
    // ✅ CORRECTION: Select only necessary columns
    $getReq = $db->prepare(
        "SELECT event_id, user_id, weekday_index, start_time, event_title, 
                event_color, duration_hours, created_at, updated_at 
         FROM events 
         WHERE event_id = :event_id"
    );
    $getReq->bindParam(':event_id', $eventId, PDO::PARAM_INT);
    $getReq->execute();
    $updatedEvent = $getReq->fetch(PDO::FETCH_ASSOC);
    
    // Format data
    $updatedEvent['event_id'] = (int)$updatedEvent['event_id'];
    $updatedEvent['user_id'] = (int)$updatedEvent['user_id'];
    $updatedEvent['weekday_index'] = (int)$updatedEvent['weekday_index'];
    $updatedEvent['duration_hours'] = (float)$updatedEvent['duration_hours'];
    
    // Add backward compatibility fields
    $updatedEvent['id'] = $updatedEvent['event_id'];
    $updatedEvent['day_index'] = $updatedEvent['weekday_index'];
    $updatedEvent['time'] = $updatedEvent['start_time'];
    $updatedEvent['title'] = $updatedEvent['event_title'];
    $updatedEvent['color'] = $updatedEvent['event_color'];
    $updatedEvent['duration'] = $updatedEvent['duration_hours'];
    
    sendResponse(true, [
        'message' => 'Event updated successfully',
        'event' => $updatedEvent
    ], 200);
    
} catch(PDOException $e) {
    error_log("Error update-event: " . $e->getMessage());
    sendResponse(false, ['error' => 'Error updating event'], 500);
}
?>