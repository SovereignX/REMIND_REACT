import { useCallback } from "react";
import { authAPI, getErrorMessage, APIError } from "../utils/apiUtils";

export function useAuth() {
  /**
   * Connexion utilisateur
   * @param {string} email - Email de l'utilisateur
   * @param {string} password - Mot de passe
   * @returns {Promise<object>} Résultat de la connexion
   */
  const login = useCallback(async (email, password) => {
    try {
      const data = await authAPI.login(email, password);
      
      // Stocker les informations utilisateur si nécessaire
      if (data.success && data.userInfo) {
        localStorage.setItem("user", JSON.stringify(data.userInfo));
        localStorage.setItem("userId", data.userId);
      }
      
      return data;
    } catch (error) {
      console.error("Erreur de connexion:", error);
      
      return {
        success: false,
        message: getErrorMessage(error),
      };
    }
  }, []);

  /**
   * Inscription utilisateur
   * @param {object} userData - Données du formulaire d'inscription
   * @returns {Promise<object>} Résultat de l'inscription
   */
  const register = useCallback(async (userData) => {
    try {
      const data = await authAPI.register(userData);
      
      // Stocker les informations utilisateur si nécessaire
      if (data.success && data.userInfo) {
        localStorage.setItem("user", JSON.stringify(data.userInfo));
        localStorage.setItem("userId", data.userId);
      }
      
      return data;
    } catch (error) {
      console.error("Erreur d'inscription:", error);
      
      // Gérer les erreurs spécifiques
      if (error instanceof APIError && error.status === 409) {
        return {
          success: false,
          message: "Cet email est déjà utilisé",
        };
      }
      
      return {
        success: false,
        message: getErrorMessage(error),
      };
    }
  }, []);

  /**
   * Déconnexion utilisateur
   * @returns {Promise<object>} Résultat de la déconnexion
   */
  const logout = useCallback(async () => {
    try {
      const data = await authAPI.logout();
      
      // Nettoyer le localStorage
      localStorage.removeItem("user");
      localStorage.removeItem("userId");
      
      return data;
    } catch (error) {
      console.error("Erreur de déconnexion:", error);
      
      // Même en cas d'erreur, on nettoie le localStorage
      localStorage.removeItem("user");
      localStorage.removeItem("userId");
      
      return {
        success: false,
        message: getErrorMessage(error),
      };
    }
  }, []);

  /**
   * Vérifier si l'utilisateur est connecté
   * @returns {boolean} True si connecté
   */
  const isAuthenticated = useCallback(() => {
    const user = localStorage.getItem("user");
    const userId = localStorage.getItem("userId");
    return !!(user && userId);
  }, []);

  /**
   * Obtenir les informations de l'utilisateur connecté
   * @returns {object|null} Informations utilisateur ou null
   */
  const getCurrentUser = useCallback(() => {
    try {
      const userStr = localStorage.getItem("user");
      return userStr ? JSON.parse(userStr) : null;
    } catch {
      return null;
    }
  }, []);

  return {
    login,
    register,
    logout,
    isAuthenticated,
    getCurrentUser,
  };
}

/**
 * Hook pour protéger les routes
 * Utiliser dans les composants qui nécessitent l'authentification
 */
export function useRequireAuth() {
  const { isAuthenticated, getCurrentUser } = useAuth();
  
  const checkAuth = useCallback((navigate) => {
    if (!isAuthenticated()) {
      navigate("/connexion");
      return false;
    }
    return true;
  }, [isAuthenticated]);

  return {
    checkAuth,
    user: getCurrentUser(),
    isAuthenticated: isAuthenticated(),
  };
}