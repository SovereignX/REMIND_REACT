import React, { useState, useRef, useEffect } from "react";
import "./Planning.css";
import { eventsAPI } from "../utils/apiUtils";
import { DAYS, formatTimeRange, formatDuration } from "../utils/dateUtils";

const hours = Array.from(
  { length: 48 },
  (_, i) =>
    `${String(Math.floor(i / 2)).padStart(2, "0")}:${i % 2 === 0 ? "00" : "30"}`
);

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
  const resizeRef = useRef(null);

  // 🔁 Load events on mount
  useEffect(() => {
    loadAllEvents();
  }, []);

  const saveAllEvents = async () => {
    try {
      // Convertir les événements pour l'API
      const eventsForAPI = events.map((event) => ({
        day_index: event.day_index, // ✅ Utiliser day_index
        time: hours[event.time],
        title: event.title,
        color: event.color,
        duration: event.duration * 0.5, // Convertir steps en heures
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
        // Les événements arrivent avec day_index (0-6) directement
        const convertedEvents = data.events.map((event) => ({
          ...event,
          time: hours.indexOf(event.time), // Convertir HH:MM en index
          duration: Math.round(event.duration * 2), // Convertir heures en steps
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
      day_index: modalData.day, // ✅ Envoyer l'index directement (0-6)
      time: hours[modalData.hour],
      title: tempTitle,
      color: tempColor,
      duration: safeDuration * 0.5, // Convertir steps en heures
    };

    console.log("Création événement:", newEvent); // Debug

    try {
      const response = await eventsAPI.add(newEvent);
      if (response.success) {
        setEvents([
          ...events,
          {
            ...response.event,
            time: modalData.hour, // Index pour l'affichage
            duration: safeDuration, // En steps pour l'affichage
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
        ev.id === editModalData.event.id ? { ...ev, [field]: value } : ev
      )
    );
    setEditModalData((prev) => ({
      ...prev,
      event: { ...prev.event, [field]: value },
    }));
  };

  const removeEvent = async () => {
    const id = editModalData.event.id;
    try {
      const response = await eventsAPI.delete(id);
      if (response.success) {
        setEvents(events.filter((e) => e.id !== id));
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
      ev.id === eventId ? { ...ev, day_index: dayIndex, time: hourIndex } : ev
    );
    setEvents(updatedEvents);

    const updated = updatedEvents.find((e) => e.id === eventId);
    if (updated) {
      try {
        await eventsAPI.update({
          id: updated.id,
          day_index: dayIndex, // ✅ Envoyer day_index
          time: hours[hourIndex],
          title: updated.title,
          color: updated.color,
          duration: updated.duration * 0.5,
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
    resizeRef.current = { eventId: event.id, startY: e.clientY };
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
          if (ev.id !== eventId) return ev;
          const maxSteps = 48 - ev.time;
          const newDuration = Math.max(
            1,
            Math.min(ev.duration + steps, maxSteps)
          );

          // Mettre à jour sur le serveur
          eventsAPI
            .update({
              id: ev.id,
              day_index: ev.day_index, // ✅ Envoyer day_index
              time: hours[ev.time],
              title: ev.title,
              color: ev.color,
              duration: newDuration * 0.5,
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
    const overlappingEvents = events.filter(
      (e) =>
        e.day_index === dayIdx && // ✅ Utiliser day_index
        e.time <= hourIdx &&
        hourIdx < e.time + (e.duration || 1)
    );

    const isStart = (e) => e.time === hourIdx;

    return (
      <div
        key={dayIdx}
        className={`planning-cell ${
          overlappingEvents.length ? "occupied" : ""
        }`}
        onClick={() =>
          overlappingEvents.length === 0 && openModal(dayIdx, hourIdx)
        }
        onDrop={(e) => handleDrop(e, dayIdx, hourIdx)}
        onDragOver={(e) => e.preventDefault()}
      >
        {overlappingEvents.length > 0 && (
          <div className="event-container">
            {overlappingEvents.map(
              (event) =>
                isStart(event) && (
                  <div
                    key={event.id}
                    className="event-block"
                    draggable={!disableDrag}
                    onDragStart={(e) => handleDragStart(e, event.id)}
                    onDoubleClick={(e) => {
                      e.stopPropagation();
                      openEditModal(event);
                    }}
                    style={{
                      backgroundColor: event.color,
                      height: `calc(${event.duration} * 40px - 4px)`,
                      width: `${100 / overlappingEvents.length}%`,
                    }}
                  >
                    <div className="event-title">{event.title}</div>
                    <div className="event-time-range">
                      {formatTimeRange(hours[event.time], event.duration * 0.5)}
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
        <button onClick={loadAllEvents}>Charger</button>
        <button onClick={saveAllEvents}>Sauvegarder</button>
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
          {hours.map((hour, hourIdx) => (
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
              value={editModalData.event.title}
              onChange={(e) => updateEventField("title", e.target.value)}
            />
            <label>Couleur :</label>
            <select
              value={editModalData.event.color}
              onChange={(e) => updateEventField("color", e.target.value)}
            >
              {colorChoices.map((c, i) => (
                <option key={i} value={c.value}>
                  {c.name}
                </option>
              ))}
            </select>
            <div
              className="color-preview"
              style={{ backgroundColor: editModalData.event.color }}
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
                    id: editModalData.event.id,
                    day_index: editModalData.event.day_index, // ✅ Envoyer day_index
                    time: hours[editModalData.event.time],
                    title: editModalData.event.title,
                    color: editModalData.event.color,
                    duration: clamped * 0.5,
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
