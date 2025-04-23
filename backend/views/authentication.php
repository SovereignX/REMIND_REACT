<?php 
include 'header.php'; 
include '../config/config.php'; //bdd

$error = "";
require_once '../app/views/authentication.php';

if (isset($_SESSION['user_id'])) {
    header("Location: /REMIND/public/profil");
    exit();
}
?>

<div class="auth-container">
    <div class="form-wrapper">
        <!-- INSCRIPTION -->
        <div class="form-section">
            <h2>Inscription</h2>
            <?php
            ?>
            <form action="/REMIND/public/register" method="post">
                <button class="buttonGoogle">Créer un compte avec Google</button>
                <label for="firstname">Prénom :</label>
                <input type="text" id="firstname" name="firstname" placeholder="Prénom" >

                <label for="lastname">Nom :</label>
                <input type="text" id="lastname" name="lastname" placeholder="Nom" >

                <label for="email">Email :</label>
                <input type="email" id="email" name="email" placeholder="Email" >

                <label for="password">Mot de passe (8 caractères minimum):</label>
                <input type="password" id="password" name="password" placeholder="Mot de passe" >

                <label for="passwordVerify">Confirmer mot de passe :</label>
                <input type="password" id="passwordVerify" name="passwordVerify" placeholder="Confirmer mot de passe" >

                <button id="authenticationButton" name="register" type="submitRegister">CRÉER UN COMPTE</button>
                <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
            </form>
            <?php if (!empty($registerErrors)): ?>
            <div class="error-messages">
                <ul>
                    <?php foreach ($registerErrors as $error): ?>
                        <li><?php echo htmlspecialchars($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        </div>


        <div class="separateur"></div>

        <!-- CONNEXION -->
        <div class="form-section">
            <h2>Connexion</h2>
            <?php
            if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
                $email = htmlspecialchars($_POST['email']);
                $password = $_POST['password'];

                try {
                    require_once '../config/database.php';
                    $db = getDatabaseConnection();

                    // Préparation de la requête (selectionne le mdp dans utilisateur par rapport à l'email)
                    $sql = "SELECT id_utilisateur, mdp_utilisateur FROM utilisateur WHERE email_utilisateur = ?";
                    $req = $db->prepare($sql);
                    $req->execute([$email]);
                    $user = $req->fetch(PDO::FETCH_ASSOC);

                    if ($user) {
                        // Vérifie le mot de passe utilisateur
                        if (password_verify($password, $user['mdp_utilisateur'])) {
                            // Crée une session utilisateur
                            session_start();
                            $_SESSION['user_id'] = $user['id_utilisateur'];
                            $_SESSION['user_email'] = $email;
                            header("Location: profil.php");
                            exit();
                        } else {
                            $loginErrors[] = "Mot de passe incorrect.";
                        }
                    } else {
                        $loginErrors[] = "Aucun utilisateur trouvé avec cet email.";
                    }
                } catch (PDOException $error) {
                    $loginErrors[] = "Erreur lors de la connexion : " . $error->getMessage();
                }
}
            ?>
            <form action="/REMIND/public/login" method="post">
                <button class="buttonGoogle">Se connecter avec Google</button>
                <label for="login-email">Email :</label>
                <input type="email" id="login-email" name="email" placeholder="Email">

                <label for="login-password">Mot de passe :</label>
                <input type="password" id="login-password" name="password" placeholder="Mot de passe">

                <button id="authenticationButton" name="login" type="submitLogin">CONNEXION</button>
            </form>
            <?php if (!empty($loginErrors)): ?>
            <div class="error-messages">
                <ul>
                    <?php foreach ($loginErrors as $error): ?>
                        <li><?php echo htmlspecialchars($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>
