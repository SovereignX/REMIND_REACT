<?php
/**
 * API - Delete an event
 * DELETE /api/events/delete-event.php
 * 
 * Required JSON body:
 * {
 *   "event_id": 1
 * }
 */

require_once '../../config/cors.php';
require_once '../../config/database.php';
require_once '../../config/auth.php';

header("Content-Type: application/json; charset=UTF-8");

/**
 * Helper function for JSON responses
 */
function sendResponse($success, $data = [], $httpCode = 200) {
    http_response_code($httpCode);
    echo json_encode(array_merge(['success' => $success], $data));
    exit;
}

// Get JSON data
$json = file_get_contents("php://input");
$data = json_decode($json, true);

// Check JSON validity
if (json_last_error() !== JSON_ERROR_NONE) {
    sendResponse(false, ['error' => 'Invalid JSON format'], 400);
}

// Check event ID
if (!isset($data['event_id']) && !isset($data['id'])) {
    sendResponse(false, ['error' => 'Event ID required'], 400);
}

// Support both old 'id' and new 'event_id' for backward compatibility
$eventId = isset($data['event_id']) ? intval($data['event_id']) : intval($data['id']);

if (!is_numeric($eventId)) {
    sendResponse(false, ['error' => 'Event ID must be numeric'], 400);
}

// Get authenticated user
$userId = getUserId();
if (!$userId) {
    sendResponse(false, ['error' => 'Authentication required'], 401);
}

try {
    $db = getConnection();
    
    // Check that event exists and belongs to user
    $checkReq = $db->prepare(
        "SELECT event_id, event_title 
         FROM events 
         WHERE event_id = :event_id AND user_id = :user_id"
    );
    $checkReq->bindParam(':event_id', $eventId, PDO::PARAM_INT);
    $checkReq->bindParam(':user_id', $userId, PDO::PARAM_INT);
    $checkReq->execute();
    
    $event = $checkReq->fetch(PDO::FETCH_ASSOC);
    
    if (!$event) {
        sendResponse(false, ['error' => 'Event not found or access not authorized'], 404);
    }
    
    // Delete event
    $deleteReq = $db->prepare(
        "DELETE FROM events 
         WHERE event_id = :event_id AND user_id = :user_id"
    );
    $deleteReq->bindParam(':event_id', $eventId, PDO::PARAM_INT);
    $deleteReq->bindParam(':user_id', $userId, PDO::PARAM_INT);
    $deleteReq->execute();
    
    sendResponse(true, [
        'message' => 'Event deleted successfully',
        'deleted_event' => [
            'event_id' => $eventId,
            'event_title' => $event['event_title']
        ]
    ], 200);
    
} catch(PDOException $e) {
    error_log("Error delete-event: " . $e->getMessage());
    sendResponse(false, ['error' => 'Error deleting event'], 500);
}
?>