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
    sendResponse(false, ['message' => 'Invalid JSON data'], 400);
}

// ============================================
// CLEANING & VALIDATION
// ============================================

// EMAIL: Use cleanEmail()
$email = cleanEmail($data['email'] ?? '');
if (!$email) {
    sendResponse(false, ['message' => "Invalid email format"], 400);
}

// LAST NAME & FIRST NAME: Use cleanName() - NOT htmlspecialchars!
$lastName = cleanName($data['nom'] ?? $data['last_name'] ?? '');
$firstName = cleanName($data['prenom'] ?? $data['first_name'] ?? '');

// PASSWORD: Just trim, no cleaning (will be hashed)
$password = trim($data['password'] ?? '');
$confirm = trim($data['confirm'] ?? '');

// ============================================
// VALIDATION
// ============================================

$errors = [];

// Email validation (already done by cleanEmail, but double check)
if (empty($email)) {
    $errors[] = "Email is required";
}

// Password validation
$passwordCheck = validatePassword($password);
if (!$passwordCheck['valid']) {
    $errors[] = $passwordCheck['error'];
}

if ($password !== $confirm) {
    $errors[] = "Passwords do not match";
}

// Last name/first name validation
if (empty($lastName)) {
    $errors[] = "Last name is required";
} elseif (containsDangerousChars($lastName)) {
    $errors[] = "Last name contains unauthorized characters";
}

if (empty($firstName)) {
    $errors[] = "First name is required";
} elseif (containsDangerousChars($firstName)) {
    $errors[] = "First name contains unauthorized characters";
}

if (!empty($errors)) {
    sendResponse(false, ['errors' => $errors], 400);
}

// ============================================
// DATABASE INSERTION
// ============================================

try {
    $db = getConnection();
    
    // Check email uniqueness
    $req = $db->prepare("SELECT user_id FROM users WHERE email_address = :email_address LIMIT 1");
    $req->bindParam(':email_address', $email, PDO::PARAM_STR);
    $req->execute();
    
    if ($req->fetch()) {
        sendResponse(false, ['message' => 'This email is already in use'], 409);
    }
    
    // Hash password
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    
    // Insert user with CLEANED data (not HTML-escaped)
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
    
    // Create session
    setAuthUser($userId, [
        'email_address' => $email,
        'last_name' => $lastName,
        'first_name' => $firstName
    ]);
    
    // ✅ json_encode() escapes automatically for JSON
    sendResponse(true, [
        'message' => 'Registration successful',
        'userId' => (int)$userId,
        'user' => [
            'id' => (int)$userId,
            'user_id' => (int)$userId,
            'email' => $email,
            'email_address' => $email,
            'nom' => $lastName, // backward compatibility
            'last_name' => $lastName,
            'prenom' => $firstName, // backward compatibility
            'first_name' => $firstName
        ]
    ], 201);
    
} catch(PDOException $e) {
    error_log("Registration error: " . $e->getMessage());
    sendResponse(false, ['message' => 'Server error'], 500);
}
?>