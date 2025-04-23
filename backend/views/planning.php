<?php include 'header.php'; ?>
<section class="calendar-container">
    <header class="calendar-header">
        <div class="add-day-button">
            <button id="addDayBtn">Ajouter un jour</button>
        </div>
    </header>
    <div class="calendar-grid">
        <div class="time-column-wrapper">
            <section class="time-column">
                <!-- Heures de 00h00 à 23h59 -->
                <?php for ($hour = 0; $hour < 24; $hour++): ?>
                    <?php for ($minute = 0; $minute < 60; $minute += 30): ?>
                        <div class="time">
                            <?php printf("%02d:%02d", $hour, $minute); ?>
                        </div>
                    <?php endfor; ?>
                <?php endfor; ?>
            </section>
        </div>
        <section id="days-container" class="days-container">
            <!-- Les jours seront générés dynamiquement via JavaScript -->
        </section>
    </div>
</section>
<aside id="activityModal" class="modal" style="display: none;">
    <article class="modal-content">
        <h3>Ajouter une activité</h3>
        <label for="activityName">Nom de l'activité:</label>
        <input type="text" id="activityName" required>
        <label for="startTime">Heure de début (hh:mm):</label>
        <input type="time" id="startTime" required>
        <label for="duration">Durée (en heures):</label>
        <input type="number" id="duration" min="0.5" step="0.5" required>
        <label for="activityColor">Choisir une couleur:</label>
        <input type="color" id="activityColor" value="#b4a7d6">
        <button id="addActivityBtn">Ajouter</button>
        <button id="closeModalBtn">Fermer</button>
    </article>
</aside>
<?php include 'footer.php'; ?>
