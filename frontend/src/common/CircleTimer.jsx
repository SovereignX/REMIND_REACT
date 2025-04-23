import PropTypes from "prop-types";

export default function CircleTimer({
  percentage,
  text,
  size = 200,
  strokeWidth = 10,
}) {
  const radius = size / 2 - strokeWidth;
  const circumference = 2 * Math.PI * radius;
  const offset = circumference - (percentage / 100) * circumference;
  const centerPos = size / 2;

  return (
    <div className="circle-timer">
      <svg width={size} height={size}>
        <circle
          cx={centerPos}
          cy={centerPos}
          r={radius}
          stroke="#e6e6e6"
          fill="none"
          strokeWidth={strokeWidth}
        />
        <circle
          cx={centerPos}
          cy={centerPos}
          r={radius}
          stroke="#ff6347"
          fill="none"
          strokeWidth={strokeWidth}
          strokeDasharray={circumference}
          strokeDashoffset={offset}
          transform={`rotate(-90 ${centerPos} ${centerPos})`}
          style={{ transition: "stroke-dashoffset 1s linear" }}
        />
        <text
          x="50%"
          y="50%"
          dominantBaseline="middle"
          textAnchor="middle"
          fontSize="24"
        >
          {text}
        </text>
      </svg>
    </div>
  );
}

CircleTimer.propTypes = {
  percentage: PropTypes.number.isRequired,
  text: PropTypes.string.isRequired,
  size: PropTypes.number,
  strokeWidth: PropTypes.number,
};
