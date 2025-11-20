<?php
/**
 * API - Inscription utilisateur
 * POST /api/users/register.php
 * 
 * Corps JSON requis :
 * {
 *   "email": "user@example.com",
 *   "password": "motdepasse",
 *   "confirm": "motdepasse",
 *   "last_name": "Nom",
 *   "first_name": "Prénom"
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


// NETTOYAGE & VALIDATION


// EMAIL : Utiliser cleanEmail()
$email = cleanEmail($data['email'] ?? '');
if (!$email) {
    sendResponse(false, ['message' => "Format d'email invalide"], 400);
}

// NOM & PRÉNOM : Utiliser cleanName() - PAS htmlspecialchars!
$lastName = cleanName($data['last_name'] ?? '');
$firstName = cleanName($data['first_name'] ?? '');

// MOT DE PASSE : Juste trim, pas de nettoyage (sera hashé)
$password = trim($data['password'] ?? '');
$confirm = trim($data['confirm'] ?? '');


// VALIDATION

$errors = [];

// Validation email (déjà fait par cleanEmail, mais double vérification)
if (empty($email)) {
    $errors[] = "L'email est requis";
}

// Validation mot de passe
$passwordCheck = validatePassword($password);
if (!$passwordCheck['valid']) {
    $errors[] = $passwordCheck['error'];
}

if ($password !== $confirm) {
    $errors[] = "Les mots de passe ne correspondent pas";
}

// Validation nom/prénom
if (empty($lastName)) {
    $errors[] = "Le nom est requis";
} 

if (empty($firstName)) {
    $errors[] = "Le prénom est requis";
} 

if (!empty($errors)) {
    sendResponse(false, ['errors' => $errors], 400);
}


// INSERTION DANS LA BASE DE DONNÉES

try {
    $db = getConnection();
    
    // Vérifier l'unicité de l'email
    $req = $db->prepare("SELECT user_id FROM users WHERE email_address = :email_address LIMIT 1");
    $req->bindParam(':email_address', $email, PDO::PARAM_STR);
    $req->execute();
    
    if ($req->fetch()) {
        sendResponse(false, ['message' => 'Cet email est déjà utilisé'], 409);
    }
    
    // Hasher le mot de passe
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    
    // Insérer l'utilisateur avec les données NETTOYÉES (pas échappées HTML)
    $req = $db->prepare(
        "INSERT INTO users (email_address, password_hash, last_name, first_name) 
         VALUES (:email_address, :password_hash, :last_name, :first_name)"
    );
    
    $req->bindParam(':email_address', $email, PDO::PARAM_STR);
    $req->bindParam(':password_hash', $hashedPassword, PDO::PARAM_STR);
    $req->bindParam(':last_name', $lastName, PDO::PARAM_STR);
    $req->bindParam(':first_name', $firstName, PDO::PARAM_STR);
    
    $req->execute();
    
    $userId = $db->lastInsertId();
    
    // Créer la session
    setAuthUser($userId, [
        'email_address' => $email,
        'last_name' => $lastName,
        'first_name' => $firstName
    ]);
    
    // json_encode() échappe automatiquement pour JSON
    sendResponse(true, [
        'message' => 'Inscription réussie',
        'userId' => (int)$userId,
        'user' => [
            'user_id' => (int)$userId,
            'email_address' => $email,
            'last_name' => $lastName,
            'first_name' => $firstName
        ]
    ], 201);
    
} catch(PDOException $e) {
    error_log("Erreur inscription : " . $e->getMessage());
    sendResponse(false, ['message' => 'Erreur serveur'], 500);
}
?>