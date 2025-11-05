import { useNavigate } from "react-router-dom";
import PropTypes from "prop-types";
import { LogOut, LogIn } from "lucide-react";
import "./Navbar.css";

const Navbar = ({ user, onLogout }) => {
  const navigate = useNavigate();

  return (
    <header>
      <nav>
        <div className="nav-left">
          <button
            className="logo-btn"
            onClick={() => navigate("/")}
            aria-label="Retour à l'accueil"
          >
            RE:MIND
          </button>
        </div>

        <div className="nav-right">
          {user ? (
            <>
              <span className="user-name">Bonjour, {user.prenom}</span>
              <button
                className="nav-btn nav-btn-profile"
                onClick={() => navigate("/profil")}
              >
                Mon profil
              </button>
              <button className="nav-btn nav-btn-logout" onClick={onLogout}>
                <LogOut /> Déconnexion
              </button>
            </>
          ) : (
            <>
              <button
                className="nav-btn nav-btn-login"
                onClick={() => navigate("/connexion?mode=login")}
              >
                <LogIn />
                Connexion
              </button>
              <button
                className="nav-btn nav-btn-register"
                onClick={() => navigate("/connexion?mode=register")}
              >
                Inscription
              </button>
            </>
          )}
        </div>
      </nav>
    </header>
  );
};

Navbar.propTypes = {
  user: PropTypes.shape({
    nom: PropTypes.string,
    prenom: PropTypes.string,
    email: PropTypes.string,
  }),
  onLogout: PropTypes.func,
};

export default Navbar;
