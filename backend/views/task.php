<?php include 'header.php'; ?>

<div class="task-container">
    <form id="task-form" class="task-form">
        <input type="text" name="title" placeholder="Task title" required>
        <input type="number" name="duration" placeholder="Duration (minutes)" required>
        <select name="method_type" required>
            <option value="timeboxing">Timeboxing</option>
            <option value="timeblocking">Timeblocking</option>
            <option value="pomodoro">Pomodoro</option>
        </select>
        <button type="submit">Create Task</button>
    </form>

    <div class="hourglass-container">
        <div class="hourglass">
            <div class="top-sand"></div>
            <div class="bottom-sand"></div>
        </div>
        <div class="timer-display">
            <span id="minutes">00</span>:<span id="seconds">00</span>
        </div>
        <div class="timer-controls">
            <button id="start-timer" class="timer-btn">Start</button>
            <button id="pause-timer" class="timer-btn" disabled>Pause</button>
            <button id="reset-timer" class="timer-btn" disabled>Reset</button>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>
