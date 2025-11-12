<?php
/**
 * API - Sauvegarder le planning complet
 * POST /api/events/save-events.php
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


// Valider un événement individuel
function validateEvent($event, $index) {
    $errors = [];
    
    if (!isset($event['weekday_index'])) {
        $errors[] = "Événement $index : l'index du jour est requis";
    } elseif (!isValidDayIndex($event['weekday_index'])) {
        $errors[] = "Événement $index : index de jour invalide (doit être entre 0 et 6)";
    }
    
    if (empty($event['start_time']) || !preg_match('/^([0-1]?[0-9]|2[0-3]):[0-5][0-9]$/', $event['start_time'])) {
        $errors[] = "Événement $index : format d'heure invalide";
    }
    
    if (empty($event['event_title']) || strlen($event['event_title']) > 255) {
        $errors[] = "Événement $index : titre invalide";
    }
    
    if (empty($event['event_color']) || !preg_match('/^#[0-9A-Fa-f]{6}$/', $event['event_color'])) {
        $errors[] = "Événement $index : format de couleur invalide";
    }
    
    if (!isset($event['duration_hours']) || $event['duration_hours'] <= 0 || $event['duration_hours'] > 24) {
        $errors[] = "Événement $index : durée invalide";
    }
    
    return $errors;
}

// Récupérer les données JSON
$json = file_get_contents("php://input");
$data = json_decode($json, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    sendResponse(false, ['error' => 'Format JSON invalide'], 400);
}

if (!isset($data['events']) || !is_array($data['events'])) {
    sendResponse(false, ['error' => 'Le champ "events" doit être un tableau'], 400);
}

$userId = getAuthUserId();
if (!$userId) {
    sendResponse(false, ['error' => 'Authentification requise'], 401);
}

// Valider tous les événements
$allErrors = [];
foreach ($data['events'] as $index => $event) {
    $eventErrors = validateEvent($event, $index + 1);
    $allErrors = array_merge($allErrors, $eventErrors);
}

if (!empty($allErrors)) {
    sendResponse(false, ['errors' => $allErrors], 400);
}

try {
    $db = getConnection();
    
    // Démarrer la transaction
    $db->beginTransaction();
    
    // Supprimer tous les événements existants de l'utilisateur
    $deleteReq = $db->prepare("DELETE FROM events WHERE user_id = :user_id");
    $deleteReq->bindParam(':user_id', $userId, PDO::PARAM_INT);
    $deleteReq->execute();
    
    $deletedCount = $deleteReq->rowCount();
    error_log("Supprimé $deletedCount événements pour l'utilisateur $userId");
    
    // Préparer la requête d'insertion
    $insertReq = $db->prepare(
        "INSERT INTO events (user_id, weekday_index, start_time, event_title, event_color, duration_hours) 
         VALUES (:user_id, :weekday_index, :start_time, :event_title, :event_color, :duration_hours)"
    );
    
    $insertedEvents = [];
    
    // Insérer tous les nouveaux événements
    foreach ($data['events'] as $event) {
        $weekdayIndex = intval($event['weekday_index']);
        $startTime = trim($event['start_time']);
        
        // NETTOYER le titre
        $eventTitle = cleanEventTitle($event['event_title']);
        
        // Vérifier que le titre nettoyé n'est pas vide
        if (empty($eventTitle)) {
            $db->rollBack();
            sendResponse(false, ['error' => 'Un titre ne peut pas être vide ou contenir uniquement des balises HTML'], 400);
        }
        
        if (containsDangerousChars($eventTitle)) {
            $db->rollBack();
            sendResponse(false, ['error' => 'Un titre contient des éléments non autorisés'], 400);
        }
        
        $eventColor = trim($event['event_color']);
        $durationHours = floatval($event['duration_hours']);
        
        $insertReq->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $insertReq->bindParam(':weekday_index', $weekdayIndex, PDO::PARAM_INT);
        $insertReq->bindParam(':start_time', $startTime, PDO::PARAM_STR);
        $insertReq->bindParam(':event_title', $eventTitle, PDO::PARAM_STR);
        $insertReq->bindParam(':event_color', $eventColor, PDO::PARAM_STR);
        $insertReq->bindParam(':duration_hours', $durationHours);
        
        $insertReq->execute();
        
        // Récupérer l'ID généré et stocker l'événement
        $newId = $db->lastInsertId();
        $insertedEvents[] = [
            'event_id' => (int)$newId,
            'user_id' => $userId,
            'weekday_index' => $weekdayIndex,
            'start_time' => $startTime,
            'event_title' => $eventTitle,
            'event_color' => $eventColor,
            'duration_hours' => $durationHours
        ];
    }
    
    // Valider la transaction
    $db->commit();
    
    error_log("Planning sauvegardé : " . count($insertedEvents) . " événements insérés");
    
    sendResponse(true, [
        'message' => 'Planning sauvegardé avec succès',
        'deleted_count' => $deletedCount,
        'inserted_count' => count($insertedEvents),
        'events' => $insertedEvents
    ], 201);
    
} catch(PDOException $e) {
    // Annuler la transaction en cas d'erreur
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    error_log("Erreur save-events : " . $e->getMessage());
    sendResponse(false, ['error' => 'Erreur lors de la sauvegarde du planning'], 500);
}
?>