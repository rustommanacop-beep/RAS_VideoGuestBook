<<<<<<< Updated upstream
<<<<<<< Updated upstream

<?php
session_start();

/* CHECK SUCCESSFUL BOOKING */

$booking = $_SESSION['booking_popup'] ?? null;

if (!$booking) {
    header("Location: events.php");
    exit;
}

/* BOOKING INFORMATION */

$bookingNumber = (int)$booking['booking_id'];
$emailSent = !empty($booking['email_sent']);

/* SHOW POPUP ONLY ONCE */

unset($_SESSION['booking_popup']);
?>

<!DOCTYPE html>
<html lang="en">

<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Booking Confirmation - RAS Video Guest Book</title>

<link rel="icon" href="pictures/ras.png">
<link rel="stylesheet" href="css/style.css">

<style>

/* PAGE BACKGROUND */

body {
    margin: 0;
    font-family: Arial, sans-serif;
    background: #f7f2e9;
}

/* POPUP BACKGROUND */

.popup-overlay {
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, 0.55);
    display: flex;
    justify-content: center;
    align-items: center;
    padding: 20px;
    z-index: 9999;
}

/* POPUP BOX */

.popup-box {
    background: white;
    width: 100%;
    max-width: 430px;
    padding: 35px 25px;
    border-radius: 12px;
    text-align: center;
    box-shadow: 0 8px 30px rgba(0,0,0,0.15);
}

/* SUCCESS SYMBOL */

.success-icon {
    font-size: 55px;
    color: #4b8a57;
    margin-bottom: 15px;
}

/* HEADING */

.popup-box h2 {
    color: #604126;
    font-size: 25px;
    margin: 0 0 15px;
}

/* DESCRIPTION */

.popup-box p {
    font-size: 14px;
    color: #555555;
    line-height: 1.7;
}

/* BOOKING NUMBER */

.booking-number {
    background: #f7f2e9;
    padding: 15px;
    border-radius: 6px;
    margin: 20px 0;
}

.booking-number strong {
    font-size: 22px;
    color: #604126;
}

/* SIMPLE BUTTON */

.popup-button {
    display: inline-block;
    margin-top: 15px;
    padding: 11px 25px;
    background: #eeeeee;
    color: #222222;
    border: 1px solid #aaaaaa;
    border-radius: 4px;
    text-decoration: none;
    font-size: 14px;
    cursor: pointer;
}

.popup-button:hover {
    background: #dddddd;
}

</style>

</head>

<body>

<!-- BOOKING SUCCESS POPUP -->

<div class="popup-overlay">

    <div class="popup-box" role="dialog"
         aria-modal="true"
         aria-labelledby="popup-title">

        <div class="success-icon">
            &#10003;
        </div>

        <h2 id="popup-title">
            Booking Submitted!
        </h2>

        <p>
            Thank you for choosing
            <strong>RAS Video Guest Book</strong>.
            Your booking request has been received.
        </p>

        <div class="booking-number">

            <p>Your Booking Number</p>

            <strong>
                <?php echo $bookingNumber; ?>
            </strong>

        </div>

        <p>
            Booking Status:
            <strong>Pending Confirmation</strong>
        </p>

        <?php if ($emailSent): ?>

            <p>
                A confirmation email has been sent
                to your booking email address.
            </p>

        <?php else: ?>

            <p>
                Your booking was saved successfully.
                Email confirmation is not available
                at this time.
            </p>

        <?php endif; ?>

        <a href="index.php" class="popup-button">
            OK
        </a>

    </div>

</div>

</body>
</html>
=======
=======
>>>>>>> Stashed changes

<?php
session_start();

/* CHECK SUCCESSFUL BOOKING */

$booking = $_SESSION['booking_popup'] ?? null;

if (!$booking) {
    header("Location: events.php");
    exit;
}

/* BOOKING INFORMATION */

$bookingNumber = (int)$booking['booking_id'];
$emailSent = !empty($booking['email_sent']);

/* SHOW POPUP ONLY ONCE */

unset($_SESSION['booking_popup']);
?>

<!DOCTYPE html>
<html lang="en">

<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Booking Confirmation - RAS Video Guest Book</title>

<link rel="icon" href="pictures/ras.png">
<link rel="stylesheet" href="css/style.css">

<style>

/* PAGE BACKGROUND */

body {
    margin: 0;
    font-family: Arial, sans-serif;
    background: #f7f2e9;
}

/* POPUP BACKGROUND */

.popup-overlay {
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, 0.55);
    display: flex;
    justify-content: center;
    align-items: center;
    padding: 20px;
    z-index: 9999;
}

/* POPUP BOX */

.popup-box {
    background: white;
    width: 100%;
    max-width: 430px;
    padding: 35px 25px;
    border-radius: 12px;
    text-align: center;
    box-shadow: 0 8px 30px rgba(0,0,0,0.15);
}

/* SUCCESS SYMBOL */

.success-icon {
    font-size: 55px;
    color: #4b8a57;
    margin-bottom: 15px;
}

/* HEADING */

.popup-box h2 {
    color: #604126;
    font-size: 25px;
    margin: 0 0 15px;
}

/* DESCRIPTION */

.popup-box p {
    font-size: 14px;
    color: #555555;
    line-height: 1.7;
}

/* BOOKING NUMBER */

.booking-number {
    background: #f7f2e9;
    padding: 15px;
    border-radius: 6px;
    margin: 20px 0;
}

.booking-number strong {
    font-size: 22px;
    color: #604126;
}

/* SIMPLE BUTTON */

.popup-button {
    display: inline-block;
    margin-top: 15px;
    padding: 11px 25px;
    background: #eeeeee;
    color: #222222;
    border: 1px solid #aaaaaa;
    border-radius: 4px;
    text-decoration: none;
    font-size: 14px;
    cursor: pointer;
}

.popup-button:hover {
    background: #dddddd;
}

</style>

</head>

<body>

<!-- BOOKING SUCCESS POPUP -->

<div class="popup-overlay">

    <div class="popup-box" role="dialog"
         aria-modal="true"
         aria-labelledby="popup-title">

        <div class="success-icon">
            &#10003;
        </div>

        <h2 id="popup-title">
            Booking Submitted!
        </h2>

        <p>
            Thank you for choosing
            <strong>RAS Video Guest Book</strong>.
            Your booking request has been received.
        </p>

        <div class="booking-number">

            <p>Your Booking Number</p>

            <strong>
                <?php echo $bookingNumber; ?>
            </strong>

        </div>

        <p>
            Booking Status:
            <strong>Pending Confirmation</strong>
        </p>

        <?php if ($emailSent): ?>

            <p>
                A confirmation email has been sent
                to your booking email address.
            </p>

        <?php else: ?>

            <p>
                Your booking was saved successfully.
                Email confirmation is not available
                at this time.
            </p>

        <?php endif; ?>

        <a href="index.php" class="popup-button">
            OK
        </a>

    </div>

</div>

</body>
</html>
<<<<<<< Updated upstream
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
