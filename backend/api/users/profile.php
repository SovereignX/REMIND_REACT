<?php
/**
 * API - Récupérer le profil utilisateur connecté
 * GET /backend/api/users/profile.php
 */

require_once '../../config/cors.php';
require_once '../../config/database.php';
require_once '../../config/session.php';

header("Content-Type: application/json; charset=UTF-8");

/**
 * Fonction helper pour les réponses JSON
 */
function sendResponse($success, $data = [], $httpCode = 200) {
    http_response_code($httpCode);
    echo json_encode(array_merge(['success' => $success], $data));
    exit;
}

// Vérifier si l'utilisateur est connecté
if (!isAuthUser()) {
    sendResponse(false, ['error' => 'Non authentifié'], 401);
}

$userId = getAuthUserId();

try {
    $db = getConnection();
    
    // Récupérer les informations complètes de l'utilisateur
    $req = $db->prepare(
        "SELECT id, email, nom, prenom, created_at 
         FROM users 
         WHERE id = :id 
         LIMIT 1"
    );
    $req->bindParam(':id', $userId, PDO::PARAM_INT);
    $req->execute();
    
    $user = $req->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        // L'utilisateur n'existe plus en base
        destroyAuthSession();
        sendResponse(false, ['error' => 'Utilisateur introuvable'], 404);
    }
    
    // Ne jamais renvoyer le mot de passe
    unset($user['password']);
    
    // Convertir l'ID en entier
    $user['id'] = (int)$user['id'];
    
    sendResponse(true, [
        'user' => $user
    ], 200);
    
} catch(PDOException $e) {
    error_log("Erreur profile: " . $e->getMessage());
    sendResponse(false, ['error' => 'Erreur serveur'], 500);
}
