<?php
/**
 * Fichier de compatibilité pour les endpoints existants
 * 
 * Ce fichier sert uniquement d'alias pour session.php
 * afin de ne pas avoir à modifier tous les require_once dans les endpoints.
 * 
 * Toutes les fonctions d'authentification sont définies dans session.php
 */

require_once __DIR__ . '/session.php';
?>