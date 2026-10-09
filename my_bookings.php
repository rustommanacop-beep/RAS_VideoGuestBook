
<?php
session_start();

// CUSTOMER LOGIN ONLY
if (!isset($_SESSION['user_id'])) {
    header("Location: account.php");
    exit;
}

if (($_SESSION['role'] ?? '') === 'admin') {
    header("Location: admin.php");
    exit;
}

include("database/database.php");

$userId = (int)$_SESSION['user_id'];

function esc($value) {
    return htmlspecialchars(
        (string)($value ?? ''),
        ENT_QUOTES,
        'UTF-8'
    );
}

// CSRF TOKEN
if (!isset($_SESSION['booking_csrf'])) {
    $_SESSION['booking_csrf'] = bin2hex(random_bytes(32));
}

$error = '';
$success = '';
$editBooking = null;

// ====================================
// HANDLE EDIT AND CANCEL
// ====================================

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!hash_equals(
        $_SESSION['booking_csrf'],
        $_POST['csrf'] ?? ''
    )) {
        $error = "Invalid request. Please try again.";
    } else {

        $bookingId = (int)($_POST['booking_id'] ?? 0);
        $action = $_POST['action'] ?? '';

        if ($bookingId <= 0) {
            $error = "Invalid booking.";
        }

        elseif ($action === 'cancel') {

            $stmt = $conn->prepare(
                "UPDATE bookings
                 SET status = 'Cancelled'
                 WHERE booking_id = ?
                   AND user_id = ?
                   AND status IN ('Pending', 'Confirmed')"
            );

            $stmt->bind_param(
                "ii",
                $bookingId,
                $userId
            );

            $stmt->execute();

            if ($stmt->affected_rows === 1) {
                $success = "Your booking has been cancelled.";
            } else {
                $error = "This booking cannot be cancelled.";
            }

            $stmt->close();
        }

        elseif ($action === 'save') {

            $eventDate = trim($_POST['event_date'] ?? '');
            $bookingTime = trim($_POST['booking_time'] ?? '');
            $location = trim($_POST['location'] ?? '');
            $phone = trim($_POST['phone'] ?? '');
            $notes = trim($_POST['notes'] ?? '');

            $dateValid = DateTimeImmutable::createFromFormat(
                '!Y-m-d',
                $eventDate
            );

            $timeValid = DateTimeImmutable::createFromFormat(
                '!H:i',
                $bookingTime
            );

            if (
                !$dateValid ||
                $dateValid->format('Y-m-d') !== $eventDate
            ) {
                $error = "Please enter a valid event date.";
            }

            elseif (
                !$timeValid ||
                $timeValid->format('H:i') !== $bookingTime
            ) {
                $error = "Please enter a valid event time.";
            }

            elseif ($eventDate < date('Y-m-d')) {
                $error = "Event date cannot be in the past.";
            }

            elseif ($location === '') {
                $error = "Location is required.";
            }

            elseif (
                strlen($location) > 255 ||
                strlen($phone) > 50 ||
                strlen($notes) > 2000
            ) {
                $error = "One or more fields are too long.";
            }

            else {

                $stmt = $conn->prepare(
                    "UPDATE bookings
                     SET event_date = ?,
                         booking_time = ?,
                         location = ?,
                         phone = ?,
                         notes = ?
                     WHERE booking_id = ?
                       AND user_id = ?
                       AND status IN ('Pending', 'Confirmed')"
                );

                $stmt->bind_param(
                    "sssssii",
                    $eventDate,
                    $bookingTime,
                    $location,
                    $phone,
                    $notes,
                    $bookingId,
                    $userId
                );

                $stmt->execute();
                $stmt->close();

                $check = $conn->prepare(
                    "SELECT booking_id
                     FROM bookings
                     WHERE booking_id = ?
                       AND user_id = ?
                       AND status IN ('Pending', 'Confirmed')"
                );

                $check->bind_param(
                    "ii",
                    $bookingId,
                    $userId
                );

                $check->execute();

                if ($check->get_result()->num_rows === 1) {
                    $success = "Your booking has been updated.";
                } else {
                    $error = "This booking cannot be edited.";
                }

                $check->close();
            }
        }
    }
}

// ====================================
// LOAD BOOKING FOR EDIT FORM
// ====================================

$editId = (int)($_GET['edit'] ?? 0);

