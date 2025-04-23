<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");

function getConnection() {
    $host = "localhost";
    $dbname = "remind";  // Your database name
    $username = "sovereign";       // Your database username
    $password = "Password123!";           // Your database password

    try {
        $db = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $db;
    } catch(PDOException $e) {
        echo json_encode(["error" => "Connection failed: " . $e->getMessage()]);
        exit;
    }
}
?>
