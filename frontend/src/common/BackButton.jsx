import { useNavigate } from "react-router-dom";
import { ArrowBigLeft } from "lucide-react";
import PropTypes from "prop-types";
import "../styles/BackButton.css";

const BackButton = ({ className = "" }) => {
  const navigate = useNavigate();

  return (
    <button
      className={`back-button ${className}`}
      onClick={() => navigate(-1)}
      aria-label="Retour à la page précédente"
    >
      <ArrowBigLeft size={24} />
      <span>Retour</span>
    </button>
  );
};

BackButton.propTypes = {
  className: PropTypes.string,
};

export default BackButton;
