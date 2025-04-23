import { useNavigate } from "react-router-dom";
import "./Navbar.css";

const Navbar = () => {
  const navigate = useNavigate();

  return (
    <header>
      <nav>
        <ul>
          <li className="logo">
            <a onClick={() => navigate("/")}>PROJET S.</a>
          </li>
          <li>
            <a onClick={() => navigate("/connexion")}>
              Connexion / Inscription
            </a>
          </li>
        </ul>
      </nav>
    </header>
  );
};

export default Navbar;
