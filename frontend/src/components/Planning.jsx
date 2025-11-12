import React, { useState, useRef, useEffect } from "react";
import "./Planning.css";
import { eventsAPI } from "../utils/apiUtils";
import { DAYS, formatTimeRange, formatDuration } from "../utils/dateUtils";

// Générer toutes les heures (00:00 à 23:30)
const allHours = Array.from(
  { length: 48 },
  (_, i) =>
    `${String(Math.floor(i / 2)).padStart(2, "0")}:${i % 2 === 0 ? "00" : "30"}`
);

// Plages horaires prédéfinies
const timeRanges = [
  { label: "Journée complète (00:00 - 23:30)", start: 0, end: 48 },
  { label: "Matinée (06:00 - 14:00)", start: 12, end: 28 },
  { label: "Après-midi (12:00 - 20:00)", start: 24, end: 40 },
  { label: "Journée de travail (07:00 - 19:00)", start: 14, end: 38 },
  { label: "Journée étendue (06:00 - 23:30)", start: 12, end: 48 },
  { label: "Soirée (17:00 - 23:30)", start: 34, end: 48 },
];

const colorChoices = [
  { name: "Bleu", value: "#2196f3" },
  { name: "Rose", value: "#e91e63" },
  { name: "Vert", value: "#4caf50" },
  { name: "Orange", value: "#ff9800" },
  { name: "Violet", value: "#9c27b0" },
  { name: "Gris", value: "#607d8b" },
];

const durationOptions = [
  { label: "30 minutes", value: 1 },
  { label: "1 heure", value: 2 },
  { label: "1h30", value: 3 },
  { label: "2 heures", value: 4 },
];

