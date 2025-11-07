<?php
/**
 * API - Ajouter un événement
 * POST /api/events/add-event.php
 * 
 * Body JSON requis:
 * {
 *   "day_index": 0,       // 0=Lundi, 1=Mardi, ..., 6=Dimanche
 *   "time": "09:00",
 *   "title": "Réunion",
 *   "color": "#b4a7d6",
 *   "duration": 1.5
 * }
 */

require_once '../../config/cors.php';
require_once '../../config/database.php';
require_once '../../config/auth.php';
require_once '../../utils/days.php';
require_once '../../utils/validation.php';  // ← NOUVEAU

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
 * Fonction de validation des données
 */
function validateEventData($data) {
    $errors = [];
    
    // Valider day_index
    if (!isset($data['day_index'])) {
        $errors[] = "L'index du jour est requis";
    } elseif (!isValidDayIndex($data['day_index'])) {
        $errors[] = "L'index du jour doit être entre 0 et 6 (0=Lundi, 6=Dimanche)";
    }
    
    if (empty($data['time']) || !preg_match('/^([0-1]?[0-9]|2[0-3]):[0-5][0-9]$/', $data['time'])) {
        $errors[] = "L'heure est requise et doit être au format HH:MM";
    }
    
    if (empty($data['title']) || strlen($data['title']) > 255) {
        $errors[] = "Le titre est requis (max 255 caractères)";
    }
    
    if (empty($data['color']) || !preg_match('/^#[0-9A-Fa-f]{6}$/', $data['color'])) {
        $errors[] = "La couleur doit être au format hexadécimal (#RRGGBB)";
    }
    
    if (!isset($data['duration']) || $data['duration'] <= 0 || $data['duration'] > 24) {
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

// Récupérer l'utilisateur connecté
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
// NETTOYER LES DONNÉES (SÉCURITÉ XSS)
// ============================================

$dayIndex = intval($data['day_index']);
$time = trim($data['time']);

// ✅ IMPORTANT : Nettoyer le titre pour éviter XSS
$title = cleanEventTitle($data['title']);

// Vérifier que le titre nettoyé n'est pas vide
if (empty($title)) {
    sendResponse(false, ['error' => 'Le titre ne peut pas être vide ou contenir uniquement des balises HTML'], 400);
}

// Vérifier les patterns dangereux restants
if (containsDangerousChars($title)) {
    sendResponse(false, ['error' => 'Le titre contient des éléments non autorisés'], 400);
}

$color = trim($data['color']);
$duration = floatval($data['duration']);

// ============================================
// INSÉRER EN BASE DE DONNÉES
// ============================================

try {
    $db = getConnection();
    
    $req = $db->prepare(
        "INSERT INTO events (user_id, day_index, time, title, color, duration) 
         VALUES (:user_id, :day_index, :time, :title, :color, :duration)"
    );
    
    $req->bindParam(':user_id', $userId, PDO::PARAM_INT);
    $req->bindParam(':day_index', $dayIndex, PDO::PARAM_INT);
    $req->bindParam(':time', $time, PDO::PARAM_STR);
    $req->bindParam(':title', $title, PDO::PARAM_STR);  // Titre nettoyé
    $req->bindParam(':color', $color, PDO::PARAM_STR);
    $req->bindParam(':duration', $duration);
    
    $req->execute();
    
    $eventId = $db->lastInsertId();
    
    // Log pour debug (optionnel)
    error_log("Événement créé: ID=$eventId, Jour=" . dayIndexToName($dayIndex) . " ($dayIndex), Heure=$time");
    
    sendResponse(true, [
        'message' => 'Événement ajouté avec succès',
        'event' => [
            'id' => $eventId,
            'user_id' => $userId,
            'day_index' => $dayIndex,
            'time' => $time,
            'title' => $title,  // Titre nettoyé retourné
            'color' => $color,
            'duration' => $duration
        ]
    ], 201);
    
} catch(PDOException $e) {
    error_log("Erreur add-event: " . $e->getMessage());
    sendResponse(false, ['error' => 'Erreur lors de l\'ajout de l\'événement'], 500);
}
?>