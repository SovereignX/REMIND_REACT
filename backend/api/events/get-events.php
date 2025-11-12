<?php
/**
 * API - Récupérer les événements de l'utilisateur
 * GET /api/events/get-events.php
 * 
 * Paramètres query optionnels :
 * - weekday_index : filtrer par jour (ex: ?weekday_index=0 pour Lundi)
 * - start_date : date de début (format : YYYY-MM-DD)
 * - end_date : date de fin
 */

require_once '../../config/cors.php';
require_once '../../config/database.php';
require_once '../../config/auth.php';

header('Content-Type: application/json; charset=UTF-8');


// Fonction helper pour les réponses JSON

function sendResponse($success, $data = [], $httpCode = 200) {
    http_response_code($httpCode);
    echo json_encode(array_merge(['success' => $success], $data));
    exit;
}

// Récupérer l'utilisateur authentifié
$userId = getAuthUserId();
if (!$userId) {
    sendResponse(false, ['error' => 'Authentification requise'], 401);
}

try {
    $db = getConnection();
    
    // Construire la requête avec filtres
    $sql = "SELECT event_id, user_id, weekday_index, start_time, event_title, 
                   event_color, duration_hours, created_at, updated_at 
            FROM events 
            WHERE user_id = :user_id";
    
    $params = [':user_id' => $userId];
    
    // Filtre optionnel par weekday_index
    if (isset($_GET['weekday_index']) && is_numeric($_GET['weekday_index'])) {
        $weekdayIndex = intval($_GET['weekday_index']);
        if ($weekdayIndex >= 0 && $weekdayIndex <= 6) {
            $sql .= " AND weekday_index = :weekday_index";
            $params[':weekday_index'] = $weekdayIndex;
        }
    }
    
    // Filtre optionnel par plage de dates
    if (isset($_GET['start_date']) && !empty($_GET['start_date'])) {
        $sql .= " AND created_at >= :start_date";
        $params[':start_date'] = $_GET['start_date'];
    }
    
    if (isset($_GET['end_date']) && !empty($_GET['end_date'])) {
        $sql .= " AND created_at <= :end_date";
        $params[':end_date'] = $_GET['end_date'];
    }
    
    // Tri optimisé par weekday_index puis start_time
    $sql .= " ORDER BY weekday_index ASC, start_time ASC";
    
    // Exécuter la requête
    $req = $db->prepare($sql);
    foreach ($params as $key => $value) {
        $req->bindValue($key, $value);
    }
    $req->execute();
    
    $events = $req->fetchAll(PDO::FETCH_ASSOC);
    
    // Formater les données avec le bon typage
    foreach ($events as &$event) {
        $event['event_id'] = (int)$event['event_id'];
        $event['user_id'] = (int)$event['user_id'];
        $event['weekday_index'] = (int)$event['weekday_index'];
        $event['duration_hours'] = (float)$event['duration_hours'];
    }
    
    sendResponse(true, [
        'count' => count($events),
        'events' => $events
    ], 200);
    
} catch(PDOException $e) {
    error_log("Erreur get-events : " . $e->getMessage());
    sendResponse(false, ['error' => 'Erreur lors de la récupération des événements'], 500);
}
?>