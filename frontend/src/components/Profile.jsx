import { useAuth } from "../context/AuthContext";
import { useNavigate } from "react-router-dom";
import { useEffect } from "react";
import "../styles/Profile.css";

const Profile = () => {
  const { user, isAuthenticated } = useAuth();
  const navigate = useNavigate();

  useEffect(() => {
    if (!isAuthenticated) {
      navigate("/connexion?mode=login");
    }
  }, [isAuthenticated, navigate]);

  if (!user) {
    return <div>Chargement...</div>;
  }

  return (
    <div className="profile-container">
      <div className="profile-info">
        <h1>Mon Profil</h1>
        <p><strong>Prénom :</strong> {user.prenom}</p>
        <p><strong>Nom :</strong> {user.nom}</p>
        <p><strong>Email :</strong> {user.email}</p>
        <p><strong>Membre depuis :</strong> {new Date(user.created_at).toLocaleDateString()}</p>
      </div>
      <div className="profile-content">
        <h2>Vos statistiques</h2>
        <p>À venir...</p>
      </div>
    </div>
  );
};

export default Profile;
