<?php
require_once '../../config/cors.php';
require_once '../../config/database.php';
require_once '../../config/session.php';

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

// ✅ PAS de htmlspecialchars sur l'email
$email = trim($data["email"] ?? '');
$password = trim($data["password"] ?? '');

// Validation email
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    sendResponse(false, ['message' => "Format d'email invalide"], 400);
}

try {
    $db = getConnection();
    
    // Normalisation pour recherche (même que lors de l'inscription)
    $email = strtolower($email);
    
    // Requête préparée
    $req = $db->prepare(
        "SELECT id, email, password, nom, prenom 
         FROM users 
         WHERE email = :email 
         LIMIT 1"
    );
    $req->bindParam(':email', $email, PDO::PARAM_STR);
    $req->execute();
    
    $user = $req->fetch(PDO::FETCH_ASSOC);
    
    // Vérifier utilisateur et mot de passe
    if (!$user || !password_verify($password, $user["password"])) {
        sendResponse(false, ['message' => 'Email ou mot de passe incorrect'], 401);
    }
    
    // Créer la session
    setAuthUser($user["id"], [
        'email' => $user["email"],    // Email ORIGINAL
        'nom' => $user["nom"],
        'prenom' => $user["prenom"]
    ]);
    
    // Supprimer le mot de passe de la réponse
    unset($user['password']);
    
    sendResponse(true, [
        'message' => 'Connexion réussie',
        'userId' => (int)$user["id"],
        'user' => [
            'id' => (int)$user["id"],
            'email' => $user["email"],  // Email ORIGINAL
            'nom' => $user["nom"],
            'prenom' => $user["prenom"]
        ]
    ], 200);
    
} catch(PDOException $e) {
    error_log("Erreur de connexion: " . $e->getMessage());
    sendResponse(false, ['message' => 'Erreur serveur'], 500);
}
?>