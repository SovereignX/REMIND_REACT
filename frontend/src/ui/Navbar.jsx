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
              <button
                className="nav-btn nav-btn-logout nav-btn-icon"
                onClick={onLogout}
              >
                <LogOut size={18} />
                <span>Déconnexion</span>
              </button>
            </>
          ) : (
            <>
              <button
                className="nav-btn nav-btn-register"
                onClick={() => navigate("/connexion?mode=register")}
              >
                Inscription
              </button>
              <button
                className="nav-btn nav-btn-login nav-btn-icon"
                onClick={() => navigate("/connexion?mode=login")}
              >
                <LogIn size={18} />
                <span>Connexion</span>
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
