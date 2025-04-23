<?php
require_once '../config/cors.php';
addCorsHeaders();

header("Content-Type: application/json");
echo json_encode(["status" => "ok", "message" => "API is working!"]);
?>