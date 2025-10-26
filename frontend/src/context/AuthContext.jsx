import { createContext, useContext, useState, useEffect } from "react";
import PropTypes from "prop-types";
import { authAPI } from "../utils/apiUtils";

const AuthContext = createContext(null);

export function AuthProvider({ children }) {
  const [user, setUser] = useState(null);
  const [loading, setLoading] = useState(true);

  // Charger l'utilisateur au démarrage
  useEffect(() => {
    loadUser();
  }, []);

  /**
   * Charge l'utilisateur depuis le serveur (vérifie la session)
   */
  const loadUser = async () => {
    try {
      const response = await fetch(
        "http://localhost:8000/backend/api/users/profile.php",
        {
          credentials: "include", // Important pour les cookies de session
        }
      );

      if (response.ok) {
        const data = await response.json();
        if (data.success && data.user) {
          setUser(data.user);
        }
      }
    } catch (error) {
      console.error("Erreur lors du chargement de l'utilisateur:", error);
    } finally {
      setLoading(false);
    }
  };

  /**
   * Connexion utilisateur
   */
  const login = async (email, password) => {
    try {
      const data = await authAPI.login(email, password);

      if (data.success && data.user) {
        setUser(data.user);
      }

      return data;
    } catch (error) {
      console.error("Erreur de connexion:", error);
      return {
        success: false,
        message: "Erreur de connexion",
      };
    }
  };

  /**
   * Inscription utilisateur
   */
  const register = async (userData) => {
    try {
      const data = await authAPI.register(userData);

      if (data.success && data.user) {
        setUser(data.user);
      }

      return data;
    } catch (error) {
      console.error("Erreur d'inscription:", error);
      return {
        success: false,
        message: "Erreur d'inscription",
      };
    }
  };

  /**
   * Déconnexion utilisateur
   */
  const logout = async () => {
    try {
      await authAPI.logout();
      setUser(null);
    } catch (error) {
      console.error("Erreur de déconnexion:", error);
      // On déconnecte quand même côté client
      setUser(null);
    }
  };

  /**
   * Rafraîchir les données utilisateur
   */
  const refreshUser = async () => {
    await loadUser();
  };

  const value = {
    user,
    loading,
    login,
    register,
    logout,
    refreshUser,
    isAuthenticated: !!user,
  };

  return <AuthContext.Provider value={value}>{children}</AuthContext.Provider>;
}

AuthProvider.propTypes = {
  children: PropTypes.node.isRequired,
};

/**
 * Hook pour accéder au contexte d'authentification
 */
export function useAuth() {
  const context = useContext(AuthContext);

  if (!context) {
    throw new Error("useAuth doit être utilisé dans un AuthProvider");
  }

  return context;
}
