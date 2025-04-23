import { useState } from "react";
import { useNavigate } from "react-router-dom";
import PropTypes from "prop-types";
import FormWrapper, { FormSection } from "./FormWrapper";
import Button from "../common/Button";
import InputField from "../common/InputField";
import ErrorMessage from "../common/ErrorMessage";
import { useAuth } from "../hooks/Auth";
import "./FormWrapper.css";

export default function AuthenticationForm({ onLogin }) {
  const [mode, setMode] = useState("login");
  const [formData, setFormData] = useState({
    email: "",
    password: "",
    confirm: "",
    nom: "",
    prenom: "",
  });
  const [error, setError] = useState(null);
  const navigate = useNavigate();
  const { login, register } = useAuth();

  const handleChange = (e) => {
    const { name, value } = e.target;
    setFormData((prev) => ({ ...prev, [name]: value }));
  };

  const validateForm = () => {
    if (mode === "register" && formData.password !== formData.confirm) {
      setError("Les mots de passe ne correspondent pas.");
      return false;
    }
    return true;
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    setError(null);

    if (!validateForm()) return;

    try {
      const data =
        mode === "login"
          ? await login(formData.email, formData.password)
          : await register(formData);

      if (data.success) {
        onLogin(data.user);
        navigate("/");
      } else {
        setError(data.message || "Une erreur est survenue.");
      }
    } catch (error) {
      console.error("Erreur lors de la soumission du formulaire :", error);
      setError("Erreur serveur. Veuillez réessayer plus tard.");
    }
  };

  const toggleMode = () => setMode(mode === "login" ? "register" : "login");

  return (
    <form className="auth-form" onSubmit={handleSubmit}>
      <FormWrapper>
        <FormSection>
          {mode === "register" && (
            <>
              <InputField
                type="text"
                name="nom"
                placeholder="Nom"
                value={formData.nom}
                onChange={handleChange}
                required
              />
              <InputField
                type="text"
                name="prenom"
                placeholder="Prénom"
                value={formData.prenom}
                onChange={handleChange}
                required
              />
            </>
          )}
          <InputField
            type="email"
            name="email"
            placeholder="Adresse mail"
            value={formData.email}
            onChange={handleChange}
            required
          />
        </FormSection>

        <FormSection>
          <InputField
            type="password"
            name="password"
            placeholder="Mot de passe"
            value={formData.password}
            onChange={handleChange}
            required
          />
          {mode === "register" && (
            <InputField
              type="password"
              name="confirm"
              placeholder="Confirmation du mot de passe"
              value={formData.confirm}
              onChange={handleChange}
              required
            />
          )}
          <Button type="submit">
            {mode === "login" ? "Se connecter" : "Créer un compte"}
          </Button>

          {error && <ErrorMessage message={error} />}

          <p>
            {mode === "login" ? (
              <>
                Pas encore de compte ?{" "}
                <Button variant="link" onClick={toggleMode}>
                  S'inscrire
                </Button>
              </>
            ) : (
              <>
                Déjà un compte ?{" "}
                <Button variant="link" onClick={toggleMode}>
                  Se connecter
                </Button>
              </>
            )}
          </p>
        </FormSection>
      </FormWrapper>
    </form>
  );
}

AuthenticationForm.propTypes = {
  onLogin: PropTypes.func.isRequired,
};
