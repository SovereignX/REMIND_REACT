import { Outlet, useNavigate } from "react-router-dom";
import Navbar from "./Navbar";
import Footer from "./Footer";
import { useEffect, useState } from "react";

export default function Layout() {
  const navigate = useNavigate();
  const [user, setUser] = useState(null);

  useEffect(() => {
    fetch("/php/getProfile.php")
      .then((res) => res.json())
      .then((data) => {
        if (data.success) setUser(data.user);
      });
  }, []);

  const handleLogout = async () => {
    await fetch("/php/logout.php");
    setUser(null);
    navigate("/connexion");
  };

  return (
    <>
      <Navbar user={user} onLogout={handleLogout} />
      <main>
        <Outlet context={{ user, setUser }} />
      </main>
      <Footer />
    </>
  );
}
