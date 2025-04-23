import { useNavigate } from "react-router-dom";
import PropTypes from "prop-types";
import PageLayout from "../layouts/PageLayout";
import NavButton from "../common/NavButton";
import "../styles/Home.css";

const navigationOptions = [
  {
    label: "Planning",
    path: "/planning",
    description: "Accédez à votre planning hebdomadaire",
  },
  {
    label: "Tâches",
    path: "/taches",
    description: "Evaluez vos tâches et gérez votre temps",
  },
  {
    label: "Pomodoro",
    path: "/pomodoro",
    description: "Utilisez la technique Pomodoro pour votre productivité",
  },
];

const Home = () => {
  const navigate = useNavigate();

  return (
    <PageLayout>
      <div className="home-container">
        <h1 className="home-title">PROJET S.</h1>
        <div className="home-description">
          <p>
            Lorem ipsum dolor sit amet, consectetur adipiscing elit. Morbi
            elementum lobortis quam non vestibulum. Aenean tempor blandit diam
            vitae tristique. Lorem ipsum dolor sit amet, consectetur adipiscing
            elit. Morbi elementum lobortis quam non vestibulum. Aenean tempor
            blandit diam vitae tristique.
          </p>
          <p>
            Lorem ipsum dolor sit amet, consectetur adipiscing elit. Morbi
            elementum lobortis quam non vestibulum. Aenean tempor blandit diam
            vitae tristique. Lorem ipsum dolor sit amet, consectetur adipiscing
            elit.
          </p>
        </div>

        <div className="navigation-container">
          {navigationOptions.map((option) => (
            <NavButton
              key={option.path}
              label={option.label}
              tooltip={option.description}
              onClick={() => navigate(option.path)}
            />
          ))}
        </div>
      </div>
    </PageLayout>
  );
};

export default Home;
