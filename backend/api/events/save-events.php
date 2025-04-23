<?php
require_once '../../config/cors.php';
require_once '../../config/database.php';

// Add CORS headers (the function call is already in cors.php, but adding it explicitly won't hurt)
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
    
    // Insert all events
    $req = $db->prepare("INSERT INTO events (id, day, time, title, color, duration) 
                          VALUES (:id, :day, :time, :title, :color, :duration)");
    
    foreach ($data['events'] as $event) {
        $req->bindParam(':id', $event['id']);
        $req->bindParam(':day', $event['day']);
        $req->bindParam(':time', $event['time']);
        $req->bindParam(':title', $event['title']);
        $req->bindParam(':color', $event['color']);
        $req->bindParam(':duration', $event['duration']);
        $req->execute();
    }
    
    // Commit the transaction
    $db->commit();
    
    echo json_encode(["success" => true]);
} catch(PDOException $e) {
    // Roll back on error
    $db->rollBack();
    echo json_encode(["error" => "Database error: " . $e->getMessage()]);
}
?>