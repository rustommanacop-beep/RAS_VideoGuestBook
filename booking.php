<?php
session_start();

require_once "database/database.php";

/* SECURITY */

function esc($value) {
    return htmlspecialchars(
        (string)($value ?? ''),
        ENT_QUOTES,
        'UTF-8'
    );
}

/* GET SELECTED EVENT */

$id = (int)($_GET['id'] ?? 0);
$message = "";

$stmt = $conn->prepare(
    "SELECT * FROM events WHERE event_id = ?"
);

$stmt->bind_param("i", $id);
$stmt->execute();

$event = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$event) {
    header("Location: events.php");
    exit;
}

/* CSRF TOKEN */

if (empty($_SESSION['booking_csrf'])) {
    $_SESSION['booking_csrf'] = bin2hex(random_bytes(32));
}

/* POPUP INFORMATION */

$showPopup = false;
$bookingDetails = null;

/* SHOW CONFIRMATION AFTER REDIRECT */

if (isset($_SESSION['booking_success'])) {

    $bookingDetails = $_SESSION['booking_success'];

    if ((int)$bookingDetails['event_id'] === $id) {
        $showPopup = true;
        unset($_SESSION['booking_success']);
    }
}

/* PROCESS BOOKING */

if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['book'])
) {

    /* VALIDATE REQUEST */

    $token = $_POST['csrf'] ?? '';

    if (
        !is_string($token) ||
        !hash_equals($_SESSION['booking_csrf'], $token)
    ) {
        http_response_code(403);
        exit("Invalid booking request.");
    }

    /* GUEST USERS CAN HAVE NULL USER ID */

    $user_id = isset($_SESSION['user_id'])
        ? (int)$_SESSION['user_id']
        : null;

    /* CUSTOMER DETAILS */

    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');

    /* EVENT DETAILS */

    $date = trim($_POST['event_date'] ?? '');
    $time = trim($_POST['booking_time'] ?? '');
    $location = trim($_POST['location'] ?? '');
    $notes = trim($_POST['notes'] ?? '');

    /* DATE AND TIME VALIDATION */

    $valid_date = DateTime::createFromFormat(
        '!Y-m-d',
        $date
    );

    $valid_time = DateTime::createFromFormat(
        '!H:i',
        $time
    );

    if (
        $name === "" ||
        !filter_var($email, FILTER_VALIDATE_EMAIL) ||
        $phone === "" ||
        $location === "" ||
        !$valid_date ||
        $valid_date->format('Y-m-d') !== $date ||
        $date < date('Y-m-d') ||
        !$valid_time ||
        $valid_time->format('H:i') !== $time
    ) {

        $message = "Please complete the booking details correctly.";

    } else {

        /* CHECK EXISTING BOOKING */

        $check = $conn->prepare("
            SELECT booking_id
            FROM bookings
            WHERE event_id = ?
              AND event_date = ?
              AND booking_time = ?
              AND status != 'Cancelled'
            LIMIT 1
        ");

        $check->bind_param(
            "iss",
            $id,
            $date,
            $time
        );

        $check->execute();
        $check->store_result();

        if ($check->num_rows > 0) {

            $message = "This service is already booked at that time.";

        } else {

            /* GENERATE BOOKING CODE */

            $code = bin2hex(random_bytes(32));

            /* SAVE BOOKING */

            $insert = $conn->prepare("
                INSERT INTO bookings
                (
                    user_id,
                    event_id,
                    customer_name,
                    customer_email,
                    phone,
                    event_date,
                    booking_time,
                    location,
                    notes,
                    status,
                    booking_code
                )
                VALUES
                (
                    ?, ?, ?, ?, ?, ?, ?, ?, ?,
                    'Pending', ?
                )
            ");

            $insert->bind_param(
                "iissssssss",
                $user_id,
                $id,
                $name,
                $email,
                $phone,
                $date,
                $time,
                $location,
                $notes,
                $code
            );

            if ($insert->execute()) {

                /* GET ACTUAL BOOKING NUMBER */

                $bookingNumber = $conn->insert_id;

                /* SAVE POPUP INFORMATION */

                $_SESSION['booking_success'] = [
                    'booking_id' => $bookingNumber,
                    'event_id' => $id,
                    'customer_name' => $name,
                    'customer_email' => $email,
                    'event_name' => $event['event_name'],
                    'event_date' => $date,
                    'booking_time' => $time,
                    'location' => $location,
                    'status' => 'Pending'
                ];

                /* NEW TOKEN FOR NEXT BOOKING */

                $_SESSION['booking_csrf'] =
                    bin2hex(random_bytes(32));

                $insert->close();
                $check->close();

                /* RETURN TO BOOKING PAGE AND SHOW POPUP */

                header(
                    "Location: booking.php?id=" . $id
                );
                exit;

            } else {

                $message = "Unable to save booking. Please try again.";
            }

            $insert->close();
        }

        $check->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Book Your Event - RAS</title>

<link rel="icon" href="pictures/ras.png">
<link rel="stylesheet" href="css/style.css">

<style>

/* BOOKING FORM */

.booking-form {
    max-width: 650px;
    margin: 25px auto;
}

.booking-form input,
.booking-form textarea {
    width: 100%;
    box-sizing: border-box;
}

/* ERROR MESSAGE */

.booking-error {
    text-align: center;
    color: #a33a3a;
    font-size: 14px;
    margin: 15px auto;
}

/* POPUP BACKGROUND */

.booking-popup-overlay {
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, 0.6);
    display: flex;
    justify-content: center;
    align-items: center;
    padding: 20px;
    z-index: 9999;
}

/* POPUP BOX */

.booking-popup {
    background: #ffffff;
    width: 100%;
    max-width: 450px;
    padding: 35px 25px;
    border-radius: 12px;
    text-align: center;
    box-shadow: 0 8px 30px rgba(0,0,0,0.2);
    font-family: Arial, sans-serif;
}

/* SUCCESS ICON */

.booking-success-icon {
    font-size: 55px;
    color: #4b8a57;
    margin-bottom: 15px;
}

/* POPUP TITLE */

.booking-popup h2 {
    font-size: 25px;
    color: #604126;
    margin: 0 0 15px;
}

/* POPUP TEXT */

.booking-popup p {
    font-size: 14px;
    color: #555555;
    line-height: 1.7;
}

/* BOOKING DETAILS */

.booking-popup-details {
    background: #f7f2e9;
    border-radius: 7px;
    padding: 18px;
    margin: 20px 0;
    text-align: left;
}

.booking-popup-details p {
    margin: 8px 0;
    font-size: 14px;
}

.booking-popup-details strong {
    color: #604126;
}

/* SIMPLE BUTTON */

.booking-popup-button {
    display: inline-block;
    padding: 11px 28px;
    margin-top: 12px;
    background: #eeeeee;
    color: #222222;
    border: 1px solid #aaaaaa;
    border-radius: 4px;
    font-size: 14px;
    font-weight: normal;
    font-family: Arial, sans-serif;
    text-decoration: none;
    cursor: pointer;
}

.booking-popup-button:hover {
    background: #dddddd;
}

/* MOBILE */

@media (max-width: 600px) {

    .booking-popup {
        padding: 25px 18px;
    }

    .booking-popup h2 {
        font-size: 22px;
    }

}

</style>

</head>

<body>

<!-- HEADER -->

<div class="top-header">

    <a href="index.php" class="top-logo">
        <img
            src="pictures/ras.png"
            alt="RAS Logo"
        >
    </a>

    <h2>RAS VIDEO GUEST BOOK</h2>

</div>

<!-- NAVIGATION -->

<nav class="navbar">

    <div class="nav-links">

        <a href="index.php">HOME</a>

        <a href="aboutus.php">ABOUT US</a>

        <a href="events.php">SERVICES</a>

        <a href="contactus.php">CONTACT US</a>

        <a href="events.php" class="gold-text">
            BOOK NOW!
        </a>

        <?php if (isset($_SESSION['user_id'])): ?>

            <a href="my_bookings.php">
                MY BOOKINGS
            </a>

            <a href="profile.php">
                PROFILE
            </a>

            <?php if (
                ($_SESSION['role'] ?? '') === 'admin'
            ): ?>

                <a href="admin.php">
                    ADMIN
                </a>

            <?php endif; ?>

            <a href="logout.php">
                LOGOUT
            </a>

        <?php else: ?>

            <a href="account.php">
                LOGIN / REGISTER
            </a>

        <?php endif; ?>

    </div>

</nav>

<!-- BOOKING SECTION -->

<section class="section">

    <p class="small-title">
        RESERVE YOUR EVENT
    </p>

    <h2>Book Your Service</h2>

    <p class="description">

        Selected Service:

        <strong>
            <?php echo esc($event['event_name']); ?>
        </strong>

    </p>

    <?php if (!isset($_SESSION['user_id'])): ?>

        <p class="description">
            You can book as a guest.
            No account or registration is required.
        </p>

    <?php endif; ?>

    <!-- ERROR MESSAGE -->

    <?php if ($message !== ""): ?>

        <p class="booking-error">
            <?php echo esc($message); ?>
        </p>

    <?php endif; ?>

    <!-- BOOKING FORM -->

    <form
        method="POST"
        class="form-box booking-form"
    >

        <input
            type="hidden"
            name="csrf"
            value="<?php echo esc($_SESSION['booking_csrf']); ?>"
        >

        <h3>Customer Information</h3>

        <!-- NAME -->

        <label for="name">
            Full Name
        </label>

        <input
            type="text"
            id="name"
            name="name"
            value="<?php
                echo esc(
                    $_POST['name'] ??
                    ($_SESSION['name'] ?? '')
                );
            ?>"
            required
        >

        <!-- EMAIL -->

        <label for="email">
            Email
        </label>

        <input
            type="email"
            id="email"
            name="email"
            value="<?php
                echo esc(
                    $_POST['email'] ??
                    ($_SESSION['email'] ?? '')
                );
            ?>"
            required
        >

        <!-- PHONE -->

        <label for="phone">
            Phone Number
        </label>

        <input
            type="tel"
            id="phone"
            name="phone"
            value="<?php
                echo esc($_POST['phone'] ?? '');
            ?>"
            required
        >

        <h3>Event Information</h3>

        <!-- EVENT DATE -->

        <label for="event_date">
            Event Date
        </label>

        <input
            type="date"
            id="event_date"
            name="event_date"
            min="<?php echo date('Y-m-d'); ?>"
            value="<?php
                echo esc($_POST['event_date'] ?? '');
            ?>"
            required
        >

        <!-- EVENT TIME -->

        <label for="booking_time">
            Event Time
        </label>

        <input
            type="time"
            id="booking_time"
            name="booking_time"
            value="<?php
                echo esc($_POST['booking_time'] ?? '');
            ?>"
            required
        >

        <!-- EVENT LOCATION -->

        <label for="location">
            Event Location
        </label>

        <input
            type="text"
            id="location"
            name="location"
            placeholder="Enter event address"
            value="<?php
                echo esc($_POST['location'] ?? '');
            ?>"
            required
        >

        <!-- NOTES -->

        <label for="notes">
            Special Requests
        </label>

        <textarea
            id="notes"
            name="notes"
            rows="4"
        ><?php
            echo esc($_POST['notes'] ?? '');
        ?></textarea>

        <!-- SUBMIT -->

        <button
            type="submit"
            name="book"
            class="button"
        >
            SUBMIT BOOKING
        </button>

    </form>

</section>

<!-- FOOTER -->

<footer class="footer">

    <h3>RAS Video Guest Book</h3>

    <p>
        Creating memories that last forever.
    </p>

    <p>
        &copy; <?php echo date('Y'); ?>
        RAS Video Guest Book
    </p>

</footer>

<!-- BOOKING CONFIRMATION POPUP -->

<?php if ($showPopup && $bookingDetails): ?>

<div
    class="booking-popup-overlay"
    id="bookingPopup"
>

    <div
        class="booking-popup"
        role="dialog"
        aria-modal="true"
        aria-labelledby="bookingPopupTitle"
    >

        <div class="booking-success-icon">
            &#10003;
        </div>

        <h2 id="bookingPopupTitle">
            Booking Submitted!
        </h2>

        <p>
            Thank you for booking with
            <strong>RAS Video Guest Book</strong>.
        </p>

        <p>
            Your booking request has been
            submitted successfully.
        </p>

        <!-- BOOKING DETAILS -->

        <div class="booking-popup-details">

            <p>
                <strong>Booking Number:</strong>
                <?php
                    echo (int)$bookingDetails['booking_id'];
                ?>
            </p>

            <p>
                <strong>Customer:</strong>
                <?php
                    echo esc($bookingDetails['customer_name']);
                ?>
            </p>

            <p>
                <strong>Service:</strong>
                <?php
                    echo esc($bookingDetails['event_name']);
                ?>
            </p>

            <p>
                <strong>Event Date:</strong>
                <?php
                    echo esc($bookingDetails['event_date']);
                ?>
            </p>

            <p>
                <strong>Event Time:</strong>
                <?php
                    echo esc($bookingDetails['booking_time']);
                ?>
            </p>

            <p>
                <strong>Status:</strong>
                Pending Confirmation
            </p>

        </div>

        <p>
            Please keep your booking number
            for future reference.
        </p>

        <p>
            Your booking details have been recorded.
            Email confirmation is not available yet.
        </p>

        <!-- SIMPLE OK BUTTON -->

        <a
            href="index.php"
            class="booking-popup-button"
        >
            OK
        </a>

    </div>

</div>

<?php endif; ?>

</body>
</html>