if ($editId > 0) {

    $stmt = $conn->prepare(
        "SELECT booking_id, event_date,
                booking_time, location,
                phone, notes
         FROM bookings
         WHERE booking_id = ?
           AND user_id = ?
           AND status IN ('Pending', 'Confirmed')"
    );

    $stmt->bind_param("ii", $editId, $userId);
    $stmt->execute();

    $editBooking = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$editBooking) {
        $error = "Booking not found or cannot be edited.";
    }
}

// ====================================
// CUSTOMER BOOKINGS
// ====================================

$stmt = $conn->prepare(
    "SELECT b.*, e.event_name
     FROM bookings b
     JOIN events e ON b.event_id = e.event_id
     WHERE b.user_id = ?
     ORDER BY b.created_at DESC"
);

$stmt->bind_param("i", $userId);
$stmt->execute();

$bookings = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// ====================================
// CUSTOMER VIDEOS
// ====================================

$videoStmt = $conn->prepare(
    "SELECT v.video_id,
            v.booking_id,
            v.message,
            e.event_name
     FROM video_messages v
     JOIN bookings b ON v.booking_id = b.booking_id
     JOIN events e ON b.event_id = e.event_id
     WHERE b.user_id = ?
     ORDER BY v.upload_date DESC"
);

$videoStmt->bind_param("i", $userId);
$videoStmt->execute();

$videos = $videoStmt->get_result()->fetch_all(MYSQLI_ASSOC);
$videoStmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>My Bookings - RAS Video Guest Book</title>

<link rel="icon" href="pictures/ras.png">
<link rel="stylesheet" href="css/style.css">

<style>
body {
    margin: 0;
    background: #f7f2e9;
    font-family: Arial, sans-serif;
    color: #332b24;
}

.customer-header {
    background: #211b17;
    text-align: center;
    padding: 20px;
}

.customer-header img {
    width: 75px;
    height: 75px;
    object-fit: contain;
}

.customer-header h2 {
    color: #c59445;
    margin: 10px 0 0;
}

.customer-nav {
    background: #302820;
    padding: 15px;
    text-align: center;
}

.customer-nav a {
    color: white;
    text-decoration: none;
    margin: 0 15px;
    font-size: 14px;
}

.customer-nav a:hover {
    color: #c59445;
}

.customer-container {
    max-width: 1150px;
    margin: 40px auto;
    padding: 0 20px;
}

.customer-container h1,
.customer-container h2 {
    color: #604126;
}

.table-section,
.edit-section {
    background: white;
    padding: 25px;
    margin-top: 30px;
    border-radius: 8px;
    border: 1px solid #e8dfd2;
}

.table-wrap {
    width: 100%;
    overflow-x: auto;
}

.customer-table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 15px;
}

.customer-table th,
.customer-table td {
    border: 1px solid #e5ddd3;
    padding: 13px;
    text-align: left;
    font-size: 13px;
}

.customer-table th {
    background: #604126;
    color: white;
}

.customer-table tr:nth-child(even) {
    background: #faf7f2;
}

.status {
    font-weight: bold;
}

.status.pending {
    color: #a66b19;
}

.status.confirmed {
    color: #26763b;
}

.status.completed {
    color: #19764d;
}

.status.cancelled {
    color: #b33131;
}

/* STANDARD BUTTONS */
.customer-btn {
    display: inline-block;
    background: #c59445;
    color: white;
    text-decoration: none;
    padding: 9px 14px;
    border: 0;
    border-radius: 5px;
    font-size: 12px;
    font-weight: bold;
    cursor: pointer;
    margin: 3px;
}

.customer-btn:hover {
    background: #a87930;
}

/* EDIT AND CANCEL: NO COLOURED BUTTONS */
.action-link,
.action-link:visited {
    display: inline;
    background: transparent;
    color: #333;
    border: none;
    border-radius: 0;
    padding: 0;
    margin: 0 12px 0 0;
    font-family: Arial, sans-serif;
    font-size: 13px;
    font-weight: normal;
    text-decoration: underline;
    cursor: pointer;
    box-shadow: none;
}

.action-link:hover {
    background: transparent;
    color: #333;
}

.edit-form {
    max-width: 600px;
}

.edit-form label {
    display: block;
    font-weight: bold;
    margin: 15px 0 7px;
    color: #604126;
}

