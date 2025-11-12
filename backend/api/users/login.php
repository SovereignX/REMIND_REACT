<?php
/**
 * API - Connexion utilisateur
 * POST /api/users/login.php
 * 
 * Corps JSON requis :
 * {
 *   "email": "user@example.com",
 *   "password": "motdepasse"
 * }
 */

require_once '../../config/cors.php';
require_once '../../config/database.php';
require_once '../../config/session.php';
require_once '../../utils/validation.php';

header("Content-Type: application/json; charset=UTF-8");

function sendResponse($success, $data = [], $httpCode = 200) {
    http_response_code($httpCode);
    echo json_encode(array_merge(['success' => $success], $data));
    exit;
}

$json = file_get_contents("php://input");
$data = json_decode($json, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    sendResponse(false, ['message' => 'Données JSON invalides'], 400);
}


// NETTOYAGE ET VALIDATION


// EMAIL : Utiliser cleanEmail()
$email = cleanEmail($data["email"] ?? '');
$password = trim($data["password"] ?? '');  // Juste trim pour le password

// Validation email
if (!$email) {
    sendResponse(false, ['message' => "Format d'email invalide"], 400);
}

try {
    $db = getConnection();
    
    // Requête préparée
    $req = $db->prepare(
        "SELECT user_id, email_address, password_hash, last_name, first_name 
         FROM users 
         WHERE email_address = :email_address 
         LIMIT 1"
    );
    $req->bindParam(':email_address', $email, PDO::PARAM_STR);
    $req->execute();
    
    $user = $req->fetch(PDO::FETCH_ASSOC);
    
    // Vérifier utilisateur et mot de passe
    if (!$user || !password_verify($password, $user["password_hash"])) {
        sendResponse(false, ['message' => 'Email ou mot de passe incorrect'], 401);
    }
    
    // Créer la session avec les données BRUTES de la BDD
    setAuthUser($user["user_id"], [
        'email_address' => $user["email_address"],
        'last_name' => $user["last_name"],
        'first_name' => $user["first_name"]
    ]);
    
    // Supprimer le mot de passe de la réponse
    unset($user['password_hash']);
    
    // json_encode() s'occupe de l'échappement automatiquement
    sendResponse(true, [
        'message' => 'Connexion réussie',
        'userId' => (int)$user["user_id"],
        'user' => [
            'user_id' => (int)$user["user_id"],
            'email_address' => $user["email_address"],
            'last_name' => $user["last_name"],
            'first_name' => $user["first_name"]
        ]
    ], 200);
    
} catch(PDOException $e) {
    error_log("Erreur de connexion : " . $e->getMessage());
    sendResponse(false, ['message' => 'Erreur serveur'], 500);
}
?>