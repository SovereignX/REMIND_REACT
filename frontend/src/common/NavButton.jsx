import PropTypes from "prop-types";
import "../styles/NavButton.css";

const NavButton = ({ label, tooltip, onClick, disabled = false }) => {
  return (
    <div className="button-wrapper">
      <button
        className={`nav-button ${disabled ? "nav-button-disabled" : ""}`}
        onClick={onClick}
        disabled={disabled}
      >
        {label}
      </button>
      <div className="info-icon" title={tooltip}>
        ⓘ
      </div>
    </div>
  );
};

NavButton.propTypes = {
  label: PropTypes.string.isRequired,
  tooltip: PropTypes.string,
  onClick: PropTypes.func.isRequired,
  disabled: PropTypes.bool,
};

export default NavButton;