.edit-form input,
.edit-form textarea {
    width: 100%;
    padding: 11px;
    box-sizing: border-box;
    border: 1px solid #ddd;
    border-radius: 5px;
    font: inherit;
}

.edit-form textarea {
    min-height: 100px;
}

.message {
    padding: 14px;
    margin: 20px 0;
    border-radius: 6px;
}

.message.success {
    background: #e4f4e7;
    color: #27783b;
}

.message.error {
    background: #fce7e7;
    color: #a32c2c;
}

.customer-footer {
    background: #211b17;
    text-align: center;
    padding: 25px;
    margin-top: 50px;
    color: white;
}

@media (max-width: 700px) {
    .customer-nav a {
        display: inline-block;
        margin: 8px;
    }

    .table-section,
    .edit-section {
        padding: 15px;
    }
}
</style>
</head>

<body>

<header class="customer-header">
    <a href="index.php">
        <img src="pictures/ras.png" alt="RAS Logo">
    </a>
    <h2>RAS VIDEO GUEST BOOK</h2>
</header>

<nav class="customer-nav">
    <a href="index.php">HOME</a>
    <a href="events.php">BOOK NOW</a>
    <a href="my_bookings.php">MY BOOKINGS</a>
    <a href="#my-videos">MY VIDEOS</a>
    <a href="profile.php">PROFILE</a>
    <a href="logout.php">LOGOUT</a>
</nav>

