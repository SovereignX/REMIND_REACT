<?php
/**
 * API de déconnexion utilisateur
 * POST /backend/api/users/logout.php
 */

require_once '../../config/cors.php';
require_once '../../config/session.php';

header("Content-Type: application/json; charset=UTF-8");

// Détruire la session
destroyAuthSession();

http_response_code(200);
echo json_encode([
    'success' => true,
    'message' => 'Déconnexion réussie'
]);
