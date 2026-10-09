<<<<<<< Updated upstream
<<<<<<< Updated upstream

<?php
session_start();

/* ADMIN ACCESS */

if (
    !isset($_SESSION['user_id']) ||
    ($_SESSION['role'] ?? '') !== 'admin'
) {
    header("Location: account.php");
    exit;
}

/* DATABASE CONNECTION */

require_once "database/database.php";

/* SECURITY */

function esc($value) {
    return htmlspecialchars(
        (string)($value ?? ''),
        ENT_QUOTES,
        'UTF-8'
    );
}

/* VALIDATE IMAGE FILENAME */

function validImageName($name) {
    return $name === '' ||
        (
            basename($name) === $name &&
            preg_match(
                '/^[a-zA-Z0-9._-]+\.(jpg|jpeg|png|webp|gif)$/i',
                $name
            )
        );
}

/* CSRF TOKEN */

if (empty($_SESSION['admin_csrf'])) {
    $_SESSION['admin_csrf'] = bin2hex(random_bytes(32));
}

/* PROCESS FORM */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $token = $_POST['csrf'] ?? '';

    if (
        !is_string($token) ||
        !hash_equals($_SESSION['admin_csrf'], $token)
    ) {
        http_response_code(403);
        exit("Invalid request.");
    }

    /* ADD EVENT */

    if (isset($_POST['add_event'])) {

        $event_name = trim($_POST['event_name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $image = trim($_POST['image'] ?? '');

        if ($event_name === '' || !validImageName($image)) {

            $_SESSION['event_message'] =
                "Please enter valid event details.";

        } else {

            $stmt = $conn->prepare("
                INSERT INTO events
                (event_name, description, image)
                VALUES (?, ?, ?)
            ");

            $stmt->bind_param(
                "sss",
                $event_name,
                $description,
                $image
            );

            $_SESSION['event_message'] = $stmt->execute()
                ? "Event added successfully."
                : "Unable to add event.";

            $stmt->close();
        }
    }

    /* UPDATE EVENT */

    if (isset($_POST['update_event'])) {

        $event_id = (int)($_POST['event_id'] ?? 0);
        $event_name = trim($_POST['event_name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $image = trim($_POST['image'] ?? '');

        if (
            $event_id <= 0 ||
            $event_name === '' ||
            !validImageName($image)
        ) {

            $_SESSION['event_message'] =
                "Invalid event details.";

        } else {

            $stmt = $conn->prepare("
                UPDATE events
                SET
                    event_name = ?,
                    description = ?,
                    image = ?
                WHERE event_id = ?
            ");

            $stmt->bind_param(
                "sssi",
                $event_name,
                $description,
                $image,
                $event_id
            );

            $_SESSION['event_message'] = $stmt->execute()
                ? "Event updated successfully."
                : "Unable to update event.";

            $stmt->close();
        }
    }

    /* DELETE EVENT */

    if (isset($_POST['delete_event'])) {

        $event_id = (int)($_POST['event_id'] ?? 0);

        if ($event_id > 0) {

            /* CHECK FOR EXISTING BOOKINGS */

            $stmt = $conn->prepare("
                SELECT COUNT(*) AS total
                FROM bookings
                WHERE event_id = ?
            ");

            $stmt->bind_param("i", $event_id);
            $stmt->execute();

            $result = $stmt->get_result()->fetch_assoc();

            $stmt->close();

            if ((int)$result['total'] > 0) {

                $_SESSION['event_message'] =
                    "Cannot delete this event because it has customer bookings.";

            } else {

                $stmt = $conn->prepare("
                    DELETE FROM events
                    WHERE event_id = ?
                ");

                $stmt->bind_param("i", $event_id);

                $_SESSION['event_message'] = $stmt->execute()
                    ? "Event deleted successfully."
                    : "Unable to delete event.";

                $stmt->close();
            }

        } else {

            $_SESSION['event_message'] =
                "Invalid event ID.";
        }
    }

    header("Location: admin_events.php");
    exit;
}

/* NOTIFICATION */

$message = $_SESSION['event_message'] ?? '';
unset($_SESSION['event_message']);

/* GET EVENT TO EDIT */

$editEvent = null;

if (isset($_GET['edit'])) {

    $event_id = (int)$_GET['edit'];

    if ($event_id > 0) {

        $stmt = $conn->prepare("
            SELECT *
            FROM events
            WHERE event_id = ?
        ");

        $stmt->bind_param("i", $event_id);
        $stmt->execute();

        $editEvent = $stmt->get_result()->fetch_assoc();

        $stmt->close();
    }
}

/* LOAD ALL EVENTS */

$events = $conn->query("
    SELECT *
    FROM events
    ORDER BY event_name ASC, event_id ASC
");
?>

<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Manage Events - RAS Admin</title>

<link rel="icon" href="pictures/ras.png">
<link rel="stylesheet" href="css/style.css">

<style>

/* GENERAL */

* {
    box-sizing: border-box;
}

body.admin-page {
    margin: 0;
    padding: 0;
    background: #f7f2e9;
    font-family: Arial, sans-serif;
    color: #2b2520;
    line-height: 1.6;
}

/* MAIN LAYOUT */

.admin-layout {
    display: flex;
    min-height: 100vh;
}

/* SIDEBAR */

.admin-sidebar {
    width: 245px;
    flex-shrink: 0;
    background: #211b17;
    color: white;
    padding: 30px 20px;
}

.admin-brand {
    text-align: center;
    margin-bottom: 40px;
}

.admin-brand img {
    width: 85px;
    height: 85px;
    object-fit: contain;
}

.admin-brand h3 {
    color: #c59445;
    margin: 12px 0 5px;
    font-size: 19px;
}

.admin-brand p {
    font-size: 12px;
    color: #c8bbaa;
}

/* SIDEBAR MENU */

.admin-menu {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.admin-menu a {
    display: block;
    padding: 14px;
    color: #f5eee4;
    text-decoration: none;
    border-radius: 7px;
    font-size: 14px;
}

.admin-menu a:hover,
.admin-menu a.active {
    background: #c59445;
    color: #181614;
}

/* MAIN CONTENT */

.admin-main {
    flex: 1;
    min-width: 0;
    padding: 35px;
}

/* TOP BAR */

.admin-topbar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 15px;
    margin-bottom: 30px;
}

.admin-topbar h1 {
    margin: 0;
    color: #604126;
    font-size: 30px;
}

.admin-topbar p {
    color: #8b735c;
    font-size: 14px;
    margin-top: 8px;
}

.admin-user {
    background: white;
    padding: 12px 18px;
    border-radius: 8px;
    font-size: 14px;
}

/* PANELS */

.admin-panel {
    background: white;
    padding: 25px;
    margin-bottom: 30px;
    border-radius: 10px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.04);
}

.admin-panel h2 {
    color: #604126;
    margin: 0 0 22px;
    font-size: 22px;
}

/* EVENT FORM */

.admin-event-form {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 20px;
}

.admin-form-group {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.admin-form-group label {
    color: #604126;
    font-size: 13px;
    font-weight: bold;
}

.admin-form-group input,
.admin-form-group textarea {
    width: 100%;
    padding: 12px;
    border: 1px solid #cccccc;
    border-radius: 5px;
    background: #ffffff;
    color: #2b2520;
    font-family: Arial, sans-serif;
    font-size: 14px;
    outline: none;
}

.admin-form-group input:focus,
.admin-form-group textarea:focus {
    border-color: #888888;
}

.admin-form-group textarea {
    min-height: 120px;
    resize: vertical;
}

.admin-form-group:has(textarea) {
    grid-column: 1 / -1;
}

.admin-muted {
    font-size: 12px;
    color: #888888;
}

/* FORM BUTTONS */

.admin-form-actions {
    display: flex;
    align-items: center;
    flex-wrap: wrap;
    gap: 10px;
    grid-column: 1 / -1;
    margin-top: 5px;
}

/* SIMPLE GREY BUTTONS */

.admin-btn,
.admin-btn:link,
.admin-btn:visited {
    display: inline-block;
    width: auto;
    padding: 9px 16px;
    margin: 0;
    background: #eeeeee;
    color: #222222;
    border: 1px solid #aaaaaa;
    border-radius: 4px;
    font-family: Arial, sans-serif;
    font-size: 13px;
    font-weight: normal;
    line-height: 1.5;
    text-align: center;
    text-decoration: none;
    cursor: pointer;
    white-space: nowrap;
}

.admin-btn:hover {
    background: #dddddd;
    color: #222222;
}

/* EVENTS TABLE */

.admin-table-wrap {
    width: 100%;
    overflow-x: auto;
    margin-top: 20px;
}

.admin-table {
    width: 100%;
    min-width: 750px;
    border-collapse: collapse;
    background: white;
    text-align: left;
}

.admin-table th,
.admin-table td {
    padding: 14px 12px;
    border-bottom: 1px solid #e9e1d6;
    text-align: left;
    vertical-align: middle;
    font-size: 13px;
}

.admin-table th {
    background: #f1e7d8;
    color: #604126;
    font-weight: bold;
    white-space: nowrap;
}

.admin-table tbody tr:hover {
    background: #faf6f0;
}

.admin-table td {
    color: #342d26;
}

/* EVENT IMAGE */

.admin-event-image {
    display: block;
    width: 90px;
    height: 65px;
    object-fit: cover;
    border-radius: 6px;
    border: 1px solid #e5e5e5;
}

/* EDIT AND DELETE BUTTONS */

.admin-event-actions {
    display: flex;
    align-items: center;
    gap: 8px;
    flex-wrap: wrap;
}

.admin-event-actions form {
    margin: 0;
}

/* OVERRIDE ANY EXISTING BUTTON STYLES */

.admin-action-link,
.admin-action-link:link,
.admin-action-link:visited {
    display: inline-block;
    width: auto;
    padding: 7px 13px;
    margin: 0;
    background: #eeeeee;
    color: #222222;
    border: 1px solid #aaaaaa;
    border-radius: 4px;
    font-family: Arial, sans-serif;
    font-size: 12px;
    font-weight: normal;
    line-height: 1.5;
    text-decoration: none;
    text-align: center;
    cursor: pointer;
    white-space: nowrap;
}

.admin-action-link:hover {
    background: #dddddd;
    color: #222222;
}

/* NOTIFICATION */

.admin-alert {
    padding: 14px 18px;
    margin-bottom: 20px;
    background: #f2f2f2;
    color: #333333;
    border: 1px solid #dddddd;
    border-radius: 6px;
    font-size: 14px;
}

/* RESPONSIVE DESIGN */

@media (max-width: 1100px) {

    .admin-main {
        padding: 25px;
    }
}

@media (max-width: 700px) {

    .admin-layout {
        flex-direction: column;
    }

    .admin-sidebar {
        width: 100%;
        padding: 15px;
    }

    .admin-brand {
        margin-bottom: 15px;
    }

    .admin-brand img {
        width: 55px;
        height: 55px;
    }

    .admin-menu {
        flex-direction: row;
        flex-wrap: wrap;
        gap: 8px;
    }

    .admin-menu a {
        padding: 10px;
        font-size: 12px;
    }

    .admin-main {
        padding: 20px 12px;
    }

    .admin-topbar h1 {
        font-size: 25px;
    }

    .admin-panel {
        padding: 15px;
    }

    .admin-event-form {
        grid-template-columns: 1fr;
    }

    .admin-form-group:has(textarea),
    .admin-form-actions {
        grid-column: 1;
    }

    .admin-table {
        min-width: 750px;
    }
}

</style>

</head>

<body class="admin-page">

<div class="admin-layout">

<!-- SIDEBAR -->

<aside class="admin-sidebar">

    <div class="admin-brand">
        <img src="pictures/ras.png" alt="RAS Logo">
        <h3>RAS ADMIN</h3>
        <p>Management Portal</p>
    </div>

    <nav class="admin-menu">

        <a href="admin.php">
            Dashboard
        </a>

        <a href="admin_bookings.php">
            Customer Bookings
        </a>

        <a href="admin_events.php" class="active">
            Manage Events
        </a>

        <a href="admin_videos.php">
            Video Messages
        </a>

        <a href="logout.php">
            Logout
        </a>

    </nav>

</aside>

<!-- MAIN CONTENT -->

<main class="admin-main">

    <div class="admin-topbar">

        <div>
            <h1>Manage Events</h1>
            <p>Add, update and remove RAS Video Guest Book events.</p>
        </div>

        <div class="admin-user">
            Administrator:
            <strong>
                <?php echo esc($_SESSION['name'] ?? 'Admin'); ?>
            </strong>
        </div>

    </div>

    <!-- NOTIFICATION -->

    <?php if ($message !== ''): ?>

        <div class="admin-alert">
            <?php echo esc($message); ?>
        </div>

    <?php endif; ?>

    <!-- ADD OR EDIT EVENT -->

    <section class="admin-panel" id="event-form">

        <h2>
            <?php echo $editEvent ? 'Edit Event' : 'Add New Event'; ?>
        </h2>

        <form method="POST" class="admin-event-form">

            <input
                type="hidden"
                name="csrf"
                value="<?php echo esc($_SESSION['admin_csrf']); ?>"
            >

            <?php if ($editEvent): ?>

                <input
                    type="hidden"
                    name="event_id"
                    value="<?php echo (int)$editEvent['event_id']; ?>"
                >

            <?php endif; ?>

            <div class="admin-form-group">

                <label for="event_name">Event Name</label>

                <input
                    type="text"
                    id="event_name"
                    name="event_name"
                    maxlength="255"
                    value="<?php echo esc($editEvent['event_name'] ?? ''); ?>"
                    placeholder="Enter event name"
                    required
                >

            </div>

            <div class="admin-form-group">

                <label for="image">Image Filename</label>

                <input
                    type="text"
                    id="image"
                    name="image"
                    value="<?php echo esc($editEvent['image'] ?? ''); ?>"
                    placeholder="Example: wedding.jpg"
                >

                <small class="admin-muted">
                    Save your image inside the pictures folder.
                </small>

            </div>

            <div class="admin-form-group">

                <label for="description">Event Description</label>

                <textarea
                    id="description"
                    name="description"
                    placeholder="Enter event description"
                ><?php echo esc($editEvent['description'] ?? ''); ?></textarea>

            </div>

            <div class="admin-form-actions">

                <?php if ($editEvent): ?>

                    <button
                        type="submit"
                        name="update_event"
                        class="admin-btn"
                    >
                        Save Changes
                    </button>

                    <a
                        href="admin_events.php"
                        class="admin-btn"
                    >
                        Cancel
                    </a>

                <?php else: ?>

                    <button
                        type="submit"
                        name="add_event"
                        class="admin-btn"
                    >
                        Add Event
                    </button>

                <?php endif; ?>

            </div>

        </form>

    </section>

    <!-- EXISTING EVENTS -->

    <section class="admin-panel">

        <h2>Existing Events</h2>

        <div class="admin-table-wrap">

            <table class="admin-table">

                <thead>

                    <tr>
                        <th>ID</th>
                        <th>Image</th>
                        <th>Event Name</th>
                        <th>Description</th>
                        <th>Actions</th>
                    </tr>

                </thead>

                <tbody>

                <?php if ($events && $events->num_rows > 0): ?>

                    <?php while ($event = $events->fetch_assoc()): ?>

                    <tr>

                        <td>
                            <?php echo (int)$event['event_id']; ?>
                        </td>

                        <td>

                            <?php if (!empty($event['image']) && validImageName($event['image'])): ?>

                                <img
                                    class="admin-event-image"
                                    src="pictures/<?php echo rawurlencode($event['image']); ?>"
                                    alt="<?php echo esc($event['event_name']); ?>"
                                >

                            <?php else: ?>

                                <span class="admin-muted">No image</span>

                            <?php endif; ?>

                        </td>

                        <td>
                            <?php echo esc($event['event_name']); ?>
                        </td>

                        <td>
                            <?php echo esc($event['description']); ?>
                        </td>

                        <td>

                            <div class="admin-event-actions">

                                <!-- EDIT BUTTON -->

                                <a
                                    href="admin_events.php?edit=<?php echo (int)$event['event_id']; ?>#event-form"
                                    class="admin-action-link"
                                >
                                    Edit
                                </a>

                                <!-- DELETE BUTTON -->

                                <form
                                    method="POST"
                                    onsubmit="return confirm('Are you sure you want to delete this event?');"
                                >

                                    <input
                                        type="hidden"
                                        name="csrf"
                                        value="<?php echo esc($_SESSION['admin_csrf']); ?>"
                                    >

                                    <input
                                        type="hidden"
                                        name="event_id"
                                        value="<?php echo (int)$event['event_id']; ?>"
                                    >

                                    <button
                                        type="submit"
                                        name="delete_event"
                                        class="admin-action-link"
                                    >
                                        Delete
                                    </button>

                                </form>

                            </div>

                        </td>

                    </tr>

                    <?php endwhile; ?>

                <?php else: ?>

                    <tr>
                        <td colspan="5">
                            No events available.
                        </td>
                    </tr>

                <?php endif; ?>

                </tbody>

            </table>

        </div>

    </section>

</main>

</div>

</body>
</html>
=======
=======
>>>>>>> Stashed changes

<?php
session_start();

/* ADMIN ACCESS */

if (
    !isset($_SESSION['user_id']) ||
    ($_SESSION['role'] ?? '') !== 'admin'
) {
    header("Location: account.php");
    exit;
}

/* DATABASE CONNECTION */

require_once "database/database.php";

/* SECURITY */

function esc($value) {
    return htmlspecialchars(
        (string)($value ?? ''),
        ENT_QUOTES,
        'UTF-8'
    );
}

/* VALIDATE IMAGE FILENAME */

function validImageName($name) {
    return $name === '' ||
        (
            basename($name) === $name &&
            preg_match(
                '/^[a-zA-Z0-9._-]+\.(jpg|jpeg|png|webp|gif)$/i',
                $name
            )
        );
}

/* CSRF TOKEN */

if (empty($_SESSION['admin_csrf'])) {
    $_SESSION['admin_csrf'] = bin2hex(random_bytes(32));
}

/* PROCESS FORM */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $token = $_POST['csrf'] ?? '';

    if (
        !is_string($token) ||
        !hash_equals($_SESSION['admin_csrf'], $token)
    ) {
        http_response_code(403);
        exit("Invalid request.");
    }

    /* ADD EVENT */

    if (isset($_POST['add_event'])) {

        $event_name = trim($_POST['event_name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $image = trim($_POST['image'] ?? '');

        if ($event_name === '' || !validImageName($image)) {

            $_SESSION['event_message'] =
                "Please enter valid event details.";

        } else {

            $stmt = $conn->prepare("
                INSERT INTO events
                (event_name, description, image)
                VALUES (?, ?, ?)
            ");

            $stmt->bind_param(
                "sss",
                $event_name,
                $description,
                $image
            );

            $_SESSION['event_message'] = $stmt->execute()
                ? "Event added successfully."
                : "Unable to add event.";

            $stmt->close();
        }
    }

    /* UPDATE EVENT */

    if (isset($_POST['update_event'])) {

        $event_id = (int)($_POST['event_id'] ?? 0);
        $event_name = trim($_POST['event_name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $image = trim($_POST['image'] ?? '');

        if (
            $event_id <= 0 ||
            $event_name === '' ||
            !validImageName($image)
        ) {

            $_SESSION['event_message'] =
                "Invalid event details.";

        } else {

            $stmt = $conn->prepare("
                UPDATE events
                SET
                    event_name = ?,
                    description = ?,
                    image = ?
                WHERE event_id = ?
            ");

            $stmt->bind_param(
                "sssi",
                $event_name,
                $description,
                $image,
                $event_id
            );

            $_SESSION['event_message'] = $stmt->execute()
                ? "Event updated successfully."
                : "Unable to update event.";

            $stmt->close();
        }
    }

    /* DELETE EVENT */

    if (isset($_POST['delete_event'])) {

        $event_id = (int)($_POST['event_id'] ?? 0);

        if ($event_id > 0) {

            /* CHECK FOR EXISTING BOOKINGS */

            $stmt = $conn->prepare("
                SELECT COUNT(*) AS total
                FROM bookings
                WHERE event_id = ?
            ");

            $stmt->bind_param("i", $event_id);
            $stmt->execute();

            $result = $stmt->get_result()->fetch_assoc();

            $stmt->close();

            if ((int)$result['total'] > 0) {

                $_SESSION['event_message'] =
                    "Cannot delete this event because it has customer bookings.";

            } else {

                $stmt = $conn->prepare("
                    DELETE FROM events
                    WHERE event_id = ?
                ");

                $stmt->bind_param("i", $event_id);

                $_SESSION['event_message'] = $stmt->execute()
                    ? "Event deleted successfully."
                    : "Unable to delete event.";

                $stmt->close();
            }

        } else {

            $_SESSION['event_message'] =
                "Invalid event ID.";
        }
    }

    header("Location: admin_events.php");
    exit;
}

/* NOTIFICATION */

$message = $_SESSION['event_message'] ?? '';
unset($_SESSION['event_message']);

/* GET EVENT TO EDIT */

$editEvent = null;

if (isset($_GET['edit'])) {

    $event_id = (int)$_GET['edit'];

    if ($event_id > 0) {

        $stmt = $conn->prepare("
            SELECT *
            FROM events
            WHERE event_id = ?
        ");

        $stmt->bind_param("i", $event_id);
        $stmt->execute();

        $editEvent = $stmt->get_result()->fetch_assoc();

        $stmt->close();
    }
}

/* LOAD ALL EVENTS */

$events = $conn->query("
    SELECT *
    FROM events
    ORDER BY event_name ASC, event_id ASC
");
?>

<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Manage Events - RAS Admin</title>

<link rel="icon" href="pictures/ras.png">
<link rel="stylesheet" href="css/style.css">

<style>

/* GENERAL */

* {
    box-sizing: border-box;
}

body.admin-page {
    margin: 0;
    padding: 0;
    background: #f7f2e9;
    font-family: Arial, sans-serif;
    color: #2b2520;
    line-height: 1.6;
}

/* MAIN LAYOUT */

.admin-layout {
    display: flex;
    min-height: 100vh;
}

/* SIDEBAR */

.admin-sidebar {
    width: 245px;
    flex-shrink: 0;
    background: #211b17;
    color: white;
    padding: 30px 20px;
}

.admin-brand {
    text-align: center;
    margin-bottom: 40px;
}

.admin-brand img {
    width: 85px;
    height: 85px;
    object-fit: contain;
}

.admin-brand h3 {
    color: #c59445;
    margin: 12px 0 5px;
    font-size: 19px;
}

.admin-brand p {
    font-size: 12px;
    color: #c8bbaa;
}

/* SIDEBAR MENU */

.admin-menu {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.admin-menu a {
    display: block;
    padding: 14px;
    color: #f5eee4;
    text-decoration: none;
    border-radius: 7px;
    font-size: 14px;
}

.admin-menu a:hover,
.admin-menu a.active {
    background: #c59445;
    color: #181614;
}

/* MAIN CONTENT */

.admin-main {
    flex: 1;
    min-width: 0;
    padding: 35px;
}

/* TOP BAR */

.admin-topbar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 15px;
    margin-bottom: 30px;
}

.admin-topbar h1 {
    margin: 0;
    color: #604126;
    font-size: 30px;
}

.admin-topbar p {
    color: #8b735c;
    font-size: 14px;
    margin-top: 8px;
}

.admin-user {
    background: white;
    padding: 12px 18px;
    border-radius: 8px;
    font-size: 14px;
}

/* PANELS */

.admin-panel {
    background: white;
    padding: 25px;
    margin-bottom: 30px;
    border-radius: 10px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.04);
}

.admin-panel h2 {
    color: #604126;
    margin: 0 0 22px;
    font-size: 22px;
}

/* EVENT FORM */

.admin-event-form {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 20px;
}

.admin-form-group {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.admin-form-group label {
    color: #604126;
    font-size: 13px;
    font-weight: bold;
}

.admin-form-group input,
.admin-form-group textarea {
    width: 100%;
    padding: 12px;
    border: 1px solid #cccccc;
    border-radius: 5px;
    background: #ffffff;
    color: #2b2520;
    font-family: Arial, sans-serif;
    font-size: 14px;
    outline: none;
}

.admin-form-group input:focus,
.admin-form-group textarea:focus {
    border-color: #888888;
}

.admin-form-group textarea {
    min-height: 120px;
    resize: vertical;
}

.admin-form-group:has(textarea) {
    grid-column: 1 / -1;
}

.admin-muted {
    font-size: 12px;
    color: #888888;
}

/* FORM BUTTONS */

.admin-form-actions {
    display: flex;
    align-items: center;
    flex-wrap: wrap;
    gap: 10px;
    grid-column: 1 / -1;
    margin-top: 5px;
}

/* SIMPLE GREY BUTTONS */

.admin-btn,
.admin-btn:link,
.admin-btn:visited {
    display: inline-block;
    width: auto;
    padding: 9px 16px;
    margin: 0;
    background: #eeeeee;
    color: #222222;
    border: 1px solid #aaaaaa;
    border-radius: 4px;
    font-family: Arial, sans-serif;
    font-size: 13px;
    font-weight: normal;
    line-height: 1.5;
    text-align: center;
    text-decoration: none;
    cursor: pointer;
    white-space: nowrap;
}

.admin-btn:hover {
    background: #dddddd;
    color: #222222;
}

/* EVENTS TABLE */

.admin-table-wrap {
    width: 100%;
    overflow-x: auto;
    margin-top: 20px;
}

.admin-table {
    width: 100%;
    min-width: 750px;
    border-collapse: collapse;
    background: white;
    text-align: left;
}

.admin-table th,
.admin-table td {
    padding: 14px 12px;
    border-bottom: 1px solid #e9e1d6;
    text-align: left;
    vertical-align: middle;
    font-size: 13px;
}

.admin-table th {
    background: #f1e7d8;
    color: #604126;
    font-weight: bold;
    white-space: nowrap;
}

.admin-table tbody tr:hover {
    background: #faf6f0;
}

.admin-table td {
    color: #342d26;
}

/* EVENT IMAGE */

.admin-event-image {
    display: block;
    width: 90px;
    height: 65px;
    object-fit: cover;
    border-radius: 6px;
    border: 1px solid #e5e5e5;
}

/* EDIT AND DELETE BUTTONS */

.admin-event-actions {
    display: flex;
    align-items: center;
    gap: 8px;
    flex-wrap: wrap;
}

.admin-event-actions form {
    margin: 0;
}

/* OVERRIDE ANY EXISTING BUTTON STYLES */

.admin-action-link,
.admin-action-link:link,
.admin-action-link:visited {
    display: inline-block;
    width: auto;
    padding: 7px 13px;
    margin: 0;
    background: #eeeeee;
    color: #222222;
    border: 1px solid #aaaaaa;
    border-radius: 4px;
    font-family: Arial, sans-serif;
    font-size: 12px;
    font-weight: normal;
    line-height: 1.5;
    text-decoration: none;
    text-align: center;
    cursor: pointer;
    white-space: nowrap;
}

.admin-action-link:hover {
    background: #dddddd;
    color: #222222;
}

/* NOTIFICATION */

.admin-alert {
    padding: 14px 18px;
    margin-bottom: 20px;
    background: #f2f2f2;
    color: #333333;
    border: 1px solid #dddddd;
    border-radius: 6px;
    font-size: 14px;
}

/* RESPONSIVE DESIGN */

@media (max-width: 1100px) {

    .admin-main {
        padding: 25px;
    }
}

@media (max-width: 700px) {

    .admin-layout {
        flex-direction: column;
    }

    .admin-sidebar {
        width: 100%;
        padding: 15px;
    }

    .admin-brand {
        margin-bottom: 15px;
    }

    .admin-brand img {
        width: 55px;
        height: 55px;
    }

    .admin-menu {
        flex-direction: row;
        flex-wrap: wrap;
        gap: 8px;
    }

    .admin-menu a {
        padding: 10px;
        font-size: 12px;
    }

    .admin-main {
        padding: 20px 12px;
    }

    .admin-topbar h1 {
        font-size: 25px;
    }

    .admin-panel {
        padding: 15px;
    }

    .admin-event-form {
        grid-template-columns: 1fr;
    }

    .admin-form-group:has(textarea),
    .admin-form-actions {
        grid-column: 1;
    }

    .admin-table {
        min-width: 750px;
    }
}

</style>

</head>

<body class="admin-page">

<div class="admin-layout">

<!-- SIDEBAR -->

<aside class="admin-sidebar">

    <div class="admin-brand">
        <img src="pictures/ras.png" alt="RAS Logo">
        <h3>RAS ADMIN</h3>
        <p>Management Portal</p>
    </div>

    <nav class="admin-menu">

        <a href="admin.php">
            Dashboard
        </a>

        <a href="admin_bookings.php">
            Customer Bookings
        </a>

        <a href="admin_events.php" class="active">
            Manage Events
        </a>

        <a href="admin_videos.php">
            Video Messages
        </a>

        <a href="logout.php">
            Logout
        </a>

    </nav>

</aside>

<!-- MAIN CONTENT -->

<main class="admin-main">

    <div class="admin-topbar">

        <div>
            <h1>Manage Events</h1>
            <p>Add, update and remove RAS Video Guest Book events.</p>
        </div>

        <div class="admin-user">
            Administrator:
            <strong>
                <?php echo esc($_SESSION['name'] ?? 'Admin'); ?>
            </strong>
        </div>

    </div>

    <!-- NOTIFICATION -->

    <?php if ($message !== ''): ?>

        <div class="admin-alert">
            <?php echo esc($message); ?>
        </div>

    <?php endif; ?>

    <!-- ADD OR EDIT EVENT -->

    <section class="admin-panel" id="event-form">

        <h2>
            <?php echo $editEvent ? 'Edit Event' : 'Add New Event'; ?>
        </h2>

        <form method="POST" class="admin-event-form">

            <input
                type="hidden"
                name="csrf"
                value="<?php echo esc($_SESSION['admin_csrf']); ?>"
            >

            <?php if ($editEvent): ?>

                <input
                    type="hidden"
                    name="event_id"
                    value="<?php echo (int)$editEvent['event_id']; ?>"
                >

            <?php endif; ?>

            <div class="admin-form-group">

                <label for="event_name">Event Name</label>

                <input
                    type="text"
                    id="event_name"
                    name="event_name"
                    maxlength="255"
                    value="<?php echo esc($editEvent['event_name'] ?? ''); ?>"
                    placeholder="Enter event name"
                    required
                >

            </div>

            <div class="admin-form-group">

                <label for="image">Image Filename</label>

                <input
                    type="text"
                    id="image"
                    name="image"
                    value="<?php echo esc($editEvent['image'] ?? ''); ?>"
                    placeholder="Example: wedding.jpg"
                >

                <small class="admin-muted">
                    Save your image inside the pictures folder.
                </small>

            </div>

            <div class="admin-form-group">

                <label for="description">Event Description</label>

                <textarea
                    id="description"
                    name="description"
                    placeholder="Enter event description"
                ><?php echo esc($editEvent['description'] ?? ''); ?></textarea>

            </div>

            <div class="admin-form-actions">

                <?php if ($editEvent): ?>

                    <button
                        type="submit"
                        name="update_event"
                        class="admin-btn"
                    >
                        Save Changes
                    </button>

                    <a
                        href="admin_events.php"
                        class="admin-btn"
                    >
                        Cancel
                    </a>

                <?php else: ?>

                    <button
                        type="submit"
                        name="add_event"
                        class="admin-btn"
                    >
                        Add Event
                    </button>

                <?php endif; ?>

            </div>

        </form>

    </section>

    <!-- EXISTING EVENTS -->

    <section class="admin-panel">

        <h2>Existing Events</h2>

        <div class="admin-table-wrap">

            <table class="admin-table">

                <thead>

                    <tr>
                        <th>ID</th>
                        <th>Image</th>
                        <th>Event Name</th>
                        <th>Description</th>
                        <th>Actions</th>
                    </tr>

                </thead>

                <tbody>

                <?php if ($events && $events->num_rows > 0): ?>

                    <?php while ($event = $events->fetch_assoc()): ?>

                    <tr>

                        <td>
                            <?php echo (int)$event['event_id']; ?>
                        </td>

                        <td>

                            <?php if (!empty($event['image']) && validImageName($event['image'])): ?>

                                <img
                                    class="admin-event-image"
                                    src="pictures/<?php echo rawurlencode($event['image']); ?>"
                                    alt="<?php echo esc($event['event_name']); ?>"
                                >

                            <?php else: ?>

                                <span class="admin-muted">No image</span>

                            <?php endif; ?>

                        </td>

                        <td>
                            <?php echo esc($event['event_name']); ?>
                        </td>

                        <td>
                            <?php echo esc($event['description']); ?>
                        </td>

                        <td>

                            <div class="admin-event-actions">

                                <!-- EDIT BUTTON -->

                                <a
                                    href="admin_events.php?edit=<?php echo (int)$event['event_id']; ?>#event-form"
                                    class="admin-action-link"
                                >
                                    Edit
                                </a>

                                <!-- DELETE BUTTON -->

                                <form
                                    method="POST"
                                    onsubmit="return confirm('Are you sure you want to delete this event?');"
                                >

                                    <input
                                        type="hidden"
                                        name="csrf"
                                        value="<?php echo esc($_SESSION['admin_csrf']); ?>"
                                    >

                                    <input
                                        type="hidden"
                                        name="event_id"
                                        value="<?php echo (int)$event['event_id']; ?>"
                                    >

                                    <button
                                        type="submit"
                                        name="delete_event"
                                        class="admin-action-link"
                                    >
                                        Delete
                                    </button>

                                </form>

                            </div>

                        </td>

                    </tr>

                    <?php endwhile; ?>

                <?php else: ?>

                    <tr>
                        <td colspan="5">
                            No events available.
                        </td>
                    </tr>

                <?php endif; ?>

                </tbody>

            </table>

        </div>

    </section>

</main>

</div>

</body>
</html>
<<<<<<< Updated upstream
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
