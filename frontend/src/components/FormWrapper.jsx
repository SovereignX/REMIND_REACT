import React from "react";
import PropTypes from "prop-types";
import "./FormWrapper.css";

const FormWrapper = ({ children, className }) => (
  <div className={`form-wrapper ${className || ""}`}>{children}</div>
);

export const FormSection = ({ children, className }) => (
  <div className={`form-section ${className || ""}`}>{children}</div>
);

FormWrapper.propTypes = {
  children: PropTypes.node.isRequired,
  className: PropTypes.string,
};

FormSection.propTypes = {
  children: PropTypes.node.isRequired,
  className: PropTypes.string,
};

export default FormWrapper;