const PlanningInteractif = () => {
  const [events, setEvents] = useState([]);
  const [modalData, setModalData] = useState({
    show: false,
    day: null,
    hour: null,
  });
  const [editModalData, setEditModalData] = useState({
    show: false,
    event: null,
  });
  const [tempTitle, setTempTitle] = useState("");
  const [tempColor, setTempColor] = useState(colorChoices[0].value);
  const [tempDuration, setTempDuration] = useState(1);
  const [disableDrag, setDisableDrag] = useState(false);
  const [titleError, setTitleError] = useState(false);
  const [selectedRangeIndex, setSelectedRangeIndex] = useState(3); // Par défaut: Journée de travail
  const resizeRef = useRef(null);

  // Calculer la plage d'heures visible et l'offset
  const visibleHours = allHours.slice(
    timeRanges[selectedRangeIndex].start,
    timeRanges[selectedRangeIndex].end
  );
  const hourOffset = timeRanges[selectedRangeIndex].start;

  // Charger les événements au montage
  useEffect(() => {
    loadAllEvents();
  }, []);

  const saveAllEvents = async () => {
    try {
      // Convertir les événements pour l'API
      const eventsForAPI = events.map((event) => ({
        weekday_index: event.weekday_index,
        start_time: allHours[event.time],
        event_title: event.event_title,
        event_color: event.event_color,
        duration_hours: event.duration * 0.5, // Convertir steps en heures
      }));

      const data = await eventsAPI.saveAll(eventsForAPI);
      if (data.success) {
        alert("Planning sauvegardé avec succès!");
        loadAllEvents();
      } else {
        alert("Erreur: " + (data.error || "Impossible de sauvegarder"));
      }
    } catch (err) {
      console.error("Erreur complète:", err);
      alert("Erreur de sauvegarde: " + err.message);
    }
  };

  const loadAllEvents = async () => {
    try {
      const data = await eventsAPI.getAll();
      if (data.success) {
        // Les événements arrivent avec weekday_index (0-6) directement
        const convertedEvents = data.events.map((event) => ({
          event_id: event.event_id,
          weekday_index: event.weekday_index,
          time: allHours.indexOf(event.start_time), // Convertir HH:MM en index
          event_title: event.event_title,
          event_color: event.event_color,
          duration: Math.round(event.duration_hours * 2), // Convertir heures en steps
        }));
        setEvents(convertedEvents);
        console.log("Planning chargé:", convertedEvents);
      }
    } catch (err) {
      console.error("Erreur de chargement:", err);
      alert("Erreur lors du chargement du planning");
    }
  };

  const openModal = (day, hour) => {
    setTempTitle("");
    setTempColor(colorChoices[0].value);
    setTempDuration(1);
    setTitleError(false);
    setModalData({ show: true, day, hour });
  };

  const closeModal = () => setModalData({ show: false, day: null, hour: null });

  const createEvent = async () => {
    if (!tempTitle.trim()) {
      setTitleError(true);
      return;
    }

    const maxSteps = 48 - modalData.hour;
    const safeDuration = Math.min(tempDuration, maxSteps);

    const newEvent = {
      weekday_index: modalData.day,
      start_time: allHours[modalData.hour],
      event_title: tempTitle,
      event_color: tempColor,
      duration_hours: safeDuration * 0.5, // Convertir steps en heures
    };

    console.log("Création événement:", newEvent);

    try {
      const response = await eventsAPI.add(newEvent);
      if (response.success) {
        setEvents([
          ...events,
          {
            event_id: response.event.event_id,
            weekday_index: response.event.weekday_index,
            time: modalData.hour,
            event_title: response.event.event_title,
            event_color: response.event.event_color,
            duration: safeDuration,
          },
        ]);
        closeModal();
      } else {
        alert(
          "Erreur lors de l'ajout : " +
            (response.error || response.errors?.join(", ") || "Erreur inconnue")
        );
      }
    } catch (error) {
      console.error("Erreur createEvent:", error);
      alert("Erreur lors de l'ajout de l'événement");
    }
  };

  const openEditModal = (event) => setEditModalData({ show: true, event });

  const closeEditModal = () => setEditModalData({ show: false, event: null });

  const updateEventField = (field, value) => {
    setEvents(
      events.map((ev) =>
        ev.event_id === editModalData.event.event_id ? { ...ev, [field]: value } : ev
      )
    );
    setEditModalData((prev) => ({
      ...prev,
      event: { ...prev.event, [field]: value },
    }));
  };

  const removeEvent = async () => {
    const eventId = editModalData.event.event_id;
    try {
      const response = await eventsAPI.delete(eventId);
      if (response.success) {
        setEvents(events.filter((e) => e.event_id !== eventId));
        closeEditModal();
      } else {
        alert("Erreur lors de la suppression");
      }
    } catch (error) {
      console.error("Erreur removeEvent:", error);
      alert("Erreur lors de la suppression de l'événement");
    }
  };

  const handleDragStart = (e, eventId) => {
    e.dataTransfer.setData("eventId", eventId);
  };

  const handleDrop = async (e, dayIndex, hourIndex) => {
    e.preventDefault();
    const eventId = parseInt(e.dataTransfer.getData("eventId"));

    const updatedEvents = events.map((ev) =>
      ev.event_id === eventId ? { ...ev, weekday_index: dayIndex, time: hourIndex } : ev
    );
    setEvents(updatedEvents);

    const updated = updatedEvents.find((e) => e.event_id === eventId);
    if (updated) {
      try {
        await eventsAPI.update({
          event_id: updated.event_id,
          weekday_index: dayIndex,
          start_time: allHours[hourIndex],
          event_title: updated.event_title,
          event_color: updated.event_color,
          duration_hours: updated.duration * 0.5,
        });
      } catch (error) {
        console.error("Erreur handleDrop:", error);
        alert("Erreur lors du déplacement de l'événement");
      }
    }
  };

  const handleResizeStart = (e, event) => {
    e.stopPropagation();
    setDisableDrag(true);
    resizeRef.current = { eventId: event.event_id, startY: e.clientY };
    document.addEventListener("mousemove", handleResizeMove);
    document.addEventListener("mouseup", handleResizeEnd);
  };

  const handleResizeMove = async (e) => {
    const { eventId, startY } = resizeRef.current;
    const deltaY = e.clientY - startY;
    const steps = Math.round(deltaY / 40);

    if (steps !== 0) {
      setEvents((prev) =>
        prev.map((ev) => {
          if (ev.event_id !== eventId) return ev;
          const maxSteps = 48 - ev.time;
          const newDuration = Math.max(
            1,
            Math.min(ev.duration + steps, maxSteps)
          );

          // Mettre à jour sur le serveur
          eventsAPI
            .update({
              event_id: ev.event_id,
              weekday_index: ev.weekday_index,
              start_time: allHours[ev.time],
              event_title: ev.event_title,
              event_color: ev.event_color,
              duration_hours: newDuration * 0.5,
            })
            .catch((err) => console.error("Erreur resize:", err));

          return { ...ev, duration: newDuration };
        })
      );

      resizeRef.current.startY = e.clientY;
    }
  };

  const handleResizeEnd = () => {
    setDisableDrag(false);
    document.removeEventListener("mousemove", handleResizeMove);
    document.removeEventListener("mouseup", handleResizeEnd);
    resizeRef.current = null;
  };

  const renderCell = (dayIdx, hourIdx) => {
    // hourIdx est relatif à la plage visible, convertir en index global
    const globalHourIdx = hourIdx + hourOffset;

    const overlappingEvents = events.filter(
      (e) =>
        e.weekday_index === dayIdx &&
        e.time <= globalHourIdx &&
        globalHourIdx < e.time + (e.duration || 1)
    );

    const isStart = (e) => e.time === globalHourIdx;

    return (
      <div
        key={dayIdx}
        className={`planning-cell ${
          overlappingEvents.length ? "occupied" : ""
        }`}
        onClick={() =>
          overlappingEvents.length === 0 && openModal(dayIdx, globalHourIdx)
        }
        onDrop={(e) => handleDrop(e, dayIdx, globalHourIdx)}
        onDragOver={(e) => e.preventDefault()}
      >
        {overlappingEvents.length > 0 && (
          <div className="event-container">
            {overlappingEvents.map(
              (event) =>
                isStart(event) && (
                  <div
                    key={event.event_id}
                    className="event-block"
                    draggable={!disableDrag}
                    onDragStart={(e) => handleDragStart(e, event.event_id)}
                    onDoubleClick={(e) => {
                      e.stopPropagation();
                      openEditModal(event);
                    }}
                    style={{
                      backgroundColor: event.event_color,
                      height: `calc(${event.duration} * 40px - 4px)`,
                      width: `${100 / overlappingEvents.length}%`,
                    }}
                  >
                    <div className="event-title">{event.event_title}</div>
                    <div className="event-time-range">
                      {formatTimeRange(
                        allHours[event.time],
                        event.duration * 0.5
                      )}
                    </div>
                    <div className="event-duration">
                      ({formatDuration(event.duration * 0.5)})
                    </div>
                    <div
                      className="resize-handle"
                      onMouseDown={(e) => handleResizeStart(e, event)}
                    >
                      ⬍
                    </div>
                  </div>
                )
            )}
          </div>
        )}
      </div>
    );
  };

  return (
    <>
      <div className="planning-controls">
        <div className="time-range-selector">
          <label htmlFor="time-range">Plage horaire : </label>
          <select
            id="time-range"
            value={selectedRangeIndex}
            onChange={(e) => setSelectedRangeIndex(Number(e.target.value))}
          >
            {timeRanges.map((range, idx) => (
              <option key={idx} value={idx}>
                {range.label}
              </option>
            ))}
          </select>
        </div>
        <div className="action-buttons">
          <button onClick={loadAllEvents}>Charger</button>
          <button onClick={saveAllEvents}>Sauvegarder</button>
        </div>
      </div>
      <div className="planning">
        <div className="planning-header">
          <div className="planning-time-column" />
          {DAYS.map((dayName, dayIndex) => (
            <div key={dayIndex} className="planning-day-header">
              {dayName}
            </div>
          ))}
        </div>

        <div className="planning-body">
          {visibleHours.map((hour, hourIdx) => (
            <div key={hourIdx} className="planning-row">
              <div className="planning-time-label">{hour}</div>
              {DAYS.map((_, dayIdx) => renderCell(dayIdx, hourIdx))}
            </div>
          ))}
        </div>
      </div>

      {modalData.show && (
        <div className="modal-overlay">
          <div className="modal">
            <h3>Créer un événement</h3>
            <label>
              Titre <span style={{ color: "red" }}>*</span> :
            </label>
            <input
              type="text"
              placeholder="Titre de l'événement"
              value={tempTitle}
              onChange={(e) => {
                setTempTitle(e.target.value);
                if (titleError) setTitleError(false);
              }}
            />
            {titleError && (
              <div className="error-text">Le titre est obligatoire</div>
            )}

            <label>Couleur :</label>
            <select
              value={tempColor}
              onChange={(e) => setTempColor(e.target.value)}
            >
              {colorChoices.map((c, idx) => (
                <option key={idx} value={c.value}>
                  {c.name}
                </option>
              ))}
            </select>
            <div
              className="color-preview"
              style={{ backgroundColor: tempColor }}
            />

            <label>Durée :</label>
            <select
              value={tempDuration}
              onChange={(e) => setTempDuration(Number(e.target.value))}
            >
              {durationOptions.map((d, i) => (
                <option key={i} value={d.value}>
                  {d.label}
                </option>
              ))}
            </select>

            <div className="modal-buttons">
              <button onClick={createEvent}>Ajouter</button>
              <button onClick={closeModal}>Annuler</button>
            </div>
          </div>
        </div>
      )}

      {editModalData.show && (
        <div className="modal-overlay">
          <div className="modal">
            <h3>Éditer l'événement</h3>
            <input
              type="text"
              value={editModalData.event.event_title}
              onChange={(e) => updateEventField("event_title", e.target.value)}
            />
            <label>Couleur :</label>
            <select
              value={editModalData.event.event_color}
              onChange={(e) => updateEventField("event_color", e.target.value)}
            >
              {colorChoices.map((c, i) => (
                <option key={i} value={c.value}>
                  {c.name}
                </option>
              ))}
            </select>
            <div
              className="color-preview"
              style={{ backgroundColor: editModalData.event.event_color }}
            />

            <label>Durée :</label>
            <select
              value={editModalData.event.duration}
              onChange={(e) => {
                const maxSteps = 48 - editModalData.event.time;
                const clamped = Math.min(Number(e.target.value), maxSteps);
                updateEventField("duration", clamped);

                eventsAPI
                  .update({
                    event_id: editModalData.event.event_id,
                    weekday_index: editModalData.event.weekday_index,
                    start_time: allHours[editModalData.event.time],
                    event_title: editModalData.event.event_title,
                    event_color: editModalData.event.event_color,
                    duration_hours: clamped * 0.5,
                  })
                  .catch((err) => console.error("Erreur update:", err));
              }}
            >
              {durationOptions.map((d, i) => (
                <option key={i} value={d.value}>
                  {d.label}
                </option>
              ))}
            </select>

            <div className="modal-buttons">
              <button onClick={closeEditModal}>Fermer</button>
              <button onClick={removeEvent}>Supprimer</button>
            </div>
          </div>
        </div>
      )}
    </>
  );
};

export default PlanningInteractif;