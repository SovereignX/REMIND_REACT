import { useCallback } from "react";
import { USER_API_URL } from "../utils/constant";

export function useAuth() {
  const login = useCallback(async (email, password) => {
    try {
      const response = await fetch(`${USER_API_URL}/login.php`, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ email, password }),
      });

      return await response.json();
    } catch (error) {
      console.error("Login error:", error);
      return { success: false, message: "Erreur de connexion au serveur" };
    }
  }, []);

  const register = useCallback(async (userData) => {
    try {
      const response = await fetch(`${USER_API_URL}/register.php`, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify(userData),
      });

      return await response.json();
    } catch (error) {
      console.error("Registration error:", error);
      return { success: false, message: "Erreur de connexion au serveur" };
    }
  }, []);

  return { login, register };
}
