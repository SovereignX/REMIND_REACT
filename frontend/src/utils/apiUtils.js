//? Utilitaires pour les appels API
//? Gestion centralisée des requêtes et des erreurs

// Configuration de base
const config = {
  baseURL: "http://localhost:8000/backend/api",
  timeout: 10000,
  headers: {
    "Content-Type": "application/json",
  },
};

// Types d'erreurs
export class APIError extends Error {
  constructor(message, status, data) {
    super(message);
    this.name = "APIError";
    this.status = status;
    this.data = data;
  }
}

/**
 * Effectue une requête HTTP avec gestion d'erreurs
 * @param {string} endpoint - Point de terminaison de l'API
 * @param {object} options - Options fetch
 * @returns {Promise<object>} Réponse JSON
 */
export async function apiRequest(endpoint, options = {}) {
  const url = `${config.baseURL}${endpoint}`;
  
  const defaultOptions = {
    headers: config.headers,
    credentials: "include", // Important pour les sessions PHP
  };

  const mergedOptions = {
    ...defaultOptions,
    ...options,
    headers: {
      ...defaultOptions.headers,
      ...options.headers,
    },
  };

  try {
    const controller = new AbortController();
    const timeoutId = setTimeout(() => controller.abort(), config.timeout);

    const response = await fetch(url, {
      ...mergedOptions,
      signal: controller.signal,
    });

    clearTimeout(timeoutId);

    // Tenter de parser la réponse JSON
    let data;
    try {
      data = await response.json();
    } catch {
      data = { message: "Réponse invalide du serveur" };
    }

    // Si la réponse n'est pas OK, lancer une erreur
    if (!response.ok) {
      throw new APIError(
        data.message || `Erreur HTTP ${response.status}`,
        response.status,
        data
      );
    }

    return data;
  } catch (error) {
    // Gestion des différents types d'erreurs
    if (error.name === "AbortError") {
      throw new APIError("Délai d'attente dépassé", 408);
    }

    if (error instanceof APIError) {
      throw error;
    }

    // Erreur réseau ou autre
    throw new APIError(
      "Erreur de connexion au serveur",
      0,
      { originalError: error.message }
    );
  }
}

// Requête GET
export async function get(endpoint, params = {}) {
  const queryString = new URLSearchParams(params).toString();
  const url = queryString ? `${endpoint}?${queryString}` : endpoint;
  
  return apiRequest(url, {
    method: "GET",
  });
}

// Requête POST
export async function post(endpoint, data = {}) {
  return apiRequest(endpoint, {
    method: "POST",
    body: JSON.stringify(data),
  });
}

// Requête PUT
export async function put(endpoint, data = {}) {
  return apiRequest(endpoint, {
    method: "PUT",
    body: JSON.stringify(data),
  });
}

// Requête DELETE
export async function del(endpoint, data = {}) {
  return apiRequest(endpoint, {
    method: "DELETE",
    body: JSON.stringify(data),
  });
}

// Hook personnalisé pour gérer les erreurs
export function getErrorMessage(error) {
  if (error instanceof APIError) {
    // Messages d'erreur personnalisés selon le code HTTP
    switch (error.status) {
      case 400:
        return error.message || "Données invalides";
      case 401:
        return "Identifiants incorrects";
      case 403:
        return "Accès non autorisé";
      case 404:
        return "Ressource introuvable";
      case 409:
        return error.message || "Conflit de données";
      case 422:
        return "Données de formulaire invalides";
      case 500:
        return "Erreur serveur. Veuillez réessayer.";
      case 408:
        return "Délai d'attente dépassé";
      default:
        return error.message || "Une erreur est survenue";
    }
  }

  return "Erreur inconnue";
}

// API Endpoints
export const endpoints = {
  auth: {
    login: "/users/login.php",
    register: "/users/register.php",
    logout: "/users/logout.php",
    profile: "/users/profile.php",
  },
  events: {
    getAll: "/events/get-events.php",
    add: "/events/add-event.php",
    update: "/events/update-event.php",
    delete: "/events/delete-event.php",
    saveAll: "/events/save-events.php",
  },
};

// Fonctions spécifiques pour l'authentification
export const authAPI = {
  async login(email, password) {
    return post(endpoints.auth.login, { email, password });
  },

  async register(userData) {
    return post(endpoints.auth.register, userData);
  },

  async logout() {
    return post(endpoints.auth.logout);
  },

  async getProfile() {
    return get(endpoints.auth.profile);
  },
};

// Fonctions spécifiques pour les événements
export const eventsAPI = {
  async getAll() {
    return get(endpoints.events.getAll);
  },

  async add(event) {
    return post(endpoints.events.add, event);
  },

  async update(event) {
    return post(endpoints.events.update, event);
  },

  async delete(eventId) {
    return post(endpoints.events.delete, { event_id: eventId });
  },

  async saveAll(events) {
    return post(endpoints.events.saveAll, { events });
  },
};

// Validation côté client
export const validators = {
  email(email) {
    const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return re.test(email);
  },

  password(password, minLength = 8) {
    return password.length >= minLength;
  },

  required(value) {
    return value !== null && value !== undefined && value.trim() !== "";
  },
};

export default {
  get,
  post,
  put,
  del,
  authAPI,
  eventsAPI,
  endpoints,
  validators,
  getErrorMessage,
};
