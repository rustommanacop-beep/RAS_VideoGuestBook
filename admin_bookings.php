
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

/* PROCESS EDIT AND CANCEL */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $token = $_POST['csrf'] ?? '';

    if (
        !is_string($token) ||
        !hash_equals($_SESSION['admin_csrf'], $token)
    ) {
        http_response_code(403);
        exit("Invalid request.");
    }

    $booking_id = (int)($_POST['booking_id'] ?? 0);

    if ($booking_id <= 0) {
        $_SESSION['admin_message'] = "Invalid booking ID.";
        header("Location: admin_bookings.php");
        exit;
    }

    /* CANCEL BOOKING */

    if (isset($_POST['cancel_booking'])) {

        $stmt = $conn->prepare("
            UPDATE bookings
            SET status = 'Cancelled'
            WHERE booking_id = ?
              AND status <> 'Cancelled'
        ");

        if (!$stmt) {
            $_SESSION['admin_message'] = "Unable to cancel booking.";
            header("Location: admin_bookings.php");
            exit;
        }

        $stmt->bind_param("i", $booking_id);

        if ($stmt->execute()) {

            if ($stmt->affected_rows > 0) {
                $_SESSION['admin_message'] =
                    "Booking " . $booking_id .
                    " cancelled successfully.";
            } else {
                $_SESSION['admin_message'] =
                    "Booking not found or already cancelled.";
            }

        } else {
            $_SESSION['admin_message'] = "Unable to cancel booking.";
        }

        $stmt->close();

        header("Location: admin_bookings.php");
        exit;
    }

    /* EDIT BOOKING */

    if (isset($_POST['save_booking'])) {

        $customer_name = trim($_POST['customer_name'] ?? '');
        $customer_email = trim($_POST['customer_email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $event_id = (int)($_POST['event_id'] ?? 0);
        $event_date = trim($_POST['event_date'] ?? '');
        $booking_time = trim($_POST['booking_time'] ?? '');
        $location = trim($_POST['location'] ?? '');
        $notes = trim($_POST['notes'] ?? '');
        $status = $_POST['status'] ?? '';

        $allowedStatuses = [
            'Pending',
            'Confirmed',
            'Completed',
            'Cancelled'
        ];

        $dateObject = DateTimeImmutable::createFromFormat(
            '!Y-m-d',
            $event_date
        );

        $validDate = $dateObject &&
            $dateObject->format('Y-m-d') === $event_date;

        $timeObject = DateTimeImmutable::createFromFormat(
            '!H:i',
            $booking_time
        );

        $validTime = $timeObject &&
            $timeObject->format('H:i') === $booking_time;

        if (
            $customer_name === '' ||
            strlen($customer_name) > 255 ||
            !filter_var($customer_email, FILTER_VALIDATE_EMAIL) ||
            strlen($customer_email) > 255 ||
            strlen($phone) > 50 ||
            strlen($location) > 255 ||
            strlen($notes) > 5000 ||
            $event_id <= 0 ||
            !$validDate ||
            !$validTime ||
            !in_array($status, $allowedStatuses, true)
        ) {

            $_SESSION['admin_message'] =
                "Please enter valid booking details.";

            header(
                "Location: admin_bookings.php?edit=" . $booking_id
            );
            exit;
        }

        /* VERIFY BOOKING EXISTS */

        $checkBooking = $conn->prepare("
            SELECT booking_id
            FROM bookings
            WHERE booking_id = ?
        ");

        if (!$checkBooking) {
            $_SESSION['admin_message'] = "Unable to verify booking.";
            header("Location: admin_bookings.php");
            exit;
        }

        $checkBooking->bind_param("i", $booking_id);
        $checkBooking->execute();

        $bookingExists = $checkBooking->get_result()->num_rows > 0;

        $checkBooking->close();

        if (!$bookingExists) {
            $_SESSION['admin_message'] = "Booking not found.";
            header("Location: admin_bookings.php");
            exit;
        }

        /* VERIFY EVENT */

        $eventStmt = $conn->prepare("
            SELECT event_id
            FROM events
            WHERE event_id = ?
        ");

        if (!$eventStmt) {
            $_SESSION['admin_message'] = "Unable to verify event.";
            header("Location: admin_bookings.php");
            exit;
        }

        $eventStmt->bind_param("i", $event_id);
        $eventStmt->execute();

        $eventExists = $eventStmt->get_result()->num_rows > 0;

        $eventStmt->close();

        if (!$eventExists) {

            $_SESSION['admin_message'] =
                "Selected event does not exist.";

            header(
                "Location: admin_bookings.php?edit=" . $booking_id
            );
            exit;
        }

        /* UPDATE BOOKING */

        $stmt = $conn->prepare("
            UPDATE bookings
            SET
                customer_name = ?,
                customer_email = ?,
                phone = ?,
                event_id = ?,
                event_date = ?,
                booking_time = ?,
                location = ?,
                notes = ?,
                status = ?
            WHERE booking_id = ?
        ");

        if (!$stmt) {
            $_SESSION['admin_message'] = "Unable to update booking.";
            header("Location: admin_bookings.php");
            exit;
        }

        $stmt->bind_param(
            "sssisssssi",
            $customer_name,
            $customer_email,
            $phone,
            $event_id,
            $event_date,
            $booking_time,
            $location,
            $notes,
            $status,
            $booking_id
        );

        if ($stmt->execute()) {
            $_SESSION['admin_message'] =
                "Booking " . $booking_id .
                " updated successfully.";
        } else {
            $_SESSION['admin_message'] = "Unable to update booking.";
        }

        $stmt->close();

        header("Location: admin_bookings.php");
        exit;
    }
}

/* NOTIFICATION */

$message = $_SESSION['admin_message'] ?? '';
unset($_SESSION['admin_message']);

/* BOOKING STATISTICS */

$stats = [
    'total' => 0,
    'pending' => 0,
    'confirmed' => 0,
    'completed' => 0,
    'cancelled' => 0
];

$statsResult = $conn->query("
    SELECT
        COUNT(*) AS total,
        COALESCE(SUM(status = 'Pending'), 0) AS pending,
        COALESCE(SUM(status = 'Confirmed'), 0) AS confirmed,
        COALESCE(SUM(status = 'Completed'), 0) AS completed,
        COALESCE(SUM(status = 'Cancelled'), 0) AS cancelled
    FROM bookings
");

if ($statsResult) {
    $data = $statsResult->fetch_assoc();

    foreach ($stats as $key => $value) {
        $stats[$key] = (int)$data[$key];
    }
}

/* LOAD EVENTS */

$events = [];

$eventResult = $conn->query("
    SELECT event_id, event_name
    FROM events
    ORDER BY event_name ASC
");

if ($eventResult) {
    while ($row = $eventResult->fetch_assoc()) {
        $events[] = $row;
    }
}

/* LOAD SELECTED BOOKING */

$editBooking = null;

if (isset($_GET['edit'])) {

    $edit_id = (int)$_GET['edit'];

    if ($edit_id > 0) {

        $stmt = $conn->prepare("
            SELECT *
            FROM bookings
            WHERE booking_id = ?
        ");

        if ($stmt) {
            $stmt->bind_param("i", $edit_id);
            $stmt->execute();

            $editBooking = $stmt->get_result()->fetch_assoc();

            $stmt->close();
        }
    }
}

/* LOAD BOOKINGS ALPHABETICALLY */

$bookings = $conn->query("
    SELECT
        b.booking_id,
        b.booking_code,
        b.customer_name,
        b.customer_email,
        b.phone,
        b.event_id,
        b.event_date,
        b.booking_time,
        b.location,
        b.notes,
        b.status,
        e.event_name
    FROM bookings b
    LEFT JOIN events e
        ON b.event_id = e.event_id
    ORDER BY b.customer_name ASC, b.booking_id ASC
");
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Customer Bookings - RAS Admin</title>

    <link rel="icon" href="pictures/ras.png">
    <link rel="stylesheet" href="css/style.css">

    <style>
        * {
            box-sizing: border-box;
        }

        body.admin-page {
            margin: 0;
            background: #f7f2e9;
            font-family: Arial, sans-serif;
            color: #2b2520;
            line-height: 1.6;
        }

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
            color: #c8bbaa;
            font-size: 12px;
        }

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

        /* STATISTICS */

        .admin-stats {
            display: grid;
            grid-template-columns: repeat(5, minmax(0, 1fr));
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

        /* PANELS */

        .admin-panel {
            background: white;
            padding: 25px;
            margin-bottom: 30px;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.04);
        }

        .admin-panel h2 {
            color: #604126;
            margin: 0 0 20px;
            font-size: 22px;
        }

        /* TABLE */

        .admin-table-wrap {
            width: 100%;
            overflow-x: auto;
        }

        .admin-table {
            width: 100%;
            min-width: 1200px;
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

        .booking-number {
            font-weight: bold;
            color: #2b2520;
        }

        .booking-status {
            font-size: 13px;
            color: #2b2520;
        }

        /* BUTTONS */

        .admin-actions {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .admin-actions form {
            margin: 0;
        }

        .simple-btn,
        .simple-btn:link,
        .simple-btn:visited {
            display: inline-block;
            width: auto;
            padding: 7px 13px;
            background: #eeeeee;
            color: #222222;
            border: 1px solid #aaaaaa;
            border-radius: 4px;
            font-family: Arial, sans-serif;
            font-size: 12px;
            text-decoration: none;
            text-align: center;
            cursor: pointer;
            white-space: nowrap;
        }

        .simple-btn:hover {
            background: #dddddd;
            color: #222222;
        }

        .simple-btn:disabled {
            background: #f5f5f5;
            color: #999999;
            border-color: #dddddd;
            cursor: not-allowed;
        }

        /* EDIT FORM */

        .admin-edit-form {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 18px;
        }

        .admin-form-group {
            display: flex;
            flex-direction: column;
            gap: 7px;
        }

        .admin-form-group label {
            font-size: 13px;
            font-weight: bold;
            color: #604126;
        }

        .admin-form-group input,
        .admin-form-group select,
        .admin-form-group textarea {
            width: 100%;
            padding: 11px;
            border: 1px solid #cccccc;
            border-radius: 4px;
            background: white;
            color: #222222;
            font-family: Arial, sans-serif;
            font-size: 14px;
        }

        .admin-form-group textarea {
            min-height: 100px;
            resize: vertical;
        }

        .full-width {
            grid-column: 1 / -1;
        }

        .admin-form-actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        /* ALERT */

        .admin-alert {
            padding: 14px 18px;
            margin-bottom: 20px;
            background: #f2f2f2;
            color: #333333;
            border: 1px solid #dddddd;
            border-radius: 6px;
            font-size: 14px;
        }

        /* RESPONSIVE */

        @media (max-width: 1100px) {
            .admin-stats {
                grid-template-columns: repeat(3, 1fr);
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
                grid-template-columns: repeat(2, 1fr);
                gap: 10px;
            }

            .stat-card {
                padding: 15px;
            }

            .admin-panel {
                padding: 15px;
            }

            .admin-edit-form {
                grid-template-columns: 1fr;
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
            <a href="admin_bookings.php" class="active">Customer Bookings</a>
            <a href="admin_events.php">Manage Events</a>
            <a href="admin_videos.php">Video Messages</a>
            <a href="logout.php">Logout</a>
        </nav>

    </aside>

    <!-- MAIN CONTENT -->

    <main class="admin-main">

        <div class="admin-topbar">

            <div>
                <h1>Customer Bookings</h1>
                <p>View, edit and cancel customer bookings.</p>
            </div>

            <div class="admin-user">
                Administrator:
                <strong><?php echo esc($_SESSION['name'] ?? 'Admin'); ?></strong>
            </div>

        </div>

        <!-- BOOKING STATISTICS -->

        <div class="admin-stats">

            <div class="stat-card">
                <h2><?php echo $stats['total']; ?></h2>
                <p>Total Bookings</p>
            </div>

            <div class="stat-card">
                <h2><?php echo $stats['pending']; ?></h2>
                <p>Pending</p>
            </div>

            <div class="stat-card">
                <h2><?php echo $stats['confirmed']; ?></h2>
                <p>Confirmed</p>
            </div>

            <div class="stat-card">
                <h2><?php echo $stats['completed']; ?></h2>
                <p>Completed</p>
            </div>

            <div class="stat-card">
                <h2><?php echo $stats['cancelled']; ?></h2>
                <p>Cancelled</p>
            </div>

        </div>

        <!-- NOTIFICATION -->

        <?php if ($message !== '') { ?>
            <div class="admin-alert">
                <?php echo esc($message); ?>
            </div>
        <?php } ?>

        <!-- EDIT BOOKING FORM -->

        <?php if ($editBooking) { ?>

        <section class="admin-panel" id="edit-booking">

            <h2>
                Edit Booking <?php echo (int)$editBooking['booking_id']; ?>
            </h2>

            <form method="POST" class="admin-edit-form">

                <input
                    type="hidden"
                    name="csrf"
                    value="<?php echo esc($_SESSION['admin_csrf']); ?>"
                >

                <input
                    type="hidden"
                    name="booking_id"
                    value="<?php echo (int)$editBooking['booking_id']; ?>"
                >

                <div class="admin-form-group">
                    <label for="customer_name">Customer Name</label>
                    <input
                        type="text"
                        id="customer_name"
                        name="customer_name"
                        maxlength="255"
                        value="<?php echo esc($editBooking['customer_name']); ?>"
                        required
                    >
                </div>

                <div class="admin-form-group">
                    <label for="customer_email">Email Address</label>
                    <input
                        type="email"
                        id="customer_email"
                        name="customer_email"
                        maxlength="255"
                        value="<?php echo esc($editBooking['customer_email']); ?>"
                        required
                    >
                </div>

                <div class="admin-form-group">
                    <label for="phone">Phone Number</label>
                    <input
                        type="text"
                        id="phone"
                        name="phone"
                        maxlength="50"
                        value="<?php echo esc($editBooking['phone']); ?>"
                    >
                </div>

                <div class="admin-form-group">
                    <label for="event_id">Event</label>
                    <select id="event_id" name="event_id" required>

                        <?php foreach ($events as $event) { ?>

                            <option
                                value="<?php echo (int)$event['event_id']; ?>"
                                <?php
                                echo (int)$editBooking['event_id'] ===
                                     (int)$event['event_id']
                                     ? 'selected'
                                     : '';
                                ?>
                            >
                                <?php echo esc($event['event_name']); ?>
                            </option>

                        <?php } ?>

                    </select>
                </div>

                <div class="admin-form-group">
                    <label for="event_date">Event Date</label>
                    <input
                        type="date"
                        id="event_date"
                        name="event_date"
                        value="<?php echo esc($editBooking['event_date']); ?>"
                        required
                    >
                </div>

                <div class="admin-form-group">
                    <label for="booking_time">Booking Time</label>
                    <input
                        type="time"
                        id="booking_time"
                        name="booking_time"
                        value="<?php echo esc(substr((string)$editBooking['booking_time'], 0, 5)); ?>"
                        required
                    >
                </div>

                <div class="admin-form-group">
                    <label for="location">Location</label>
                    <input
                        type="text"
                        id="location"
                        name="location"
                        maxlength="255"
                        value="<?php echo esc($editBooking['location']); ?>"
                    >
                </div>

                <div class="admin-form-group">
                    <label for="status">Booking Status</label>
                    <select id="status" name="status" required>

                        <?php
                        $statuses = [
                            'Pending',
                            'Confirmed',
                            'Completed',
                            'Cancelled'
                        ];

                        foreach ($statuses as $status) {
                        ?>

                            <option
                                value="<?php echo esc($status); ?>"
                                <?php
                                echo $editBooking['status'] === $status
                                    ? 'selected'
                                    : '';
                                ?>
                            >
                                <?php echo esc($status); ?>
                            </option>

                        <?php } ?>

                    </select>
                </div>

                <div class="admin-form-group full-width">
                    <label for="notes">Notes</label>
                    <textarea
                        id="notes"
                        name="notes"
                        maxlength="5000"
                    ><?php echo esc($editBooking['notes']); ?></textarea>
                </div>

                <div class="admin-form-actions full-width">

                    <button
                        type="submit"
                        name="save_booking"
                        class="simple-btn"
                    >
                        Save Changes
                    </button>

                    <a href="admin_bookings.php" class="simple-btn">
                        Back
                    </a>

                </div>

            </form>

        </section>

        <?php } ?>

        <!-- BOOKINGS TABLE -->

        <section class="admin-panel">

            <h2>All Customer Bookings</h2>

            <div class="admin-table-wrap">

                <table class="admin-table">

                    <thead>
                        <tr>
                            <th>Booking Number</th>
                            <th>Customer Name</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Event</th>
                            <th>Event Date</th>
                            <th>Time</th>
                            <th>Location</th>
                            <th>Notes</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>

                    <tbody>

                    <?php if ($bookings && $bookings->num_rows > 0) { ?>

                        <?php while ($booking = $bookings->fetch_assoc()) { ?>

                            <tr>

                                <td>
                                    <span class="booking-number">
                                        <?php echo (int)$booking['booking_id']; ?>
                                    </span>
                                </td>

                                <td><?php echo esc($booking['customer_name']); ?></td>
                                <td><?php echo esc($booking['customer_email']); ?></td>
                                <td><?php echo esc($booking['phone']); ?></td>
                                <td><?php echo esc($booking['event_name'] ?? 'Unknown'); ?></td>
                                <td><?php echo esc($booking['event_date']); ?></td>
                                <td><?php echo esc($booking['booking_time']); ?></td>
                                <td><?php echo esc($booking['location']); ?></td>
                                <td><?php echo esc($booking['notes']); ?></td>

                                <td>
                                    <span class="booking-status">
                                        <?php echo esc($booking['status']); ?>
                                    </span>
                                </td>

                                <td>

                                    <div class="admin-actions">

                                        <a
                                            href="admin_bookings.php?edit=<?php echo (int)$booking['booking_id']; ?>#edit-booking"
                                            class="simple-btn"
                                        >
                                            Edit
                                        </a>

                                        <form
                                            method="POST"
                                            onsubmit="return confirm('Are you sure you want to cancel this booking?');"
                                        >

                                            <input
                                                type="hidden"
                                                name="csrf"
                                                value="<?php echo esc($_SESSION['admin_csrf']); ?>"
                                            >

                                            <input
                                                type="hidden"
                                                name="booking_id"
                                                value="<?php echo (int)$booking['booking_id']; ?>"
                                            >

                                            <button
                                                type="submit"
                                                name="cancel_booking"
                                                class="simple-btn"
                                                <?php
                                                echo $booking['status'] === 'Cancelled'
                                                    ? 'disabled'
                                                    : '';
                                                ?>
                                            >
                                                Cancel
                                            </button>

                                        </form>

                                    </div>

                                </td>

                            </tr>

                        <?php } ?>

                    <?php } else { ?>

                        <tr>
                            <td colspan="11">No customer bookings found.</td>
                        </tr>

                    <?php } ?>

                    </tbody>

                </table>

            </div>

        </section>

    </main>

</div>

</body>
</html>