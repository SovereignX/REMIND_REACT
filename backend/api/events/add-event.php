<?php
require_once '../../config/cors.php';
require_once '../../config/database.php';

header("Content-Type: application/json; charset=UTF-8");

// Fonction helper pour les réponses
function sendResponse($success, $data = [], $httpCode = 200) {
    http_response_code($httpCode);
    echo json_encode(array_merge(['success' => $success], $data));
    exit;
}

// Récupérer les données JSON
$json = file_get_contents("php://input");
$data = json_decode($json, true);

// Vérifier que le JSON est valide
if (json_last_error() !== JSON_ERROR_NONE) {
    sendResponse(false, ['error' => 'JSON invalide'], 400);
}

// Vérifier les champs requis
if (!isset($data['id']) || !isset($data['day']) || !isset($data['time']) || 
    !isset($data['title']) || !isset($data['color']) || !isset($data['duration'])) {
    sendResponse(false, ['error' => 'Champs manquants'], 400);
}

// Insérer en base de données
try {
    $db = getConnection();
    
    $req = $db->prepare(
        "INSERT INTO events (id, day, time, title, color, duration) 
         VALUES (:id, :day, :time, :title, :color, :duration)"
    );
    
    $req->bindParam(':id', $data['id']);
    $req->bindParam(':day', $data['day']);
    $req->bindParam(':time', $data['time']);
    $req->bindParam(':title', $data['title']);
    $req->bindParam(':color', $data['color']);
    $req->bindParam(':duration', $data['duration']);
    
    $req->execute();
    
    sendResponse(true, ['message' => 'Événement ajouté avec succès'], 201);
    
} catch(PDOException $e) {
    error_log("Erreur add-event: " . $e->getMessage());
    sendResponse(false, ['error' => 'Erreur serveur: ' . $e->getMessage()], 500);
}
?>