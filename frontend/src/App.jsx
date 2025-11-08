import { createBrowserRouter, RouterProvider } from "react-router-dom";
import { AuthProvider } from "./context/AuthContext";
import ProtectedRoute from "./components/ProtectedRoute";
import AuthForm from "./components/AuthenticationForm";
import Home from "./components/Home";
import Pomodoro from "./components/Pomodoro";
import Layout from "./ui/Layout";
import Planning from "./components/Planning";
import Profile from "./components/Profile";

export default function App() {
  const router = createBrowserRouter([
    {
      path: "/",
      element: <Layout />,
      children: [
        // Routes publiques
        { index: true, element: <Home /> },
        { path: "/connexion", element: <AuthForm /> },

        // Routes protégées - nécessitent une authentification
        {
          path: "/pomodoro",
          element: <Pomodoro />,
        },
        {
          path: "/planning",
          element: (
            <ProtectedRoute>
              <Planning />
            </ProtectedRoute>
          ),
        },
        {
          path: "/profil",
          element: (
            <ProtectedRoute>
              <Profile />
            </ProtectedRoute>
          ),
        },
      ],
    },
  ]);

  return (
    <AuthProvider>
      <RouterProvider router={router} />
    </AuthProvider>
  );
}
