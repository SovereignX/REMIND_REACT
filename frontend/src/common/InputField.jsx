import React from "react";
import PropTypes from "prop-types";

const InputField = ({
  type,
  name,
  placeholder,
  value,
  onChange,
  required,
  className = "",
}) => {
  return (
    <input
      type={type}
      name={name}
      placeholder={placeholder}
      value={value}
      onChange={onChange}
      required={required}
      className={`input-field ${className}`}
    />
  );
};

InputField.propTypes = {
  type: PropTypes.string.isRequired,
  name: PropTypes.string.isRequired,
  placeholder: PropTypes.string,
  value: PropTypes.string,
  onChange: PropTypes.func.isRequired,
  required: PropTypes.bool,
  className: PropTypes.string,
};

export default InputField;
