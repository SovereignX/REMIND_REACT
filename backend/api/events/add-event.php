<?php
require_once '../../config/cors.php';
require_once '../../config/database.php';

// Add CORS headers to this response
addCorsHeaders();

header("Content-Type: application/json");
$data = json_decode(file_get_contents("php://input"), true);

if (!isset($data['id']) || !isset($data['day']) || !isset($data['time']) || !isset($data['title']) || !isset($data['color']) || !isset($data['duration'])) {
    echo json_encode(["error" => "Missing required fields"]);
    exit;
}

try {
    $db = getConnection();
    $req = $db->prepare("INSERT INTO events (id, day, time, title, color, duration) VALUES (:id, :day, :time, :title, :color, :duration)");
    
    $req->bindParam(':id', $data['id']);
    $req->bindParam(':day', $data['day']);
    $req->bindParam(':time', $data['time']);
    $req->bindParam(':title', $data['title']);
    $req->bindParam(':color', $data['color']);
    $req->bindParam(':duration', $data['duration']);
    
    $req->execute();
    
    echo json_encode(["success" => true, "message" => "Event added successfully"]);
} catch(PDOException $e) {
    echo json_encode(["error" => "Error adding event: " . $e->getMessage()]);
}
?>