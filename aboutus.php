
<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About Us - RAS Video Guest Book</title>
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
        <a href="contactus.php">CONTACT US</a>

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

<section class="page-banner">
    <p class="small-title">WELCOME TO RAS</p>
    <h1>About Us</h1>
    <p>Creating memories that last forever.</p>
</section>

<section class="section">
    <p class="small-title">OUR STORY</p>
    <h2>Who We Are</h2>

    <p class="description">
        RAS Video Guest Book is an event service concept
        focused on helping people capture meaningful moments
        through video guestbooks and photo booth experiences.
    </p>

    <p class="description">
        Instead of relying only on traditional written
        messages, guests can record personal greetings,
        share stories and express their wishes through video.
        These recordings can become special memories
        for families, couples and friends.
    </p>
</section>

<section class="section about-preview">
    <p class="small-title">WHAT WE OFFER</p>
    <h2>Our Services</h2>

    <div class="cards">
        <div class="card">
            <h3>Video Guestbook</h3>
            <p>
                Allow guests to record heartfelt video messages
                during weddings, birthdays and celebrations.
            </p>
        </div>

        <div class="card">
            <h3>Digital Guestbook</h3>
            <p>
                A modern way to collect and preserve personal
                greetings from family and friends.
            </p>
        </div>

        <div class="card">
            <h3>Mirror Photo Booth</h3>
            <p>
                An interactive photo booth experience that
                adds entertainment to special occasions.
            </p>
        </div>
    </div>
</section>

<section class="section">
    <p class="small-title">OUR PURPOSE</p>
    <h2>Our Mission</h2>

    <p class="description">
        Our mission is to make it easier for customers
        to capture meaningful messages and celebrate
        important life events through memorable experiences.
    </p>

    <a href="events.php" class="button">EXPLORE OUR SERVICES</a>
</section>

<footer class="footer">
    <h3>RAS Video Guest Book</h3>
    <p>Creating memories that last forever.</p>
    <p>&copy; <?php echo date('Y'); ?> RAS Video Guest Book</p>
</footer>

</body>
</html>
