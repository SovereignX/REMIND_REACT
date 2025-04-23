<?php
require_once '../../config/cors.php';
require_once '../../config/database.php';

// Add CORS headers
addCorsHeaders();

header('Content-Type: application/json');

try {
    // Get database connection
    $db = getConnection();
    
    // Prepare and execute query to get all events
    $req = $db->prepare("SELECT id, day, time, title, color, duration FROM events");
    $req->execute();
    
    // Fetch all events as an associative array
    $events = $req->fetchAll(PDO::FETCH_ASSOC);
    
    // Return events as JSON
    echo json_encode($events);
    
} catch(PDOException $e) {
    // Return error message if something goes wrong
    echo json_encode(["error" => "Database error: " . $e->getMessage()]);
}
?>