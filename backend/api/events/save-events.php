<?php
require_once '../../config/cors.php';
require_once '../../config/database.php';

// Add CORS headers
addCorsHeaders();

header("Content-Type: application/json");

$data = json_decode(file_get_contents("php://input"), true);

if (!isset($data['events']) || !is_array($data['events'])) {
    echo json_encode(["error" => "Invalid events data"]);
    exit;
}

try {
    $db = getConnection();
    // Begin transaction
    $db->beginTransaction();
    
    // Clear existing events
    $req = $db->prepare("DELETE FROM events");
    $req->execute();
    
    // Insert all events without specifying ID
    $req = $db->prepare("INSERT INTO events (day, time, title, color, duration) 
                          VALUES (:day, :time, :title, :color, :duration)");
    
    $insertedEvents = [];
    
    foreach ($data['events'] as $event) {
        $req->bindParam(':day', $event['day']);
        $req->bindParam(':time', $event['time']);
        $req->bindParam(':title', $event['title']);
        $req->bindParam(':color', $event['color']);
        $req->bindParam(':duration', $event['duration']);
        $req->execute();
        
        // Get the ID that was automatically generated
        $newId = $db->lastInsertId();
        
        // Store the event with its new ID
        $event['id'] = $newId;
        $insertedEvents[] = $event;
    }
    
    // Commit the transaction
    $db->commit();
    
    // Return the events with their new IDs
    echo json_encode([
        "success" => true,
        "events" => $insertedEvents
    ]);
} catch(PDOException $e) {
    // Roll back on error
    $db->rollBack();
    echo json_encode(["error" => "Database error: " . $e->getMessage()]);
}
?>