<?php
/**
 * API - Mettre à jour un événement
 * PUT /api/events/update-event.php
 */

require_once '../../config/cors.php';
require_once '../../config/database.php';
require_once '../../config/auth.php';
require_once '../../utils/days.php';
require_once '../../utils/validation.php';  // ← NOUVEAU

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

if (!isset($data['id']) || !is_numeric($data['id'])) {
    sendResponse(false, ['error' => 'ID de l\'événement requis'], 400);
}

$userId = getUserId();
if (!$userId) {
    sendResponse(false, ['error' => 'Authentification requise'], 401);
}

$eventId = intval($data['id']);

try {
    $db = getConnection();
    
    // Vérifier que l'événement existe et appartient à l'utilisateur
    $checkReq = $db->prepare("SELECT id FROM events WHERE id = :id AND user_id = :user_id");
    $checkReq->bindParam(':id', $eventId, PDO::PARAM_INT);
    $checkReq->bindParam(':user_id', $userId, PDO::PARAM_INT);
    $checkReq->execute();
    
    if (!$checkReq->fetch()) {
        sendResponse(false, ['error' => 'Événement non trouvé ou accès non autorisé'], 404);
    }
    
    // Construction de la requête de mise à jour
    $updateFields = [];
    $params = [':id' => $eventId, ':user_id' => $userId];
    
    // Validation et ajout des champs à mettre à jour
    if (isset($data['day_index'])) {
        if (!isValidDayIndex($data['day_index'])) {
            sendResponse(false, ['error' => 'Index de jour invalide (doit être entre 0 et 6)'], 400);
        }
        $updateFields[] = "day_index = :day_index";
        $params[':day_index'] = intval($data['day_index']);
    }
    
    if (isset($data['time'])) {
        if (!preg_match('/^([0-1]?[0-9]|2[0-3]):[0-5][0-9]$/', $data['time'])) {
            sendResponse(false, ['error' => 'Format d\'heure invalide (HH:MM attendu)'], 400);
        }
        $updateFields[] = "time = :time";
        $params[':time'] = trim($data['time']);
    }
    
    if (isset($data['title'])) {
        // ✅ NETTOYER le titre
        $cleanedTitle = cleanEventTitle($data['title']);
        
        if (empty($cleanedTitle)) {
            sendResponse(false, ['error' => 'Titre invalide (vide ou contient uniquement des balises)'], 400);
        }
        
        if (strlen($cleanedTitle) > 255) {
            sendResponse(false, ['error' => 'Titre invalide (max 255 caractères)'], 400);
        }
        
        if (containsDangerousChars($cleanedTitle)) {
            sendResponse(false, ['error' => 'Le titre contient des éléments non autorisés'], 400);
        }
        
        $updateFields[] = "title = :title";
        $params[':title'] = $cleanedTitle;
    }
    
    if (isset($data['color'])) {
        if (!preg_match('/^#[0-9A-Fa-f]{6}$/', $data['color'])) {
            sendResponse(false, ['error' => 'Format de couleur invalide (#RRGGBB attendu)'], 400);
        }
        $updateFields[] = "color = :color";
        $params[':color'] = trim($data['color']);
    }
    
    if (isset($data['duration'])) {
        $duration = floatval($data['duration']);
        if ($duration <= 0 || $duration > 24) {
            sendResponse(false, ['error' => 'Durée invalide (entre 0.5 et 24 heures)'], 400);
        }
        $updateFields[] = "duration = :duration";
        $params[':duration'] = $duration;
    }
    
    // Vérifier qu'il y a au moins un champ à mettre à jour
    if (empty($updateFields)) {
        sendResponse(false, ['error' => 'Aucun champ à mettre à jour'], 400);
    }
    
    // Exécuter la mise à jour
    $sql = "UPDATE events SET " . implode(", ", $updateFields) . " WHERE id = :id AND user_id = :user_id";
    $req = $db->prepare($sql);
    $req->execute($params);
    
    // Récupérer l'événement mis à jour
    $getReq = $db->prepare("SELECT * FROM events WHERE id = :id");
    $getReq->bindParam(':id', $eventId, PDO::PARAM_INT);
    $getReq->execute();
    $updatedEvent = $getReq->fetch(PDO::FETCH_ASSOC);
    
    // Formater les données
    $updatedEvent['id'] = (int)$updatedEvent['id'];
    $updatedEvent['user_id'] = (int)$updatedEvent['user_id'];
    $updatedEvent['day_index'] = (int)$updatedEvent['day_index'];
    $updatedEvent['duration'] = (float)$updatedEvent['duration'];
    
    sendResponse(true, [
        'message' => 'Événement mis à jour avec succès',
        'event' => $updatedEvent
    ], 200);
    
} catch(PDOException $e) {
    error_log("Erreur update-event: " . $e->getMessage());
    sendResponse(false, ['error' => 'Erreur lors de la mise à jour de l\'événement'], 500);
}
?>