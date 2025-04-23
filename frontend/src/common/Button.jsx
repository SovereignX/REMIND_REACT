// Button.jsx
import React from "react";
import PropTypes from "prop-types";

const Button = ({
  children,
  variant = "primary",
  type = "button",
  className = "",
  onClick,
  disabled,
}) => {
  const baseClass = variant === "link" ? "btn-link" : "pomodoro-button";

  return (
    <button
      type={type}
      className={`${baseClass} ${className}`}
      onClick={onClick}
      disabled={disabled}
    >
      {children}
    </button>
  );
};

Button.propTypes = {
  children: PropTypes.node.isRequired,
  variant: PropTypes.oneOf(["primary", "secondary", "link"]),
  type: PropTypes.oneOf(["button", "submit", "reset"]),
  className: PropTypes.string,
  onClick: PropTypes.func,
  disabled: PropTypes.bool,
};

export default Button;
