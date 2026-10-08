
<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RAS Video Guest Book - Home</title>
    <link rel="icon" href="pictures/ras.png">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

<!-- Logo and Website Name -->
<div class="top-header">
    <a href="index.php" class="top-logo">
        <img src="pictures/ras.png" alt="RAS Video Guest Book Logo">
    </a>
    <h2>RAS VIDEO GUEST BOOK</h2>
</div>

<!-- Navigation -->
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

<!-- Hero Section -->
<section class="hero">
    <div class="hero-content">
        <div class="line"></div>

        <h2>
            <em>Mirror Photobooth</em> &amp;
            <br>
            <em>Video Guestbook</em> FOR ANY EVENTS
        </h2>

        <a href="events.php" class="button">BOOK HERE!</a>
    </div>
</section>

<!-- Services Introduction -->
<section class="section">
    <p class="small-title">ABOUT OUR SERVICES</p>
    <h2>Capture Your Special Moments</h2>

    <p class="description">
        RAS Video Guest Book provides video guestbook
        and photobooth services for weddings, birthdays,
        anniversaries, graduations and other celebrations.
    </p>

    <div class="cards">
        <div class="card">
            <h3>01. Choose Service</h3>
            <p>Select a video guestbook or mirror photobooth.</p>
        </div>

        <div class="card">
            <h3>02. Enter Details</h3>
            <p>Choose your event date, time and location.</p>
        </div>

        <div class="card">
            <h3>03. Confirm Booking</h3>
            <p>Submit your booking request.</p>
        </div>
    </div>

    <a href="events.php" class="button">VIEW SERVICES</a>
</section>

<!-- About Preview -->
<section class="section about-preview">
    <p class="small-title">GET TO KNOW US</p>
    <h2>About RAS Video Guest Book</h2>

    <p class="description">
        We help make celebrations more memorable through
        video messages, personal greetings and photo booth
        experiences that guests can enjoy.
    </p>

    <a href="aboutus.php" class="button">READ MORE</a>
</section>

<footer class="footer">
    <h3>RAS Video Guest Book</h3>
    <p>Creating memories that last forever.</p>
    <p>&copy; <?php echo date('Y'); ?> RAS Video Guest Book</p>
</footer>

</body>
</html>
