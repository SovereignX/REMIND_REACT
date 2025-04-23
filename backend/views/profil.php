<?php
include 'header.php';
include '../config/config.php';
// session_start();


if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    header("Location: /REMIND/public/authentication");
    exit();
}

// if (!isset($_SESSION['user_id'])) {
//     header("Location: index.php");
//     exit();
// }

$user_id = $_SESSION['user_id'];
$sql = "SELECT prenom_utilisateur, nom_utilisateur, email_utilisateur FROM utilisateur WHERE id_utilisateur = ?";
$req = $conn->prepare($sql);
$req->bind_param("i", $user_id);
$req->execute();
$req->bind_result($firstname, $lastname, $email);
$req->fetch();
$req->close();
?>
<div class="profile-container">
    <!-- COLONNE GAUCHE : INFO -->
    <div class="profile-info">
        <h1>Profil</h1>
        <p><strong>Prénom :</strong> <?php echo htmlspecialchars($firstname); ?></p>
        <p><strong>Nom :</strong> <?php echo htmlspecialchars($lastname); ?></p>
        <p><strong>Email :</strong> <?php echo htmlspecialchars($email); ?></p>
        <form action="/REMIND/public/modify-user" method="post">
            <button type="submit" class="modify-button">Modifier mon compte</button>
        </form>
        <!-- SUPPRESSION BOUTON -->
        <form action="/REMIND/public/delete-user" method="post" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer votre compte ? Cette action est irréversible.')">
            <button type="submit" class="delete-button">Supprimer mon compte</button>
        </form>
    </div>

    <!-- COLONNE DROITE : CONTENT -->
    <div class="profile-content">
        <!-- PLACEHOLDER -->
        <h2>Placeholder Planning (?)</h2>
        <p>Placeholder Paragraphe (?)</p>
    </div>
</div>
<?php include 'footer.php'; ?>
