<?php
/**
 * API - Récupérer le profil de l'utilisateur authentifié
 * GET /api/users/profile.php
 */

require_once '../../config/cors.php';
require_once '../../config/database.php';
require_once '../../config/session.php';

header("Content-Type: application/json; charset=UTF-8");


// Fonction helper pour les réponses JSON

function sendResponse($success, $data = [], $httpCode = 200) {
    http_response_code($httpCode);
    echo json_encode(array_merge(['success' => $success], $data));
    exit;
}

// Vérifier si l'utilisateur est authentifié
if (!isAuthUser()) {
    sendResponse(false, ['error' => 'Non authentifié'], 401);
}

$userId = getAuthUserId();

try {
    $db = getConnection();
    
    // Récupérer les informations complètes de l'utilisateur
    $req = $db->prepare(
        "SELECT user_id, email_address, last_name, first_name, created_at 
         FROM users 
         WHERE user_id = :user_id 
         LIMIT 1"
    );
    $req->bindParam(':user_id', $userId, PDO::PARAM_INT);
    $req->execute();
    
    $user = $req->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        // L'utilisateur n'existe plus dans la base de données
        destroyAuthSession();
        sendResponse(false, ['error' => 'Utilisateur non trouvé'], 404);
    }
    
    // Ne jamais renvoyer le mot de passe
    unset($user['password_hash']);
    
    // Convertir l'ID en entier
    $user['user_id'] = (int)$user['user_id'];
    
    sendResponse(true, [
        'user' => $user
    ], 200);
    
} catch(PDOException $e) {
    error_log("Erreur profile : " . $e->getMessage());
    sendResponse(false, ['error' => 'Erreur serveur'], 500);
}
?>