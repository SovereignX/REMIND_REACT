import { Outlet } from "react-router-dom";
import Navbar from "./Navbar";
import Footer from "./Footer";
import { useAuth } from "../context/AuthContext";

export default function Layout() {
  const { user, logout, loading } = useAuth();

  if (loading) {
    return (
      <div style={{ textAlign: "center", padding: "2rem" }}>
        Chargement...
      </div>
    );
  }

  return (
    <>
      <Navbar user={user} onLogout={logout} />
      <main>
        <Outlet />
      </main>
      <Footer />
    </>
  );
}
