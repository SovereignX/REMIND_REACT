<?php
/**
 * API - Get user events
 * GET /api/events/get-events.php
 * 
 * Optional query params:
 * - weekday_index: filter by day (ex: ?weekday_index=0 for Monday)
 * - start_date: start date (format: YYYY-MM-DD)
 * - end_date: end date
 */

require_once '../../config/cors.php';
require_once '../../config/database.php';
require_once '../../config/auth.php';

header('Content-Type: application/json; charset=UTF-8');

/**
 * Helper function for JSON responses
 */
function sendResponse($success, $data = [], $httpCode = 200) {
    http_response_code($httpCode);
    echo json_encode(array_merge(['success' => $success], $data));
    exit;
}

// Get authenticated user
$userId = getUserId();
if (!$userId) {
    sendResponse(false, ['error' => 'Authentication required'], 401);
}

try {
    $db = getConnection();
    
    // Build query based on filters
    $sql = "SELECT event_id, user_id, weekday_index, start_time, event_title, 
                   event_color, duration_hours, created_at, updated_at 
            FROM events 
            WHERE user_id = :user_id";
    
    $params = [':user_id' => $userId];
    
    // Optional filter by weekday_index (also support old 'day_index' for backward compatibility)
    $dayParam = isset($_GET['weekday_index']) ? $_GET['weekday_index'] : 
                (isset($_GET['day_index']) ? $_GET['day_index'] : null);
    
    if ($dayParam !== null && is_numeric($dayParam)) {
        $weekdayIndex = intval($dayParam);
        if ($weekdayIndex >= 0 && $weekdayIndex <= 6) {
            $sql .= " AND weekday_index = :weekday_index";
            $params[':weekday_index'] = $weekdayIndex;
        }
    }
    
    // Optional filter by date range
    if (isset($_GET['start_date']) && !empty($_GET['start_date'])) {
        $sql .= " AND created_at >= :start_date";
        $params[':start_date'] = $_GET['start_date'];
    }
    
    if (isset($_GET['end_date']) && !empty($_GET['end_date'])) {
        $sql .= " AND created_at <= :end_date";
        $params[':end_date'] = $_GET['end_date'];
    }
    
    // Optimized sorting by weekday_index then start_time
    $sql .= " ORDER BY weekday_index ASC, start_time ASC";
    
    // Execute query
    $req = $db->prepare($sql);
    foreach ($params as $key => $value) {
        $req->bindValue($key, $value);
    }
    $req->execute();
    
    $events = $req->fetchAll(PDO::FETCH_ASSOC);
    
    // Format data
    foreach ($events as &$event) {
        $event['event_id'] = (int)$event['event_id'];
        $event['user_id'] = (int)$event['user_id'];
        $event['weekday_index'] = (int)$event['weekday_index'];
        $event['duration_hours'] = (float)$event['duration_hours'];
        
        // Add backward compatibility fields
        $event['id'] = $event['event_id'];
        $event['day_index'] = $event['weekday_index'];
        $event['time'] = $event['start_time'];
        $event['title'] = $event['event_title'];
        $event['color'] = $event['event_color'];
        $event['duration'] = $event['duration_hours'];
    }
    
    sendResponse(true, [
        'count' => count($events),
        'events' => $events
    ], 200);
    
} catch(PDOException $e) {
    error_log("Error get-events: " . $e->getMessage());
    sendResponse(false, ['error' => 'Error retrieving events'], 500);
}
?>