<?php
require_once '../config/config.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <link rel="stylesheet" href="/REMIND/public/css/styles.css">
    <link href='https://fonts.googleapis.com/css?family=Montserrat' rel='stylesheet'>
    <title></title>
</head>
<body>
    <header>
        <nav>
            <ul>
                <li class="logo"><a href="/REMIND/public">PROJET S.</a></li>
                <!-- <li><a href="/planning">Planning</a></li>
                <li><a href="/about">À propos</a></li> -->
                <?php if (isset($_SESSION['user_name'])): ?>
            <li class="right"><a href="/REMIND/public/profil"><?php echo htmlspecialchars($_SESSION['user_name']); ?> </a></li>
            <li class="right"><a class="logout"href="/REMIND/public/logout.php">Déconnexion</a></li>
        <?php else: ?>
            <li><a href="/REMIND/public/authentication">Connexion / Inscription</a></li>
        <?php endif; ?>
                <!-- <li><a href="/REMIND/public/authentication">Connexion / Inscription</a></li> -->
            </ul>
        </nav>
    </header>
