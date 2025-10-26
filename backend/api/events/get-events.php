<?php
/**
 * API - Récupérer les événements de l'utilisateur
 * GET /api/events/get-events.php
 * 
 * Query params optionnels:
 * - day: filtrer par jour (ex: ?day=Lundi)
 * - start_date: date de début (format: YYYY-MM-DD)
 * - end_date: date de fin
 */

require_once '../../config/cors.php';
require_once '../../config/database.php';
require_once '../../config/auth.php';

header('Content-Type: application/json; charset=UTF-8');

/**
 * Fonction helper pour les réponses JSON
 */
function sendResponse($success, $data = [], $httpCode = 200) {
    http_response_code($httpCode);
    echo json_encode(array_merge(['success' => $success], $data));
    exit;
}

// Récupérer l'utilisateur connecté
$userId = getUserId();
if (!$userId) {
    sendResponse(false, ['error' => 'Authentification requise'], 401);
}

try {
    $db = getConnection();
    
    // Construction de la requête selon les filtres
    $sql = "SELECT id, user_id, day, time, title, color, duration, 
                   created_at, updated_at 
            FROM events 
            WHERE user_id = :user_id";
    
    $params = [':user_id' => $userId];
    
    // Filtre optionnel par jour
    if (isset($_GET['day']) && !empty($_GET['day'])) {
        $sql .= " AND day = :day";
        $params[':day'] = trim($_GET['day']);
    }
    
    // Filtre optionnel par plage de dates (si vous utilisez des dates)
    if (isset($_GET['start_date']) && !empty($_GET['start_date'])) {
        $sql .= " AND created_at >= :start_date";
        $params[':start_date'] = $_GET['start_date'];
    }
    
    if (isset($_GET['end_date']) && !empty($_GET['end_date'])) {
        $sql .= " AND created_at <= :end_date";
        $params[':end_date'] = $_GET['end_date'];
    }
    
    // Tri par jour et heure
    $sql .= " ORDER BY 
              CASE day
                  WHEN 'Lundi' THEN 1
                  WHEN 'Mardi' THEN 2
                  WHEN 'Mercredi' THEN 3
                  WHEN 'Jeudi' THEN 4
                  WHEN 'Vendredi' THEN 5
                  WHEN 'Samedi' THEN 6
                  WHEN 'Dimanche' THEN 7
              END,
              time ASC";
    
    // Exécution de la requête
    $req = $db->prepare($sql);
    foreach ($params as $key => $value) {
        $req->bindValue($key, $value);
    }
    $req->execute();
    
    $events = $req->fetchAll(PDO::FETCH_ASSOC);
    
    // Formatage des données
    foreach ($events as &$event) {
        $event['id'] = (int)$event['id'];
        $event['user_id'] = (int)$event['user_id'];
        $event['duration'] = (float)$event['duration'];
    }
    
    sendResponse(true, [
        'count' => count($events),
        'events' => $events
    ], 200);
    
} catch(PDOException $e) {
    error_log("Erreur get-events: " . $e->getMessage());
    sendResponse(false, ['error' => 'Erreur lors de la récupération des événements'], 500);
}
?>
