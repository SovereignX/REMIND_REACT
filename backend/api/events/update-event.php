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
    $updateFields = [];
    $params = [':id' => $data['id']];
    
    if (isset($data['day'])) {
        $updateFields[] = "day = :day";
        $params[':day'] = $data['day'];
    }
    
    if (isset($data['time'])) {
        $updateFields[] = "time = :time";
        $params[':time'] = $data['time'];
    }
    
    if (isset($data['title'])) {
        $updateFields[] = "title = :title";
        $params[':title'] = $data['title'];
    }
    
    if (isset($data['color'])) {
        $updateFields[] = "color = :color";
        $params[':color'] = $data['color'];
    }
    
    if (isset($data['duration'])) {
        $updateFields[] = "duration = :duration";
        $params[':duration'] = $data['duration'];
    }
    
    if (empty($updateFields)) {
        echo json_encode(["error" => "No fields to update"]);
        exit;
    }
    
    $sql = "UPDATE events SET " . implode(", ", $updateFields) . " WHERE id = :id";
    $req = $db->prepare($sql);
    $req->execute($params);
    
    echo json_encode(["success" => true, "message" => "Event updated successfully"]);
} catch(PDOException $e) {
    echo json_encode(["error" => "Error updating event: " . $e->getMessage()]);
}
?>