<main class="customer-container">

    <h1>My Bookings</h1>

    <p>
        Welcome,
        <strong>
            <?php echo esc($_SESSION['name'] ?? 'Customer'); ?>
        </strong>
    </p>

    <p>
        View, edit or cancel your bookings below.
    </p>

    <?php if ($error !== '') { ?>
        <div class="message error">
            <?php echo esc($error); ?>
        </div>
    <?php } ?>

    <?php if ($success !== '') { ?>
        <div class="message success">
            <?php echo esc($success); ?>
        </div>
    <?php } ?>

    <!-- EDIT BOOKING FORM -->

    <?php if ($editBooking) { ?>

        <section class="edit-section" id="edit-booking">

            <h2>
                Edit Booking
                #<?php echo (int)$editBooking['booking_id']; ?>
            </h2>

            <form method="POST" class="edit-form">

                <input
                    type="hidden"
                    name="csrf"
                    value="<?php echo esc($_SESSION['booking_csrf']); ?>"
                >

                <input
                    type="hidden"
                    name="action"
                    value="save"
                >

                <input
                    type="hidden"
                    name="booking_id"
                    value="<?php echo (int)$editBooking['booking_id']; ?>"
                >

                <label>Event Date</label>
                <input
                    type="date"
                    name="event_date"
                    value="<?php echo esc($editBooking['event_date']); ?>"
                    min="<?php echo date('Y-m-d'); ?>"
                    required
                >

                <label>Event Time</label>
                <input
                    type="time"
                    name="booking_time"
                    value="<?php echo esc(substr($editBooking['booking_time'], 0, 5)); ?>"
                    required
                >

                <label>Location</label>
                <input
                    type="text"
                    name="location"
                    value="<?php echo esc($editBooking['location']); ?>"
                    maxlength="255"
                    required
                >

                <label>Phone</label>
                <input
                    type="text"
                    name="phone"
                    value="<?php echo esc($editBooking['phone']); ?>"
                    maxlength="50"
                >

                <label>Notes</label>
                <textarea
                    name="notes"
                    maxlength="2000"
                ><?php echo esc($editBooking['notes']); ?></textarea>

                <button
                    type="submit"
                    class="customer-btn"
                >
                    SAVE CHANGES
                </button>

                <a
                    href="my_bookings.php"
                    class="customer-btn"
                >
                    BACK
                </a>

            </form>

        </section>

    <?php } ?>

    <!-- MY BOOKINGS TABLE -->

    <section class="table-section" id="bookings">

        <h2>My Reservations</h2>

        <div class="table-wrap">

            <table class="customer-table">

                <thead>
                    <tr>
                        <th>Booking ID</th>
                        <th>Service</th>
                        <th>Customer</th>
                        <th>Event Date</th>
                        <th>Time</th>
                        <th>Location</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>

                <tbody>

                <?php if (count($bookings) > 0) { ?>

                    <?php foreach ($bookings as $booking) { ?>

                        <?php
                        $status = strtolower(
                            $booking['status'] ?? 'pending'
                        );

                        $canEdit = in_array(
                            $booking['status'],
                            ['Pending', 'Confirmed'],
                            true
                        );
                        ?>

                        <tr>

                            <td>
                                #<?php echo (int)$booking['booking_id']; ?>
                            </td>

                            <td>
                                <?php echo esc($booking['event_name']); ?>
                            </td>

                            <td>
                                <?php echo esc($booking['customer_name']); ?>
                            </td>

                            <td>
                                <?php echo esc($booking['event_date']); ?>
                            </td>

                            <td>
                                <?php echo esc($booking['booking_time']); ?>
                            </td>

                            <td>
                                <?php echo esc($booking['location']); ?>
                            </td>

                            <td>
                                <span class="status <?php echo esc($status); ?>">
                                    <?php echo esc($booking['status']); ?>
                                </span>
                            </td>

                            <td>

                                <?php if ($canEdit) { ?>

                                    <a
                                        href="my_bookings.php?edit=<?php
                                            echo (int)$booking['booking_id'];
                                        ?>#edit-booking"
                                        class="action-link"
                                    >
                                        EDIT
                                    </a>

                                    <form
                                        method="POST"
                                        style="display:inline;"
                                        onsubmit="return confirm('Are you sure you want to cancel this booking?');"
                                    >

                                        <input
                                            type="hidden"
                                            name="csrf"
                                            value="<?php echo esc($_SESSION['booking_csrf']); ?>"
                                        >

                                        <input
                                            type="hidden"
                                            name="action"
                                            value="cancel"
                                        >

                                        <input
                                            type="hidden"
                                            name="booking_id"
                                            value="<?php echo (int)$booking['booking_id']; ?>"
                                        >

                                        <button
                                            type="submit"
                                            class="action-link"
                                        >
                                            CANCEL
                                        </button>

                                    </form>

                                <?php } else { ?>

                                    <span>Not available</span>

                                <?php } ?>

                            </td>

                        </tr>

                    <?php } ?>

                <?php } else { ?>

                    <tr>
                        <td colspan="8" style="text-align:center;">
                            You have no bookings yet.
                        </td>
                    </tr>

                <?php } ?>

                </tbody>

            </table>

        </div>

        <a href="events.php" class="customer-btn">
            + BOOK A SERVICE
        </a>

    </section>

    <!-- MY VIDEOS TABLE -->

    <section class="table-section" id="my-videos">

        <h2>My Videos</h2>

        <p>
            Videos uploaded by the RAS admin
            will appear here.
        </p>

        <div class="table-wrap">

            <table class="customer-table">

                <thead>
                    <tr>
                        <th>Video ID</th>
                        <th>Booking ID</th>
                        <th>Service</th>
                        <th>Message</th>
                        <th>Video</th>
                    </tr>
                </thead>

                <tbody>

                <?php if (count($videos) > 0) { ?>

                    <?php foreach ($videos as $video) { ?>

                        <tr>

                            <td>
                                #<?php echo (int)$video['video_id']; ?>
                            </td>

                            <td>
                                #<?php echo (int)$video['booking_id']; ?>
                            </td>

                            <td>
                                <?php echo esc($video['event_name']); ?>
                            </td>

                            <td>
                                <?php echo esc($video['message']); ?>
                            </td>

                            <td>
                                <a
                                    href="watch_video.php?id=<?php
                                        echo (int)$video['video_id'];
                                    ?>"
                                    target="_blank"
                                    rel="noopener"
                                    class="customer-btn"
                                >
                                    WATCH
                                </a>

                                <a
                                    href="watch_video.php?id=<?php
                                        echo (int)$video['video_id'];
                                    ?>&download=1"
                                    class="customer-btn"
                                >
                                    DOWNLOAD
                                </a>
                            </td>

                        </tr>

                    <?php } ?>

                <?php } else { ?>

                    <tr>
                        <td colspan="5" style="text-align:center;">
                            No videos available yet.
                        </td>
                    </tr>

                <?php } ?>

                </tbody>

            </table>

        </div>

    </section>

</main>

<footer class="customer-footer">
    &copy; <?php echo date('Y'); ?>
    RAS Video Guest Book
</footer>

</body>
</html>
