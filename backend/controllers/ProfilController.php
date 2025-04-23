<?php
// class ProfilController {
//     public function showProfil() {
//         // Inclure la vue pour le formulaire d'inscription et de connexion
//         require_once '../app/views/profil.php';
//     }
// }
class ProfilController {

    // METHODE AFFICHAGE PROFIL
    public function showProfil() {
        // Vérifier si l'utilisateur est connecté
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!isset($_SESSION['user_id'])) {
            header("Location: /REMIND/public/authentication");
            exit();
        }

        // Connexion à la base de données
        require_once '../config/database.php';
        $db = getDatabaseConnection();

        // Récupérer les informations de l'utilisateur
        $user_id = $_SESSION['user_id'];
        $req = $db->prepare("SELECT prenom_utilisateur, nom_utilisateur, email_utilisateur FROM utilisateur WHERE id_utilisateur = ?");
        $req->execute([$user_id]);
        $user = $req->fetch(PDO::FETCH_ASSOC);

        // Passer les données à la vue
        require_once '../app/views/profil.php';
    }

    // METHODE SUPPRESSION PROFIL
    public function deleteUser() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!isset($_SESSION['user_id'])) {
            header("Location: /REMIND/public/authentication");
            exit();
        }

        // Connexion à la base de données
        require_once '../config/database.php';
        $db = getDatabaseConnection();

        // Supprimer l'utilisateur de la base de données
        $user_id = $_SESSION['user_id'];
        try {
            $req = $db->prepare("DELETE FROM utilisateur WHERE id_utilisateur = ?");
            $req->execute([$user_id]);

            // Détruire la session
            session_destroy();

            // Rediriger vers la page d'accueil ou d'inscription
            header("Location: /REMIND/public/authentication");
            exit();
        } catch (PDOException $error) {
            echo "Erreur lors de la suppression du compte : " . $error->getMessage();
        }
    }

        // METHODE MODIFICATION PROFIL WIP
}
?>