<?php
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

// ============================================
// NETTOYAGE & VALIDATION
// ============================================

// EMAIL : Utiliser cleanEmail()
$email = cleanEmail($data['email'] ?? '');
if (!$email) {
    sendResponse(false, ['message' => "Format d'email invalide"], 400);
}

// NOM & PRÉNOM : Utiliser cleanName() - PAS htmlspecialchars !
$nom = cleanName($data['nom'] ?? '');
$prenom = cleanName($data['prenom'] ?? '');

// MOT DE PASSE : Juste trim, pas de nettoyage (sera hashé)
$password = trim($data['password'] ?? '');
$confirm = trim($data['confirm'] ?? '');

// ============================================
// VALIDATION
// ============================================

$errors = [];

// Validation email (déjà fait par cleanEmail, mais double check)
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
if (empty($nom)) {
    $errors[] = "Le nom est requis";
} elseif (containsDangerousChars($nom)) {
    $errors[] = "Le nom contient des caractères non autorisés";
}

if (empty($prenom)) {
    $errors[] = "Le prénom est requis";
} elseif (containsDangerousChars($prenom)) {
    $errors[] = "Le prénom contient des caractères non autorisés";
}

if (!empty($errors)) {
    sendResponse(false, ['errors' => $errors], 400);
}

// ============================================
// INSERTION EN BASE
// ============================================

try {
    $db = getConnection();
    
    // Vérifier unicité email
    $req = $db->prepare("SELECT id FROM users WHERE email = :email LIMIT 1");
    $req->bindParam(':email', $email, PDO::PARAM_STR);
    $req->execute();
    
    if ($req->fetch()) {
        sendResponse(false, ['message' => 'Cet email est déjà utilisé'], 409);
    }
    
    // Hasher le mot de passe
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    
    // Insérer l'utilisateur avec les données NETTOYÉES (pas échappées HTML)
    $req = $db->prepare(
        "INSERT INTO users (email, password, nom, prenom) 
         VALUES (:email, :password, :nom, :prenom)"
    );
    
    $req->bindParam(':email', $email, PDO::PARAM_STR);
    $req->bindParam(':password', $hashedPassword, PDO::PARAM_STR);
    $req->bindParam(':nom', $nom, PDO::PARAM_STR);
    $req->bindParam(':prenom', $prenom, PDO::PARAM_STR);
    
    $req->execute();
    
    $userId = $db->lastInsertId();
    
    // Créer la session
    setAuthUser($userId, [
        'email' => $email,
        'nom' => $nom,
        'prenom' => $prenom
    ]);
    
    // ✅ json_encode() échappe automatiquement pour JSON
    sendResponse(true, [
        'message' => 'Inscription réussie',
        'userId' => (int)$userId,
        'user' => [
            'id' => (int)$userId,
            'email' => $email,
            'nom' => $nom,
            'prenom' => $prenom
        ]
    ], 201);
    
} catch(PDOException $e) {
    error_log("Erreur d'inscription: " . $e->getMessage());
    sendResponse(false, ['message' => 'Erreur serveur'], 500);
}
?>