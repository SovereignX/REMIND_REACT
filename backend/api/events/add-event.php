<?php
/**
 * API - Ajouter un événement
 * POST /api/events/add-event.php
 * 
 * Corps JSON requis :
 * {
 *   "weekday_index": 0,       // 0=Lundi, 1=Mardi, ..., 6=Dimanche
 *   "start_time": "09:00",
 *   "event_title": "Réunion",
 *   "event_color": "#b4a7d6",
 *   "duration_hours": 1.5
 * }
 */

require_once '../../config/cors.php';
require_once '../../config/database.php';
require_once '../../config/auth.php';
require_once '../../utils/days.php';
require_once '../../utils/validation.php';

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
 * Validation des données de l'événement
 */
function validateEventData($data) {
    $errors = [];
    
    // Valider weekday_index
    if (!isset($data['weekday_index'])) {
        $errors[] = "L'index du jour est requis";
    } elseif (!isValidDayIndex($data['weekday_index'])) {
        $errors[] = "L'index du jour doit être entre 0 et 6 (0=Lundi, 6=Dimanche)";
    }
    
    if (empty($data['start_time']) || !preg_match('/^([0-1]?[0-9]|2[0-3]):[0-5][0-9]$/', $data['start_time'])) {
        $errors[] = "L'heure de début est requise et doit être au format HH:MM";
    }
    
    if (empty($data['event_title']) || strlen($data['event_title']) > 255) {
        $errors[] = "Le titre est requis (max 255 caractères)";
    }
    
    if (empty($data['event_color']) || !preg_match('/^#[0-9A-Fa-f]{6}$/', $data['event_color'])) {
        $errors[] = "La couleur doit être au format hexadécimal (#RRGGBB)";
    }
    
    if (!isset($data['duration_hours']) || $data['duration_hours'] <= 0 || $data['duration_hours'] > 24) {
        $errors[] = "La durée doit être entre 0.5 et 24 heures";
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

// Récupérer l'utilisateur authentifié
$userId = getUserId();
if (!$userId) {
    sendResponse(false, ['error' => 'Authentification requise'], 401);
}

// Valider les données
$errors = validateEventData($data);
if (!empty($errors)) {
    sendResponse(false, ['errors' => $errors], 400);
}

// ============================================
// NETTOYAGE DES DONNÉES (SÉCURITÉ XSS)
// ============================================

$weekdayIndex = intval($data['weekday_index']);
$startTime = trim($data['start_time']);

// ✅ IMPORTANT : Nettoyer le titre pour prévenir les XSS
$eventTitle = cleanEventTitle($data['event_title']);

// Vérifier que le titre nettoyé n'est pas vide
if (empty($eventTitle)) {
    sendResponse(false, ['error' => 'Le titre ne peut pas être vide ou contenir uniquement des balises HTML'], 400);
}

// Vérifier qu'il ne contient pas de patterns dangereux
if (containsDangerousChars($eventTitle)) {
    sendResponse(false, ['error' => 'Le titre contient des éléments non autorisés'], 400);
}

$eventColor = trim($data['event_color']);
$durationHours = floatval($data['duration_hours']);

// ============================================
// INSERTION DANS LA BASE DE DONNÉES
// ============================================

try {
    $db = getConnection();
    
    $req = $db->prepare(
        "INSERT INTO events (user_id, weekday_index, start_time, event_title, event_color, duration_hours) 
         VALUES (:user_id, :weekday_index, :start_time, :event_title, :event_color, :duration_hours)"
    );
    
    $req->bindParam(':user_id', $userId, PDO::PARAM_INT);
    $req->bindParam(':weekday_index', $weekdayIndex, PDO::PARAM_INT);
    $req->bindParam(':start_time', $startTime, PDO::PARAM_STR);
    $req->bindParam(':event_title', $eventTitle, PDO::PARAM_STR);
    $req->bindParam(':event_color', $eventColor, PDO::PARAM_STR);
    $req->bindParam(':duration_hours', $durationHours);
    
    $req->execute();
    
    $eventId = $db->lastInsertId();
    
    // Log pour debug (optionnel)
    error_log("Événement créé : ID=$eventId, Jour=" . dayIndexToName($weekdayIndex) . " ($weekdayIndex), Heure=$startTime");
    
    sendResponse(true, [
        'message' => 'Événement ajouté avec succès',
        'event' => [
            'event_id' => $eventId,
            'user_id' => $userId,
            'weekday_index' => $weekdayIndex,
            'start_time' => $startTime,
            'event_title' => $eventTitle,
            'event_color' => $eventColor,
            'duration_hours' => $durationHours
        ]
    ], 201);
    
} catch(PDOException $e) {
    error_log("Erreur add-event : " . $e->getMessage());
    sendResponse(false, ['error' => 'Erreur lors de l\'ajout de l\'événement'], 500);
}
?>