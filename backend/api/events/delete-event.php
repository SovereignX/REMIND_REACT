<?php
require_once '../../config/cors.php';
require_once '../../config/database.php';

// Add CORS headers to this response
addCorsHeaders();

header("Content-Type: application/json");
$data = json_decode(file_get_contents("php://input"), true);

if (!isset($data['id'])) {
    echo json_encode(["error" => "Missing event ID"]);
    exit;
}

try {
    $db = getConnection();
    $req = $db->prepare("DELETE FROM events WHERE id = :id");
    $req->bindParam(':id', $data['id']);
    $req->execute();
    
    echo json_encode(["success" => true, "message" => "Event deleted successfully"]);
} catch(PDOException $e) {
    echo json_encode(["error" => "Error deleting event: " . $e->getMessage()]);
}
?>