import PropTypes from "prop-types";
import Button from "../common/Button";
import "../styles/Profile.css";

const Profile = ({ user, onModify, onDelete, error }) => {
  return (
    <div className="profile-container">
      <div className="profile-info">
        <p>
          <strong>Nom :</strong> {user?.nom || "Non renseigné"}
        </p>
        <p>
          <strong>Prénom :</strong> {user?.prenom || "Non renseigné"}
        </p>
        <p>
          <strong>Email :</strong> {user?.email || "Non renseigné"}
        </p>

        <Button className="modify-button" onClick={onModify} variant="primary">
          Modifier
        </Button>

        <Button className="delete-button" onClick={onDelete} variant="danger">
          Supprimer le compte
        </Button>
      </div>

      <div className="profile-content">
        <h1>Placeholder Planning</h1>

        {error && <p className="error-messages">{error}</p>}
      </div>
    </div>
  );
};

Profile.propTypes = {
  user: PropTypes.shape({
    pseudo: PropTypes.string,
    nom: PropTypes.string,
    prenom: PropTypes.string,
    email: PropTypes.string,
    photo: PropTypes.string,
  }),
  onModify: PropTypes.func.isRequired,
  onDelete: PropTypes.func.isRequired,
  error: PropTypes.string,
};

export default Profile;
