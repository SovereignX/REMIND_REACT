<?php
/**
 * API - Supprimer un événement
 * DELETE /api/events/delete-event.php
 * 
 * Corps JSON requis :
 * {
 *   "event_id": 1
 * }
 */

require_once '../../config/cors.php';
require_once '../../config/database.php';
require_once '../../config/auth.php';

header("Content-Type: application/json; charset=UTF-8");


// Fonction helper pour les réponses JSON
function sendResponse($success, $data = [], $httpCode = 200) {
    http_response_code($httpCode);
    echo json_encode(array_merge(['success' => $success], $data));
    exit;
}

// Récupérer les données JSON
$json = file_get_contents("php://input");
$data = json_decode($json, true);

// Vérifier la validité du JSON
if (json_last_error() !== JSON_ERROR_NONE) {
    sendResponse(false, ['error' => 'Format JSON invalide'], 400);
}

// Vérifier la présence de l'ID de l'événement
if (!isset($data['event_id'])) {
    sendResponse(false, ['error' => 'L\'ID de l\'événement est requis'], 400);
}

$eventId = intval($data['event_id']);

if (!is_numeric($eventId)) {
    sendResponse(false, ['error' => 'L\'ID de l\'événement doit être numérique'], 400);
}

// Récupérer l'utilisateur authentifié
$userId = getAuthUserId();
if (!$userId) {
    sendResponse(false, ['error' => 'Authentification requise'], 401);
}

try {
    $db = getConnection();
    
    // Vérifier que l'événement existe et appartient à l'utilisateur
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
        sendResponse(false, ['error' => 'Événement non trouvé ou accès non autorisé'], 404);
    }
    
    // Supprimer l'événement
    $deleteReq = $db->prepare(
        "DELETE FROM events 
         WHERE event_id = :event_id AND user_id = :user_id"
    );
    $deleteReq->bindParam(':event_id', $eventId, PDO::PARAM_INT);
    $deleteReq->bindParam(':user_id', $userId, PDO::PARAM_INT);
    $deleteReq->execute();
    
    sendResponse(true, [
        'message' => 'Événement supprimé avec succès',
        'deleted_event' => [
            'event_id' => $eventId,
            'event_title' => $event['event_title']
        ]
    ], 200);
    
} catch(PDOException $e) {
    error_log("Erreur delete-event : " . $e->getMessage());
    sendResponse(false, ['error' => 'Erreur lors de la suppression de l\'événement'], 500);
}
?>