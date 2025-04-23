import PropTypes from "prop-types";
//import "../styles/PageLayout.css";

const PageLayout = ({ children, className }) => {
  return <div className={`page-container ${className || ""}`}>{children}</div>;
};

PageLayout.propTypes = {
  children: PropTypes.node.isRequired,
  className: PropTypes.string,
};

export default PageLayout;
