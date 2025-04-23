// import { useEffect, useState } from "react";
import { createBrowserRouter, RouterProvider } from "react-router-dom";
import AuthForm from "./components/AuthenticationForm";
import Home from "./components/Home";
import Pomodoro from "./components/Pomodoro";
import Layout from "./ui/Layout";
import Planning from "./components/Planning";
import Profile from "./components/Profile";
// import Profile from './components/Profile';
// import Pomodoro from './components/Pomodoro';
// import TaskList from './components/TaskList';

export default function App() {
  const router = createBrowserRouter([
    {
      path: "/",
      element: <Layout />,
      children: [
        { index: true, element: <Home /> },
        { path: "/pomodoro", element: <Pomodoro /> },
        { path: "/connexion", element: <AuthForm /> },
        { path: "/planning", element: <Planning /> },
        { path: "/profil", element: <Profile /> },
      ],
    },
  ]);

  return <RouterProvider router={router} />;
}
