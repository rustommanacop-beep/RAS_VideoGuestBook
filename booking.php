
<?php
session_start();
include("database/database.php");

$id = (int)($_GET['id'] ?? 0);
$message = "";

$stmt = $conn->prepare(
    "SELECT * FROM events WHERE event_id=?"
);
$stmt->bind_param("i", $id);
$stmt->execute();
$event = $stmt->get_result()->fetch_assoc();

if (!$event) {
    header("Location: events.php");
    exit;
}

if (isset($_POST['book'])) {
    $user_id = $_SESSION['user_id'] ?? null;
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $date = $_POST['event_date'];
    $time = $_POST['booking_time'];
    $location = trim($_POST['location']);
    $notes = trim($_POST['notes']);

    $valid_date = DateTime::createFromFormat('!Y-m-d', $date);
    $valid_time = DateTime::createFromFormat('!H:i', $time);

    if (
        $name == "" ||
        !filter_var($email, FILTER_VALIDATE_EMAIL) ||
        $phone == "" ||
        $location == "" ||
        !$valid_date ||
        $valid_date->format('Y-m-d') != $date ||
        $date < date('Y-m-d') ||
        !$valid_time ||
        $valid_time->format('H:i') != $time
    ) {
        $message = "Please complete the booking details correctly.";
    } else {
        $check = $conn->prepare(
            "SELECT booking_id FROM bookings
             WHERE event_id=? AND event_date=?
             AND booking_time=? AND status!='Cancelled'"
        );

        $check->bind_param("iss", $id, $date, $time);
        $check->execute();
        $check->store_result();

        if ($check->num_rows > 0) {
            $message = "This service is already booked at that time.";
        } else {
            $code = bin2hex(random_bytes(32));

            $stmt = $conn->prepare(
                "INSERT INTO bookings
                 (user_id,event_id,customer_name,customer_email,
                  phone,event_date,booking_time,location,notes,
                  status,booking_code)
                 VALUES (?,?,?,?,?,?,?,?,?,'Pending',?)"
            );

            $stmt->bind_param(
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

            if ($stmt->execute()) {
                header(
                    "Location: my_bookings.php?code=" . urlencode($code)
                );
                exit;
            }

            $message = "Unable to save booking.";
        }
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
</head>
<body>

<div class="top-header">
    <a href="index.php" class="top-logo">
        <img src="pictures/ras.png" alt="RAS Logo">
    </a>
    <h2>RAS VIDEO GUEST BOOK</h2>
</div>

<nav class="navbar">
    <div class="nav-links">
        <a href="index.php">HOME</a>
        <a href="aboutus.php">ABOUT US</a>
        <a href="events.php">SERVICES</a>
        <a href="events.php" class="gold-text">BOOK NOW!</a>
        <?php if (isset($_SESSION['user_id'])) { ?>
            <a href="my_bookings.php">MY BOOKINGS</a>
            <a href="profile.php">PROFILE</a>
            <?php if (($_SESSION['role'] ?? '') == 'admin') { ?>
                <a href="admin.php">ADMIN</a>
            <?php } ?>
            <a href="logout.php">LOGOUT</a>
        <?php } else { ?>
            <a href="account.php">LOGIN / REGISTER</a>
        <?php } ?>
    </div>
</nav>

<section class="section">
    <p class="small-title">RESERVE YOUR EVENT</p>
    <h2>Book Your Service</h2>

    <p class="description">
        Selected Service:
        <strong>
            <?php echo htmlspecialchars($event['event_name']); ?>
        </strong>
    </p>

    <p class="message">
        <?php echo htmlspecialchars($message); ?>
    </p>

    <form method="POST" class="form-box booking-form">

        <h3>Customer Information</h3>

        <label>Full Name</label>
        <input type="text" name="name"
               value="<?php echo htmlspecialchars(
                   $_POST['name'] ?? ($_SESSION['name'] ?? '')
               ); ?>" required>

        <label>Email</label>
        <input type="email" name="email"
               value="<?php echo htmlspecialchars(
                   $_POST['email'] ?? ($_SESSION['email'] ?? '')
               ); ?>" required>

        <label>Phone Number</label>
        <input type="tel" name="phone" required>

        <h3>Event Information</h3>

        <label>Event Date</label>
        <input type="date" name="event_date"
               min="<?php echo date('Y-m-d'); ?>" required>

        <label>Event Time</label>
        <input type="time" name="booking_time" required>

        <label>Event Location</label>
        <input type="text" name="location"
               placeholder="Enter event address" required>

        <label>Special Requests</label>
        <textarea name="notes" rows="4"></textarea>

        <button type="submit" name="book" class="button">
            SUBMIT BOOKING
        </button>

    </form>
</section>

<footer class="footer">
    <h3>RAS Video Guest Book</h3>
    <p>Creating memories that last forever.</p>
    <p>&copy; <?php echo date('Y'); ?> RAS Video Guest Book</p>
</footer>

</body>
</html>
