<?php
require_once '../../config/cors.php';
require_once '../../config/database.php';

// Add CORS headers (the function call is already in cors.php, but adding it explicitly won't hurt)
addCorsHeaders();

header("Content-Type: application/json");

$data = json_decode(file_get_contents("php://input"), true);


$username = trim(filter_var($data["username"], FILTER_SANITIZE_STRING));
$password = trim($data["password"]);

$req = $pdo->prepare("SELECT * FROM users WHERE username = ?");
$req->execute([$username]);
$user = $req->fetch();

if (!$user || !password_verify($password, $user["password"])) {
  http_response_code(401);
  echo json_encode(["error" => "Invalid credentials"]);
  exit;
}

echo json_encode(["success" => true, "userId" => $user["id"]]);

?>