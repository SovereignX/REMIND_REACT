import { useNavigate } from "react-router-dom";
import PropTypes from "prop-types";
import PageLayout from "../layouts/PageLayout";
import NavButton from "../common/NavButton";
import { Calendar, Hourglass, ClipboardCheck } from "lucide-react";
import "../styles/Home.css";

const navigationOptions = [
  {
    label: "Planning ",
    path: "/planning",
    description: "Accédez à votre planning hebdomadaire",
  },
  {
    label: "Tâches",
    path: "/taches",
    description: "Evaluez vos tâches et gérez votre temps",
    disabled: true,
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
        <h1 className="home-title">RE:MIND</h1>
        <div className="home-description">
          <p>
            Le timeblocking (<span className="keyword">Planning</span>) consiste
            à planifier sa journée en réservant des plages horaires précises à
            chaque tâche, ce qui permet de mieux organiser son temps et de
            limiter les distractions.
          </p>
          <p>
            Le timeboxing (<span className="keyword">Tâches</span>), quant à
            lui, fixe une durée maximale pour accomplir une tâche donnée,
            obligeant à rester concentré et à prioriser l'essentiel. Ces deux
            méthodes aident à évaluer plus justement le temps réellement
            nécessaire à ses activités.
          </p>
          <p>
            Le <span className="keyword">Pomodoro</span>, lui, découpe le
            travail en sessions courtes de 25 minutes suivies de pauses,
            favorisant la concentration tout en évitant la fatigue mentale. Ces
            approches complémentaires optimisent la gestion du temps et la
            productivité.
          </p>
          <p className="home-cta">
            Grâce à RE:MIND, essayez-les, et découvrez à quel point vos journées
            peuvent devenir fluides et satisfaisantes !
          </p>
        </div>

        <div className="navigation-container">
          {navigationOptions.map((option) => (
            <NavButton
              key={option.path}
              label={option.label}
              tooltip={option.description}
              onClick={() => navigate(option.path)}
              disabled={option.disabled}
            />
          ))}
        </div>
      </div>
    </PageLayout>
  );
};

export default Home;
