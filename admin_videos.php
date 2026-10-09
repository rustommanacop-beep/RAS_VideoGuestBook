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

/* CSRF TOKEN */

if (empty($_SESSION['admin_csrf'])) {
    $_SESSION['admin_csrf'] = bin2hex(random_bytes(32));
}

/* VIDEO UPLOAD DIRECTORY */

$videoFolder = __DIR__ . DIRECTORY_SEPARATOR . "uploads"
    . DIRECTORY_SEPARATOR . "videos";

$videoWebPath = "uploads/videos/";

if (!is_dir($videoFolder)) {
    if (!mkdir($videoFolder, 0755, true) && !is_dir($videoFolder)) {
        http_response_code(500);
        exit("Unable to create video upload folder.");
    }
}

/* UPLOAD VIDEO */

if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['upload_video'])
) {

    $token = $_POST['csrf'] ?? '';

    if (
        !is_string($token) ||
        !hash_equals($_SESSION['admin_csrf'], $token)
    ) {
        http_response_code(403);
        exit("Invalid request.");
    }

    $booking_id = (int)($_POST['booking_id'] ?? 0);
    $message = trim($_POST['message'] ?? '');

    $error = '';

    /* CHECK CUSTOMER BOOKING EXISTS */

    if ($booking_id <= 0) {

        $error = "Please select a customer booking.";

    } else {

        $stmt = $conn->prepare("
            SELECT booking_id
            FROM bookings
            WHERE booking_id = ?
        ");

        $stmt->bind_param("i", $booking_id);
        $stmt->execute();

        $bookingExists = $stmt->get_result()->num_rows > 0;

        $stmt->close();

        if (!$bookingExists) {
            $error = "Selected customer booking does not exist.";
        }
    }

    /* CHECK VIDEO FILE */

    if ($error === '') {

        if (
            !isset($_FILES['video_file']) ||
            $_FILES['video_file']['error'] === UPLOAD_ERR_NO_FILE
        ) {

            $error = "Please choose a video file.";

        } elseif (
            $_FILES['video_file']['error'] !== UPLOAD_ERR_OK
        ) {

            $error = "Video upload failed. Check PHP upload limits.";

        } elseif (
            $_FILES['video_file']['size'] <= 0 ||
            $_FILES['video_file']['size'] > 100 * 1024 * 1024
        ) {

            $error = "Video must be smaller than 100MB.";

        } elseif (
            !is_uploaded_file($_FILES['video_file']['tmp_name'])
        ) {

            $error = "Invalid video upload.";

        }
    }

    /* CHECK VIDEO EXTENSION AND TYPE */

    if ($error === '') {

        $originalName = $_FILES['video_file']['name'];

        $extension = strtolower(
            pathinfo($originalName, PATHINFO_EXTENSION)
        );

        $allowedTypes = [
            'mp4' => ['video/mp4'],
            'webm' => ['video/webm'],
            'mov' => ['video/quicktime']
        ];

        if (!isset($allowedTypes[$extension])) {

            $error = "Only MP4, WEBM and MOV videos are allowed.";

        } else {

            $finfo = new finfo(FILEINFO_MIME_TYPE);

            $mimeType = $finfo->file(
                $_FILES['video_file']['tmp_name']
            );

            if (!in_array(
                $mimeType,
                $allowedTypes[$extension],
                true
            )) {

                $error = "Invalid video format.";
            }
        }
    }

    /* SAVE VIDEO */

    if ($error === '') {

        $filename = "video_" .
            bin2hex(random_bytes(12)) .
            "." . $extension;

        $destination = $videoFolder .
            DIRECTORY_SEPARATOR . $filename;

        if (!move_uploaded_file(
            $_FILES['video_file']['tmp_name'],
            $destination
        )) {

            $error = "Unable to save video file.";

        } else {

            /* SAVE VIDEO DETAILS IN MYSQL */

            $status = "Uploaded";

            $stmt = $conn->prepare("
                INSERT INTO video_messages
                    (
                        booking_id,
                        video_filename,
                        message,
                        status,
                        upload_date
                    )
                VALUES (?, ?, ?, ?, NOW())
            ");

            $stmt->bind_param(
                "isss",
                $booking_id,
                $filename,
                $message,
                $status
            );

            if ($stmt->execute()) {

                $_SESSION['video_message'] =
                    "Video uploaded successfully for Booking "
                    . $booking_id . ".";

            } else {

                /* REMOVE FILE IF DATABASE SAVE FAILS */

                @unlink($destination);

                $_SESSION['video_message'] =
                    "Unable to save video information in database.";
            }

            $stmt->close();
        }
    }

    if ($error !== '') {
        $_SESSION['video_message'] = $error;
    }

    header("Location: admin_videos.php");
    exit;
}

/* NOTIFICATION */

$notification = $_SESSION['video_message'] ?? '';
unset($_SESSION['video_message']);

/* LOAD CUSTOMER BOOKINGS FOR DROPDOWN */

$customers = $conn->query("
    SELECT
        booking_id,
        customer_name,
        customer_email,
        event_date
    FROM bookings
    ORDER BY customer_name ASC, booking_id ASC
");

/* TOTAL VIDEO MESSAGES */

$totalVideos = 0;

$countResult = $conn->query("
    SELECT COUNT(*) AS total
    FROM video_messages
");

if ($countResult) {
    $countData = $countResult->fetch_assoc();
    $totalVideos = (int)$countData['total'];
}

/* LOAD VIDEO MESSAGES */

$videos = $conn->query("
    SELECT
        v.video_id,
        v.booking_id,
        v.video_filename,
        v.message,
        v.status,
        v.upload_date,
        b.customer_name,
        b.customer_email,
        e.event_name
    FROM video_messages v
    LEFT JOIN bookings b
        ON v.booking_id = b.booking_id
    LEFT JOIN events e
        ON b.event_id = e.event_id
    ORDER BY v.video_id DESC
");
?>

<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Video Messages - RAS Admin</title>

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

/* LAYOUT */

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
}

.admin-brand p {
    font-size: 12px;
    color: #c8bbaa;
}

/* MENU */

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

/* MAIN */

.admin-main {
    flex: 1;
    min-width: 0;
    padding: 35px;
}

/* HEADER */

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
    margin: 0 0 20px;
    color: #604126;
    font-size: 22px;
}

/* VIDEO COUNT */

.admin-stats {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 18px;
    margin-bottom: 30px;
}

.stat-card {
    background: white;
    padding: 22px;
    border-radius: 10px;
    border-left: 4px solid #c59445;
}

.stat-card h2 {
    margin: 0 0 8px;
    font-size: 30px;
    color: #604126;
}

.stat-card p {
    margin: 0;
    font-size: 13px;
    color: #777;
}

/* UPLOAD FORM */

.video-upload-form {
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

.admin-form-group select,
.admin-form-group input,
.admin-form-group textarea {
    width: 100%;
    padding: 12px;
    border: 1px solid #cccccc;
    border-radius: 5px;
    background: white;
    color: #222222;
    font-family: Arial, sans-serif;
    font-size: 14px;
}

.admin-form-group textarea {
    min-height: 95px;
    resize: vertical;
}

.full-width {
    grid-column: 1 / -1;
}

.admin-muted {
    color: #888888;
    font-size: 12px;
}

/* SIMPLE GREY BUTTON */

.simple-btn {
    display: inline-block;
    width: auto;
    padding: 9px 16px;
    background: #eeeeee;
    color: #222222;
    border: 1px solid #aaaaaa;
    border-radius: 4px;
    font-family: Arial, sans-serif;
    font-size: 13px;
    font-weight: normal;
    text-decoration: none;
    cursor: pointer;
}

.simple-btn:hover {
    background: #dddddd;
}

/* NOTIFICATION */

.admin-alert {
    padding: 14px 18px;
    margin-bottom: 20px;
    background: #f2f2f2;
    color: #333333;
    border: 1px solid #dddddd;
    border-radius: 6px;
}

/* TABLE */

.admin-table-wrap {
    width: 100%;
    overflow-x: auto;
}

.admin-table {
    width: 100%;
    min-width: 1100px;
    border-collapse: collapse;
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
    white-space: nowrap;
}

.admin-table tbody tr:hover {
    background: #faf6f0;
}

.video-filename {
    overflow-wrap: anywhere;
}

.video-status {
    color: #2b2520;
}

.empty-message {
    text-align: center;
    color: #888888;
    padding: 25px;
}

/* RESPONSIVE */

@media (max-width: 1100px) {
    .admin-stats {
        grid-template-columns: repeat(2, 1fr);
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

    .admin-stats {
        grid-template-columns: 1fr;
    }

    .admin-panel {
        padding: 15px;
    }

    .video-upload-form {
        grid-template-columns: 1fr;
    }

    .full-width {
        grid-column: 1;
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

        <a href="admin.php">Dashboard</a>

        <a href="admin_bookings.php">
            Customer Bookings
        </a>

        <a href="admin_events.php">
            Manage Events
        </a>

        <a href="admin_videos.php" class="active">
            Video Messages
        </a>

        <a href="logout.php">Logout</a>

    </nav>

</aside>

<!-- MAIN CONTENT -->

<main class="admin-main">

    <div class="admin-topbar">

        <div>
            <h1>Video Messages</h1>
            <p>Upload and manage customer video guest book messages.</p>
        </div>

        <div class="admin-user">
            Administrator:
            <strong>
                <?php echo esc($_SESSION['name'] ?? 'Admin'); ?>
            </strong>
        </div>

    </div>

    <!-- TOTAL VIDEOS -->

    <div class="admin-stats">

        <div class="stat-card">
            <h2><?php echo $totalVideos; ?></h2>
            <p>Total Video Messages</p>
        </div>

    </div>

    <!-- NOTIFICATION -->

    <?php if ($notification !== ''): ?>

        <div class="admin-alert">
            <?php echo esc($notification); ?>
        </div>

    <?php endif; ?>

    <!-- UPLOAD VIDEO FORM -->

    <section class="admin-panel">

        <h2>Upload Video Message</h2>

        <form
            method="POST"
            enctype="multipart/form-data"
            class="video-upload-form"
        >

            <input
                type="hidden"
                name="csrf"
                value="<?php echo esc($_SESSION['admin_csrf']); ?>"
            >

            <!-- SELECT CUSTOMER -->

            <div class="admin-form-group">

                <label for="booking_id">
                    Choose Customer Booking
                </label>

                <select
                    name="booking_id"
                    id="booking_id"
                    required
                >

                    <option value="">
                        -- Select Customer --
                    </option>

                    <?php if ($customers): ?>

                        <?php while ($customer = $customers->fetch_assoc()): ?>

                            <option
                                value="<?php echo (int)$customer['booking_id']; ?>"
                            >
                                <?php
                                echo esc(
                                    $customer['customer_name'] .
                                    ' - Booking ' .
                                    $customer['booking_id'] .
                                    ' - ' .
                                    $customer['event_date']
                                );
                                ?>
                            </option>

                        <?php endwhile; ?>

                    <?php endif; ?>

                </select>

                <small class="admin-muted">
                    Choose the customer booking that should receive this video.
                </small>

            </div>

            <!-- VIDEO FILE -->

            <div class="admin-form-group">

                <label for="video_file">
                    Select Video
                </label>

                <input
                    type="file"
                    id="video_file"
                    name="video_file"
                    accept=".mp4,.webm,.mov,video/mp4,video/webm,video/quicktime"
                    required
                >

                <small class="admin-muted">
                    MP4, WEBM or MOV. Maximum size: 100MB.
                </small>

            </div>

            <!-- MESSAGE -->

            <div class="admin-form-group full-width">

                <label for="message">
                    Message (Optional)
                </label>

                <textarea
                    name="message"
                    id="message"
                    placeholder="Enter a message about this video"
                ></textarea>

            </div>

            <!-- UPLOAD BUTTON -->

            <div class="full-width">

                <button
                    type="submit"
                    name="upload_video"
                    class="simple-btn"
                >
                    Upload Video
                </button>

            </div>

        </form>

    </section>

    <!-- ALL VIDEO MESSAGES -->

    <section class="admin-panel">

        <h2>All Video Messages</h2>

        <div class="admin-table-wrap">

            <table class="admin-table">

                <thead>

                    <tr>
                        <th>Video ID</th>
                        <th>Booking ID</th>
                        <th>Customer</th>
                        <th>Email</th>
                        <th>Event</th>
                        <th>Video Filename</th>
                        <th>Message</th>
                        <th>Status</th>
                        <th>Upload Date</th>
                    </tr>

                </thead>

                <tbody>

                <?php if ($videos && $videos->num_rows > 0): ?>

                    <?php while ($video = $videos->fetch_assoc()): ?>

                    <tr>

                        <td>
                            <?php echo (int)$video['video_id']; ?>
                        </td>

                        <td>
                            <?php echo (int)$video['booking_id']; ?>
                        </td>

                        <td>
                            <?php echo esc($video['customer_name'] ?? 'Unknown'); ?>
                        </td>

                        <td>
                            <?php echo esc($video['customer_email'] ?? ''); ?>
                        </td>

                        <td>
                            <?php echo esc($video['event_name'] ?? 'Unknown'); ?>
                        </td>

                        <td>
                            <span class="video-filename">
                                <?php echo esc($video['video_filename']); ?>
                            </span>
                        </td>

                        <td>
                            <?php echo esc($video['message']); ?>
                        </td>

                        <td>
                            <span class="video-status">
                                <?php echo esc($video['status']); ?>
                            </span>
                        </td>

                        <td>
                            <?php echo esc($video['upload_date']); ?>
                        </td>

                    </tr>

                    <?php endwhile; ?>

                <?php else: ?>

                    <tr>
                        <td colspan="9" class="empty-message">
                            No video messages available.
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

/* CSRF TOKEN */

if (empty($_SESSION['admin_csrf'])) {
    $_SESSION['admin_csrf'] = bin2hex(random_bytes(32));
}

/* VIDEO UPLOAD DIRECTORY */

$videoFolder = __DIR__ . DIRECTORY_SEPARATOR . "uploads"
    . DIRECTORY_SEPARATOR . "videos";

$videoWebPath = "uploads/videos/";

if (!is_dir($videoFolder)) {
    if (!mkdir($videoFolder, 0755, true) && !is_dir($videoFolder)) {
        http_response_code(500);
        exit("Unable to create video upload folder.");
    }
}

/* UPLOAD VIDEO */

if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['upload_video'])
) {

    $token = $_POST['csrf'] ?? '';

    if (
        !is_string($token) ||
        !hash_equals($_SESSION['admin_csrf'], $token)
    ) {
        http_response_code(403);
        exit("Invalid request.");
    }

    $booking_id = (int)($_POST['booking_id'] ?? 0);
    $message = trim($_POST['message'] ?? '');

    $error = '';

    /* CHECK CUSTOMER BOOKING EXISTS */

    if ($booking_id <= 0) {

        $error = "Please select a customer booking.";

    } else {

        $stmt = $conn->prepare("
            SELECT booking_id
            FROM bookings
            WHERE booking_id = ?
        ");

        $stmt->bind_param("i", $booking_id);
        $stmt->execute();

        $bookingExists = $stmt->get_result()->num_rows > 0;

        $stmt->close();

        if (!$bookingExists) {
            $error = "Selected customer booking does not exist.";
        }
    }

    /* CHECK VIDEO FILE */

    if ($error === '') {

        if (
            !isset($_FILES['video_file']) ||
            $_FILES['video_file']['error'] === UPLOAD_ERR_NO_FILE
        ) {

            $error = "Please choose a video file.";

        } elseif (
            $_FILES['video_file']['error'] !== UPLOAD_ERR_OK
        ) {

            $error = "Video upload failed. Check PHP upload limits.";

        } elseif (
            $_FILES['video_file']['size'] <= 0 ||
            $_FILES['video_file']['size'] > 100 * 1024 * 1024
        ) {

            $error = "Video must be smaller than 100MB.";

        } elseif (
            !is_uploaded_file($_FILES['video_file']['tmp_name'])
        ) {

            $error = "Invalid video upload.";

        }
    }

    /* CHECK VIDEO EXTENSION AND TYPE */

    if ($error === '') {

        $originalName = $_FILES['video_file']['name'];

        $extension = strtolower(
            pathinfo($originalName, PATHINFO_EXTENSION)
        );

        $allowedTypes = [
            'mp4' => ['video/mp4'],
            'webm' => ['video/webm'],
            'mov' => ['video/quicktime']
        ];

        if (!isset($allowedTypes[$extension])) {

            $error = "Only MP4, WEBM and MOV videos are allowed.";

        } else {

            $finfo = new finfo(FILEINFO_MIME_TYPE);

            $mimeType = $finfo->file(
                $_FILES['video_file']['tmp_name']
            );

            if (!in_array(
                $mimeType,
                $allowedTypes[$extension],
                true
            )) {

                $error = "Invalid video format.";
            }
        }
    }

    /* SAVE VIDEO */

    if ($error === '') {

        $filename = "video_" .
            bin2hex(random_bytes(12)) .
            "." . $extension;

        $destination = $videoFolder .
            DIRECTORY_SEPARATOR . $filename;

        if (!move_uploaded_file(
            $_FILES['video_file']['tmp_name'],
            $destination
        )) {

            $error = "Unable to save video file.";

        } else {

            /* SAVE VIDEO DETAILS IN MYSQL */

            $status = "Uploaded";

            $stmt = $conn->prepare("
                INSERT INTO video_messages
                    (
                        booking_id,
                        video_filename,
                        message,
                        status,
                        upload_date
                    )
                VALUES (?, ?, ?, ?, NOW())
            ");

            $stmt->bind_param(
                "isss",
                $booking_id,
                $filename,
                $message,
                $status
            );

            if ($stmt->execute()) {

                $_SESSION['video_message'] =
                    "Video uploaded successfully for Booking "
                    . $booking_id . ".";

            } else {

                /* REMOVE FILE IF DATABASE SAVE FAILS */

                @unlink($destination);

                $_SESSION['video_message'] =
                    "Unable to save video information in database.";
            }

            $stmt->close();
        }
    }

    if ($error !== '') {
        $_SESSION['video_message'] = $error;
    }

    header("Location: admin_videos.php");
    exit;
}

/* NOTIFICATION */

$notification = $_SESSION['video_message'] ?? '';
unset($_SESSION['video_message']);

/* LOAD CUSTOMER BOOKINGS FOR DROPDOWN */

$customers = $conn->query("
    SELECT
        booking_id,
        customer_name,
        customer_email,
        event_date
    FROM bookings
    ORDER BY customer_name ASC, booking_id ASC
");

/* TOTAL VIDEO MESSAGES */

$totalVideos = 0;

$countResult = $conn->query("
    SELECT COUNT(*) AS total
    FROM video_messages
");

if ($countResult) {
    $countData = $countResult->fetch_assoc();
    $totalVideos = (int)$countData['total'];
}

/* LOAD VIDEO MESSAGES */

$videos = $conn->query("
    SELECT
        v.video_id,
        v.booking_id,
        v.video_filename,
        v.message,
        v.status,
        v.upload_date,
        b.customer_name,
        b.customer_email,
        e.event_name
    FROM video_messages v
    LEFT JOIN bookings b
        ON v.booking_id = b.booking_id
    LEFT JOIN events e
        ON b.event_id = e.event_id
    ORDER BY v.video_id DESC
");
?>

<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Video Messages - RAS Admin</title>

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

/* LAYOUT */

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
}

.admin-brand p {
    font-size: 12px;
    color: #c8bbaa;
}

/* MENU */

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

/* MAIN */

.admin-main {
    flex: 1;
    min-width: 0;
    padding: 35px;
}

/* HEADER */

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
    margin: 0 0 20px;
    color: #604126;
    font-size: 22px;
}

/* VIDEO COUNT */

.admin-stats {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 18px;
    margin-bottom: 30px;
}

.stat-card {
    background: white;
    padding: 22px;
    border-radius: 10px;
    border-left: 4px solid #c59445;
}

.stat-card h2 {
    margin: 0 0 8px;
    font-size: 30px;
    color: #604126;
}

.stat-card p {
    margin: 0;
    font-size: 13px;
    color: #777;
}

/* UPLOAD FORM */

.video-upload-form {
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

.admin-form-group select,
.admin-form-group input,
.admin-form-group textarea {
    width: 100%;
    padding: 12px;
    border: 1px solid #cccccc;
    border-radius: 5px;
    background: white;
    color: #222222;
    font-family: Arial, sans-serif;
    font-size: 14px;
}

.admin-form-group textarea {
    min-height: 95px;
    resize: vertical;
}

.full-width {
    grid-column: 1 / -1;
}

.admin-muted {
    color: #888888;
    font-size: 12px;
}

/* SIMPLE GREY BUTTON */

.simple-btn {
    display: inline-block;
    width: auto;
    padding: 9px 16px;
    background: #eeeeee;
    color: #222222;
    border: 1px solid #aaaaaa;
    border-radius: 4px;
    font-family: Arial, sans-serif;
    font-size: 13px;
    font-weight: normal;
    text-decoration: none;
    cursor: pointer;
}

.simple-btn:hover {
    background: #dddddd;
}

/* NOTIFICATION */

.admin-alert {
    padding: 14px 18px;
    margin-bottom: 20px;
    background: #f2f2f2;
    color: #333333;
    border: 1px solid #dddddd;
    border-radius: 6px;
}

/* TABLE */

.admin-table-wrap {
    width: 100%;
    overflow-x: auto;
}

.admin-table {
    width: 100%;
    min-width: 1100px;
    border-collapse: collapse;
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
    white-space: nowrap;
}

.admin-table tbody tr:hover {
    background: #faf6f0;
}

.video-filename {
    overflow-wrap: anywhere;
}

.video-status {
    color: #2b2520;
}

.empty-message {
    text-align: center;
    color: #888888;
    padding: 25px;
}

/* RESPONSIVE */

@media (max-width: 1100px) {
    .admin-stats {
        grid-template-columns: repeat(2, 1fr);
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

    .admin-stats {
        grid-template-columns: 1fr;
    }

    .admin-panel {
        padding: 15px;
    }

    .video-upload-form {
        grid-template-columns: 1fr;
    }

    .full-width {
        grid-column: 1;
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

        <a href="admin.php">Dashboard</a>

        <a href="admin_bookings.php">
            Customer Bookings
        </a>

        <a href="admin_events.php">
            Manage Events
        </a>

        <a href="admin_videos.php" class="active">
            Video Messages
        </a>

        <a href="logout.php">Logout</a>

    </nav>

</aside>

<!-- MAIN CONTENT -->

<main class="admin-main">

    <div class="admin-topbar">

        <div>
            <h1>Video Messages</h1>
            <p>Upload and manage customer video guest book messages.</p>
        </div>

        <div class="admin-user">
            Administrator:
            <strong>
                <?php echo esc($_SESSION['name'] ?? 'Admin'); ?>
            </strong>
        </div>

    </div>

    <!-- TOTAL VIDEOS -->

    <div class="admin-stats">

        <div class="stat-card">
            <h2><?php echo $totalVideos; ?></h2>
            <p>Total Video Messages</p>
        </div>

    </div>

    <!-- NOTIFICATION -->

    <?php if ($notification !== ''): ?>

        <div class="admin-alert">
            <?php echo esc($notification); ?>
        </div>

    <?php endif; ?>

    <!-- UPLOAD VIDEO FORM -->

    <section class="admin-panel">

        <h2>Upload Video Message</h2>

        <form
            method="POST"
            enctype="multipart/form-data"
            class="video-upload-form"
        >

            <input
                type="hidden"
                name="csrf"
                value="<?php echo esc($_SESSION['admin_csrf']); ?>"
            >

            <!-- SELECT CUSTOMER -->

            <div class="admin-form-group">

                <label for="booking_id">
                    Choose Customer Booking
                </label>

                <select
                    name="booking_id"
                    id="booking_id"
                    required
                >

                    <option value="">
                        -- Select Customer --
                    </option>

                    <?php if ($customers): ?>

                        <?php while ($customer = $customers->fetch_assoc()): ?>

                            <option
                                value="<?php echo (int)$customer['booking_id']; ?>"
                            >
                                <?php
                                echo esc(
                                    $customer['customer_name'] .
                                    ' - Booking ' .
                                    $customer['booking_id'] .
                                    ' - ' .
                                    $customer['event_date']
                                );
                                ?>
                            </option>

                        <?php endwhile; ?>

                    <?php endif; ?>

                </select>

                <small class="admin-muted">
                    Choose the customer booking that should receive this video.
                </small>

            </div>

            <!-- VIDEO FILE -->

            <div class="admin-form-group">

                <label for="video_file">
                    Select Video
                </label>

                <input
                    type="file"
                    id="video_file"
                    name="video_file"
                    accept=".mp4,.webm,.mov,video/mp4,video/webm,video/quicktime"
                    required
                >

                <small class="admin-muted">
                    MP4, WEBM or MOV. Maximum size: 100MB.
                </small>

            </div>

            <!-- MESSAGE -->

            <div class="admin-form-group full-width">

                <label for="message">
                    Message (Optional)
                </label>

                <textarea
                    name="message"
                    id="message"
                    placeholder="Enter a message about this video"
                ></textarea>

            </div>

            <!-- UPLOAD BUTTON -->

            <div class="full-width">

                <button
                    type="submit"
                    name="upload_video"
                    class="simple-btn"
                >
                    Upload Video
                </button>

            </div>

        </form>

    </section>

    <!-- ALL VIDEO MESSAGES -->

    <section class="admin-panel">

        <h2>All Video Messages</h2>

        <div class="admin-table-wrap">

            <table class="admin-table">

                <thead>

                    <tr>
                        <th>Video ID</th>
                        <th>Booking ID</th>
                        <th>Customer</th>
                        <th>Email</th>
                        <th>Event</th>
                        <th>Video Filename</th>
                        <th>Message</th>
                        <th>Status</th>
                        <th>Upload Date</th>
                    </tr>

                </thead>

                <tbody>

                <?php if ($videos && $videos->num_rows > 0): ?>

                    <?php while ($video = $videos->fetch_assoc()): ?>

                    <tr>

                        <td>
                            <?php echo (int)$video['video_id']; ?>
                        </td>

                        <td>
                            <?php echo (int)$video['booking_id']; ?>
                        </td>

                        <td>
                            <?php echo esc($video['customer_name'] ?? 'Unknown'); ?>
                        </td>

                        <td>
                            <?php echo esc($video['customer_email'] ?? ''); ?>
                        </td>

                        <td>
                            <?php echo esc($video['event_name'] ?? 'Unknown'); ?>
                        </td>

                        <td>
                            <span class="video-filename">
                                <?php echo esc($video['video_filename']); ?>
                            </span>
                        </td>

                        <td>
                            <?php echo esc($video['message']); ?>
                        </td>

                        <td>
                            <span class="video-status">
                                <?php echo esc($video['status']); ?>
                            </span>
                        </td>

                        <td>
                            <?php echo esc($video['upload_date']); ?>
                        </td>

                    </tr>

                    <?php endwhile; ?>

                <?php else: ?>

                    <tr>
                        <td colspan="9" class="empty-message">
                            No video messages available.
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
>>>>>>> Stashed changes
