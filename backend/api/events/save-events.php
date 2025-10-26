<?php
/**
 * API - Sauvegarder un planning complet
 * POST /api/events/save-events.php
 * 
 * Remplace tous les événements de l'utilisateur par un nouveau planning
 * 
 * Body JSON requis:
 * {
 *   "events": [
 *     {
 *       "day": "Lundi",
 *       "time": "09:00",
 *       "title": "Réunion",
 *       "color": "#b4a7d6",
 *       "duration": 1.5
 *     },
 *     ...
 *   ]
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

/**
 * Valider un événement individuel
 */
function validateEvent($event, $index) {
    $errors = [];
    
    if (empty($event['day'])) {
        $errors[] = "Événement $index : le jour est requis";
    }
    
    if (empty($event['time']) || !preg_match('/^([0-1]?[0-9]|2[0-3]):[0-5][0-9]$/', $event['time'])) {
        $errors[] = "Événement $index : format d'heure invalide";
    }
    
    if (empty($event['title']) || strlen($event['title']) > 255) {
        $errors[] = "Événement $index : titre invalide";
    }
    
    if (empty($event['color']) || !preg_match('/^#[0-9A-Fa-f]{6}$/', $event['color'])) {
        $errors[] = "Événement $index : format de couleur invalide";
    }
    
    if (!isset($event['duration']) || $event['duration'] <= 0 || $event['duration'] > 24) {
        $errors[] = "Événement $index : durée invalide";
    }
    
    return $errors;
}

// Récupérer les données JSON
$json = file_get_contents("php://input");
$data = json_decode($json, true);

// Vérifier la validité du JSON
if (json_last_error() !== JSON_ERROR_NONE) {
    sendResponse(false, ['error' => 'Format JSON invalide'], 400);
}

// Vérifier la présence du tableau d'événements
if (!isset($data['events']) || !is_array($data['events'])) {
    sendResponse(false, ['error' => 'Le champ "events" doit être un tableau'], 400);
}

// Récupérer l'utilisateur connecté
$userId = getUserId();
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
    
    // Démarrer une transaction
    $db->beginTransaction();
    
    // Supprimer tous les événements existants de l'utilisateur
    $deleteReq = $db->prepare("DELETE FROM events WHERE user_id = :user_id");
    $deleteReq->bindParam(':user_id', $userId, PDO::PARAM_INT);
    $deleteReq->execute();
    
    // Préparer la requête d'insertion
    $insertReq = $db->prepare(
        "INSERT INTO events (user_id, day, time, title, color, duration) 
         VALUES (:user_id, :day, :time, :title, :color, :duration)"
    );
    
    $insertedEvents = [];
    
    // Insérer tous les nouveaux événements
    foreach ($data['events'] as $event) {
        $insertReq->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $insertReq->bindParam(':day', $event['day'], PDO::PARAM_STR);
        $insertReq->bindParam(':time', $event['time'], PDO::PARAM_STR);
        $insertReq->bindParam(':title', $event['title'], PDO::PARAM_STR);
        $insertReq->bindParam(':color', $event['color'], PDO::PARAM_STR);
        $insertReq->bindValue(':duration', floatval($event['duration']));
        
        $insertReq->execute();
        
        // Récupérer l'ID généré et stocker l'événement
        $newId = $db->lastInsertId();
        $event['id'] = (int)$newId;
        $event['user_id'] = $userId;
        $insertedEvents[] = $event;
    }
    
    // Valider la transaction
    $db->commit();
    
    sendResponse(true, [
        'message' => 'Planning sauvegardé avec succès',
        'count' => count($insertedEvents),
        'events' => $insertedEvents
    ], 201);
    
} catch(PDOException $e) {
    // Annuler la transaction en cas d'erreur
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    error_log("Erreur save-events: " . $e->getMessage());
    sendResponse(false, ['error' => 'Erreur lors de la sauvegarde du planning'], 500);
}
?>
