import { useState } from "react";
import { useNavigate } from "react-router-dom";
import PropTypes from "prop-types";
import { useAuth } from "../hooks/Auth";
import "./AuthenticationForm.css";

export default function AuthenticationForm({ onLogin }) {
  const [mode, setMode] = useState("login");
  const [formData, setFormData] = useState({
    email: "",
    password: "",
    confirm: "",
    nom: "",
    prenom: "",
  });
  const [errors, setErrors] = useState({});
  const [isLoading, setIsLoading] = useState(false);
  const navigate = useNavigate();
  const { login, register } = useAuth();

  const handleChange = (e) => {
    const { name, value } = e.target;
    setFormData((prev) => ({ ...prev, [name]: value }));
    // Clear error for this field when user starts typing
    if (errors[name]) {
      setErrors((prev) => ({ ...prev, [name]: "" }));
    }
  };

  const validateForm = () => {
    const newErrors = {};

    // Email validation
    if (!formData.email.trim()) {
      newErrors.email = "L'email est requis";
    } else if (!/\S+@\S+\.\S+/.test(formData.email)) {
      newErrors.email = "Format d'email invalide";
    }

    // Password validation
    if (!formData.password) {
      newErrors.password = "Le mot de passe est requis";
    } else if (mode === "register" && formData.password.length < 8) {
      newErrors.password = "Le mot de passe doit contenir au moins 8 caractères";
    }

    // Register-specific validations
    if (mode === "register") {
      if (!formData.nom.trim()) {
        newErrors.nom = "Le nom est requis";
      }
      if (!formData.prenom.trim()) {
        newErrors.prenom = "Le prénom est requis";
      }
      if (formData.password !== formData.confirm) {
        newErrors.confirm = "Les mots de passe ne correspondent pas";
      }
    }

    setErrors(newErrors);
    return Object.keys(newErrors).length === 0;
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    
    if (!validateForm()) return;

    setIsLoading(true);
    setErrors({});

    try {
      const data =
        mode === "login"
          ? await login(formData.email, formData.password)
          : await register(formData);

      if (data.success) {
        onLogin?.(data.userInfo || data.user);
        navigate("/");
      } else {
        setErrors({ general: data.message || "Une erreur est survenue" });
      }
    } catch (error) {
      console.error("Erreur lors de la soumission du formulaire :", error);
      setErrors({ general: "Erreur serveur. Veuillez réessayer plus tard." });
    } finally {
      setIsLoading(false);
    }
  };

  const toggleMode = () => {
    setMode(mode === "login" ? "register" : "login");
    setErrors({});
  };

  return (
    <div className="auth-container">
      <div className="auth-card">
        <div className="auth-header">
          <h1>
            {mode === "login" ? "Connexion" : "Créer un compte"}
          </h1>
          <p className="auth-subtitle">
            {mode === "login"
              ? "Connectez-vous pour accéder à votre espace"
              : "Rejoignez-nous et organisez votre temps"}
          </p>
        </div>

        <form className="auth-form" onSubmit={handleSubmit}>
          {/* Register fields */}
          {mode === "register" && (
            <div className="form-row">
              <div className="input-group">
                <label htmlFor="prenom" className="input-label">
                  Prénom <span className="required">*</span>
                </label>
                <input
                  type="text"
                  id="prenom"
                  name="prenom"
                  placeholder="Votre prénom"
                  value={formData.prenom}
                  onChange={handleChange}
                  className={errors.prenom ? "input-error" : ""}
                  disabled={isLoading}
                />
                {errors.prenom && (
                  <span className="error-message">{errors.prenom}</span>
                )}
              </div>

              <div className="input-group">
                <label htmlFor="nom" className="input-label">
                  Nom <span className="required">*</span>
                </label>
                <input
                  type="text"
                  id="nom"
                  name="nom"
                  placeholder="Votre nom"
                  value={formData.nom}
                  onChange={handleChange}
                  className={errors.nom ? "input-error" : ""}
                  disabled={isLoading}
                />
                {errors.nom && (
                  <span className="error-message">{errors.nom}</span>
                )}
              </div>
            </div>
          )}

          {/* Email field */}
          <div className="input-group">
            <label htmlFor="email" className="input-label">
              Email <span className="required">*</span>
            </label>
            <input
              type="email"
              id="email"
              name="email"
              placeholder="votre@email.com"
              value={formData.email}
              onChange={handleChange}
              className={errors.email ? "input-error" : ""}
              disabled={isLoading}
            />
            {errors.email && (
              <span className="error-message">{errors.email}</span>
            )}
          </div>

          {/* Password field */}
          <div className="input-group">
            <label htmlFor="password" className="input-label">
              Mot de passe <span className="required">*</span>
            </label>
            <input
              type="password"
              id="password"
              name="password"
              placeholder="••••••••"
              value={formData.password}
              onChange={handleChange}
              className={errors.password ? "input-error" : ""}
              disabled={isLoading}
            />
            {errors.password && (
              <span className="error-message">{errors.password}</span>
            )}
          </div>

          {/* Confirm password (register only) */}
          {mode === "register" && (
            <div className="input-group">
              <label htmlFor="confirm" className="input-label">
                Confirmer le mot de passe <span className="required">*</span>
              </label>
              <input
                type="password"
                id="confirm"
                name="confirm"
                placeholder="••••••••"
                value={formData.confirm}
                onChange={handleChange}
                className={errors.confirm ? "input-error" : ""}
                disabled={isLoading}
              />
              {errors.confirm && (
                <span className="error-message">{errors.confirm}</span>
              )}
            </div>
          )}

          {/* General error */}
          {errors.general && (
            <div className="error-message general-error">
              {errors.general}
            </div>
          )}

          {/* Submit button */}
          <button
            type="submit"
            className="auth-submit-btn"
            disabled={isLoading}
          >
            {isLoading
              ? "Chargement..."
              : mode === "login"
              ? "Se connecter"
              : "Créer mon compte"}
          </button>

          {/* Toggle mode */}
          <div className="auth-toggle">
            {mode === "login" ? (
              <p>
                Pas encore de compte ?{" "}
                <button
                  type="button"
                  className="toggle-btn"
                  onClick={toggleMode}
                  disabled={isLoading}
                >
                  S'inscrire
                </button>
              </p>
            ) : (
              <p>
                Déjà un compte ?{" "}
                <button
                  type="button"
                  className="toggle-btn"
                  onClick={toggleMode}
                  disabled={isLoading}
                >
                  Se connecter
                </button>
              </p>
            )}
          </div>
        </form>
      </div>
    </div>
  );
}

AuthenticationForm.propTypes = {
  onLogin: PropTypes.func,
};