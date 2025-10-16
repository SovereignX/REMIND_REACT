import { useNavigate } from "react-router-dom";
import "./Footer.css";

const Footer = () => {
  const navigate = useNavigate();

  return (
    <footer>
      <p>&copy; 2025 RE:MIND</p>
      <ul>
        <li>
          <a onClick={() => navigate("/")}>Mentions Légales</a>
        </li>
      </ul>
    </footer>
  );
};
export default Footer;
