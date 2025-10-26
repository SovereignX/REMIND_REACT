<?php
/**
 * API - Supprimer un événement
 * DELETE /api/events/delete-event.php
 * 
 * Body JSON requis:
 * {
 *   "id": 1
 * }
 */

require_once '../../config/cors.php';
require_once '../../config/database.php';
require_once '../../config/auth.php';

header("Content-Type: application/json; charset=UTF-8");

/**
 * Fonction helper pour les réponses JSON
 */
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

// Vérifier l'ID de l'événement
if (!isset($data['id']) || !is_numeric($data['id'])) {
    sendResponse(false, ['error' => 'ID de l\'événement requis'], 400);
}

// Récupérer l'utilisateur connecté
$userId = getUserId();
if (!$userId) {
    sendResponse(false, ['error' => 'Authentification requise'], 401);
}

$eventId = intval($data['id']);

try {
    $db = getConnection();
    
    // Vérifier que l'événement existe et appartient à l'utilisateur
    $checkReq = $db->prepare("SELECT id, title FROM events WHERE id = :id AND user_id = :user_id");
    $checkReq->bindParam(':id', $eventId, PDO::PARAM_INT);
    $checkReq->bindParam(':user_id', $userId, PDO::PARAM_INT);
    $checkReq->execute();
    
    $event = $checkReq->fetch(PDO::FETCH_ASSOC);
    
    if (!$event) {
        sendResponse(false, ['error' => 'Événement non trouvé ou accès non autorisé'], 404);
    }
    
    // Supprimer l'événement
    $deleteReq = $db->prepare("DELETE FROM events WHERE id = :id AND user_id = :user_id");
    $deleteReq->bindParam(':id', $eventId, PDO::PARAM_INT);
    $deleteReq->bindParam(':user_id', $userId, PDO::PARAM_INT);
    $deleteReq->execute();
    
    sendResponse(true, [
        'message' => 'Événement supprimé avec succès',
        'deleted_event' => [
            'id' => $eventId,
            'title' => $event['title']
        ]
    ], 200);
    
} catch(PDOException $e) {
    error_log("Erreur delete-event: " . $e->getMessage());
    sendResponse(false, ['error' => 'Erreur lors de la suppression de l\'événement'], 500);
}
?>
