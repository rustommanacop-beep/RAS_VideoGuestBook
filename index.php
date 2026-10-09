<<<<<<< Updated upstream

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

    <style>
        .home-contact {
            background: #f7f2e9;
            text-align: center;
            padding: 70px 20px;
        }

        .home-contact h2 {
            color: #604126;
            font-size: 32px;
            margin-bottom: 15px;
        }

        .home-contact p {
            max-width: 650px;
            margin: 0 auto 25px;
            color: #66594d;
            line-height: 1.8;
        }

        .home-contact .contact-link {
            display: inline-block;
            padding: 12px 25px;
            background: #eeeeee;
            color: #222222;
            border: 1px solid #aaaaaa;
            border-radius: 4px;
            text-decoration: none;
            font-size: 14px;
        }

        .home-contact .contact-link:hover {
            background: #dddddd;
        }
    </style>
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

<!-- Contact Us Section -->
<section class="home-contact">
    <p class="small-title">GET IN TOUCH</p>
    <h2>Contact Us</h2>

    <p>
        Have questions about our video guest book or mirror
        photobooth services? Contact us for more information,
        booking enquiries, or assistance with your upcoming event.
    </p>

    <a href="contactus.php" class="contact-link">
        CONTACT US
    </a>
</section>

<!-- Footer -->
<footer class="footer">
    <h3>RAS Video Guest Book</h3>
    <p>Creating memories that last forever.</p>
    <p>&copy; <?php echo date('Y'); ?> RAS Video Guest Book</p>
</footer>

</body>
</html>
=======

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

    <style>
        .home-contact {
            background: #f7f2e9;
            text-align: center;
            padding: 70px 20px;
        }

        .home-contact h2 {
            color: #604126;
            font-size: 32px;
            margin-bottom: 15px;
        }

        .home-contact p {
            max-width: 650px;
            margin: 0 auto 25px;
            color: #66594d;
            line-height: 1.8;
        }

        .home-contact .contact-link {
            display: inline-block;
            padding: 12px 25px;
            background: #eeeeee;
            color: #222222;
            border: 1px solid #aaaaaa;
            border-radius: 4px;
            text-decoration: none;
            font-size: 14px;
        }

        .home-contact .contact-link:hover {
            background: #dddddd;
        }
    </style>
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

<!-- Contact Us Section -->
<section class="home-contact">
    <p class="small-title">GET IN TOUCH</p>
    <h2>Contact Us</h2>

    <p>
        Have questions about our video guest book or mirror
        photobooth services? Contact us for more information,
        booking enquiries, or assistance with your upcoming event.
    </p>

    <a href="contactus.php" class="contact-link">
        CONTACT US
    </a>
</section>

<!-- Footer -->
<footer class="footer">
    <h3>RAS Video Guest Book</h3>
    <p>Creating memories that last forever.</p>
    <p>&copy; <?php echo date('Y'); ?> RAS Video Guest Book</p>
</footer>

</body>
</html>
>>>>>>> Stashed changes
