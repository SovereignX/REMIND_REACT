<?php
/**
 * API d'inscription utilisateur
 * Crée un nouveau compte utilisateur dans la base de données
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

// Nettoyer et valider les champs
$email = sanitize($data['email'] ?? '');
$password = trim($data['password'] ?? '');
$confirm = trim($data['confirm'] ?? '');
$nom = sanitize($data['nom'] ?? '');
$prenom = sanitize($data['prenom'] ?? '');

$errors = [];

// Validation email
if (empty($email)) {
    $errors[] = "L'email est requis";
} elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = "Format d'email invalide";
}

// Validation mot de passe
if (empty($password)) {
    $errors[] = "Le mot de passe est requis";
} elseif (strlen($password) < 8) {
    $errors[] = "Le mot de passe doit contenir au moins 8 caractères";
}

// Validation confirmation
if ($password !== $confirm) {
    $errors[] = "Les mots de passe ne correspondent pas";
}

// Validation nom/prénom
if (empty($nom)) {
    $errors[] = "Le nom est requis";
}

if (empty($prenom)) {
    $errors[] = "Le prénom est requis";
}

// Si erreurs de validation
if (!empty($errors)) {
    sendResponse(false, ['errors' => $errors], 400);
}

// Traitement base de données
try {
    $db = getConnection();
    
    // Vérifier si l'email existe déjà
    $req = $db->prepare("SELECT id FROM users WHERE email = :email LIMIT 1");
    $req->bindParam(':email', $email, PDO::PARAM_STR);
    $req->execute();
    
    if ($req->fetch()) {
        sendResponse(false, ['message' => 'Cet email est déjà utilisé'], 409);
    }
    
    // Hasher le mot de passe
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    
    // Insérer le nouvel utilisateur
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
    
    // Succès - Renvoyer les infos utilisateur (sans le mot de passe)
    sendResponse(true, [
        'message' => 'Inscription réussie',
        'userId' => $userId,
        'userInfo' => [
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