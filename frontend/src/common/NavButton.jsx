import PropTypes from "prop-types";
import "../styles/NavButton.css";

const NavButton = ({ label, tooltip, onClick }) => {
  return (
    <div className="button-wrapper">
      <button className="nav-button" onClick={onClick}>
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
};

export default NavButton;
