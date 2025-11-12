<?php
/**
 * API - Mettre à jour un événement
 * PUT /api/events/update-event.php
 */

require_once '../../config/cors.php';
require_once '../../config/database.php';
require_once '../../config/auth.php';
require_once '../../utils/days.php';
require_once '../../utils/validation.php';

header("Content-Type: application/json; charset=UTF-8");

function sendResponse($success, $data = [], $httpCode = 200) {
    http_response_code($httpCode);
    echo json_encode(array_merge(['success' => $success], $data));
    exit;
}

// Récupérer les données JSON
$json = file_get_contents("php://input");
$data = json_decode($json, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    sendResponse(false, ['error' => 'Format JSON invalide'], 400);
}

// Vérifier la présence de l'ID
if (!isset($data['event_id'])) {
    sendResponse(false, ['error' => 'L\'ID de l\'événement est requis'], 400);
}

$eventId = intval($data['event_id']);

if (!is_numeric($eventId)) {
    sendResponse(false, ['error' => 'L\'ID de l\'événement doit être numérique'], 400);
}

$userId = getAuthUserId();
if (!$userId) {
    sendResponse(false, ['error' => 'Authentification requise'], 401);
}

try {
    $db = getConnection();
    
    // Vérifier que l'événement existe et appartient à l'utilisateur
    $checkReq = $db->prepare(
        "SELECT event_id 
         FROM events 
         WHERE event_id = :event_id AND user_id = :user_id"
    );
    $checkReq->bindParam(':event_id', $eventId, PDO::PARAM_INT);
    $checkReq->bindParam(':user_id', $userId, PDO::PARAM_INT);
    $checkReq->execute();
    
    if (!$checkReq->fetch()) {
        sendResponse(false, ['error' => 'Événement non trouvé ou accès non autorisé'], 404);
    }
    
    // Construire la requête de mise à jour
    $updateFields = [];
    $params = [':event_id' => $eventId, ':user_id' => $userId];
    
    // Valider et ajouter les champs à mettre à jour
    if (isset($data['weekday_index'])) {
        if (!isValidDayIndex($data['weekday_index'])) {
            sendResponse(false, ['error' => 'Index de jour invalide (doit être entre 0 et 6)'], 400);
        }
        $updateFields[] = "weekday_index = :weekday_index";
        $params[':weekday_index'] = intval($data['weekday_index']);
    }
    
    if (isset($data['start_time'])) {
        if (!preg_match('/^([0-1]?[0-9]|2[0-3]):[0-5][0-9]$/', $data['start_time'])) {
            sendResponse(false, ['error' => 'Format d\'heure invalide (HH:MM attendu)'], 400);
        }
        $updateFields[] = "start_time = :start_time";
        $params[':start_time'] = trim($data['start_time']);
    }
    
    if (isset($data['event_title'])) {
        // NETTOYER le titre
        $cleanedTitle = cleanEventTitle($data['event_title']);
        
        if (empty($cleanedTitle)) {
            sendResponse(false, ['error' => 'Titre invalide (vide ou contient uniquement des balises)'], 400);
        }
        
        if (strlen($cleanedTitle) > 255) {
            sendResponse(false, ['error' => 'Titre invalide (max 255 caractères)'], 400);
        }
        
        if (containsDangerousChars($cleanedTitle)) {
            sendResponse(false, ['error' => 'Le titre contient des éléments non autorisés'], 400);
        }
        
        $updateFields[] = "event_title = :event_title";
        $params[':event_title'] = $cleanedTitle;
    }
    
    if (isset($data['event_color'])) {
        if (!preg_match('/^#[0-9A-Fa-f]{6}$/', $data['event_color'])) {
            sendResponse(false, ['error' => 'Format de couleur invalide (#RRGGBB attendu)'], 400);
        }
        $updateFields[] = "event_color = :event_color";
        $params[':event_color'] = trim($data['event_color']);
    }
    
    if (isset($data['duration_hours'])) {
        $duration = floatval($data['duration_hours']);
        if ($duration <= 0 || $duration > 24) {
            sendResponse(false, ['error' => 'Durée invalide (entre 0.5 et 24 heures)'], 400);
        }
        $updateFields[] = "duration_hours = :duration_hours";
        $params[':duration_hours'] = $duration;
    }
    
    // Vérifier qu'il y a au moins un champ à mettre à jour
    if (empty($updateFields)) {
        sendResponse(false, ['error' => 'Aucun champ à mettre à jour'], 400);
    }
    
    // Exécuter la mise à jour
    $sql = "UPDATE events SET " . implode(", ", $updateFields) . 
           " WHERE event_id = :event_id AND user_id = :user_id";
    $req = $db->prepare($sql);
    $req->execute($params);
    
    // Récupérer l'événement mis à jour
    $getReq = $db->prepare(
        "SELECT event_id, user_id, weekday_index, start_time, event_title, 
                event_color, duration_hours, created_at, updated_at 
         FROM events 
         WHERE event_id = :event_id"
    );
    $getReq->bindParam(':event_id', $eventId, PDO::PARAM_INT);
    $getReq->execute();
    $updatedEvent = $getReq->fetch(PDO::FETCH_ASSOC);
    
    // Formater les données
    $updatedEvent['event_id'] = (int)$updatedEvent['event_id'];
    $updatedEvent['user_id'] = (int)$updatedEvent['user_id'];
    $updatedEvent['weekday_index'] = (int)$updatedEvent['weekday_index'];
    $updatedEvent['duration_hours'] = (float)$updatedEvent['duration_hours'];
    
    sendResponse(true, [
        'message' => 'Événement mis à jour avec succès',
        'event' => $updatedEvent
    ], 200);
    
} catch(PDOException $e) {
    error_log("Erreur update-event : " . $e->getMessage());
    sendResponse(false, ['error' => 'Erreur lors de la mise à jour de l\'événement'], 500);
}
?>