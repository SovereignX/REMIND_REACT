<?php
/**
 * API de connexion utilisateur
 * Authentifie un utilisateur et retourne ses informations
 */

require_once '../../config/cors.php';
require_once '../../config/database.php';

header("Content-Type: application/json; charset=UTF-8");

// Fonction de nettoyage des entrées
function sanitize($data) {
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

// Fonction de réponse JSON
function sendResponse($success, $data = [], $httpCode = 200) {
    http_response_code($httpCode);
    echo json_encode(array_merge(['success' => $success], $data));
    exit;
}

// Récupérer et valider les données JSON
$json = file_get_contents("php://input");
$data = json_decode($json, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    sendResponse(false, ['message' => 'Données JSON invalides'], 400);
}

// Vérifier les champs requis
if (!isset($data["email"]) || !isset($data["password"])) {
    sendResponse(false, ['message' => 'Email et mot de passe requis'], 400);
}

$email = sanitize($data["email"]);
$password = trim($data["password"]);

// Validation de l'email
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    sendResponse(false, ['message' => "Format d'email invalide"], 400);
}

// Traitement de la base de données
try {
    $db = getConnection();
    
    // Requête préparée pour éviter les injections SQL
    $req = $db->prepare(
        "SELECT id, email, password, nom, prenom 
         FROM users 
         WHERE email = :email 
         LIMIT 1"
    );
    $req->bindParam(':email', $email, PDO::PARAM_STR);
    $req->execute();
    
    $user = $req->fetch(PDO::FETCH_ASSOC);
    
    // Vérifier l'utilisateur et le mot de passe
    if (!$user || !password_verify($password, $user["password"])) {
        sendResponse(false, ['message' => 'Email ou mot de passe incorrect'], 401);
    }
    
    // Succès - Ne jamais renvoyer le mot de passe
    unset($user['password']);
    
    sendResponse(true, [
        'message' => 'Connexion réussie',
        'userId' => $user["id"],
        'userInfo' => [
            'email' => $user["email"],
            'nom' => $user["nom"],
            'prenom' => $user["prenom"]
        ]
    ], 200);
    
} catch(PDOException $e) {
    error_log("Erreur de connexion: " . $e->getMessage());
    sendResponse(false, ['message' => 'Erreur serveur'], 500);
}
?>