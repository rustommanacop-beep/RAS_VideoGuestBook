
<?php
session_start();
include("database/database.php");

// Get all services from database
$result = mysqli_query(
    $conn,
    "SELECT * FROM events ORDER BY event_id"
);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Our Services - RAS Video Guest Book</title>

    <link rel="icon"
          type="image/png"
          href="pictures/ras.png">

    <link rel="stylesheet" href="css/style.css">
</head>

<body>

<!-- =====================================
     LOGO AND WEBSITE TITLE
     ===================================== -->

<div class="top-header">

    <a href="index.php" class="top-logo">

        <img
            src="pictures/ras.png"
            alt="RAS Video Guest Book Logo"
        >

    </a>

    <h2>RAS VIDEO GUEST BOOK</h2>

</div>


<!-- =====================================
     NAVIGATION BAR
     ===================================== -->

<nav class="navbar">

    <div class="nav-links">

        <a href="index.php">HOME</a>

        <a href="aboutus.php">ABOUT US</a>

        <a href="events.php">SERVICES</a>

        <a href="contactus.php">CONTACT US</a>


        <?php if (isset($_SESSION['user_id'])) { ?>

            <a href="my_bookings.php">
                MY BOOKINGS
            </a>

            <a href="profile.php">
                PROFILE
            </a>

            <?php if (
                ($_SESSION['role'] ?? '') == 'admin'
            ) { ?>

                <a href="admin.php">
                    ADMIN
                </a>

            <?php } ?>

            <a href="logout.php">
                LOGOUT
            </a>

        <?php } else { ?>

            <a href="account.php">
                LOGIN / REGISTER
            </a>

        <?php } ?>

    </div>

</nav>


<!-- =====================================
     PAGE BANNER
     ===================================== -->

<section class="page-banner">

    <p class="small-title">
        OUR COLLECTION
    </p>

    <h1>Our Services</h1>

    <p>
        Choose the perfect service for your event.
    </p>

</section>


<!-- =====================================
     SERVICES SECTION
     ===================================== -->

<section class="section">

    <div class="cards">

        <?php if (
            $result &&
            mysqli_num_rows($result) > 0
        ) { ?>

            <?php while (
                $event = mysqli_fetch_assoc($result)
            ) { ?>

                <?php

                // Get the service name
                $service = strtolower(
                    trim($event['event_name'] ?? '')
                );

                // =================================
                // DIFFERENT IMAGE FOR EACH SERVICE
                // =================================

                if (
                    strpos($service, 'vintage') !== false
                ) {

                    $image = "vintage.jpg";

                } elseif (
                    strpos($service, 'all-in-one') !== false ||
                    strpos($service, 'all in one') !== false ||
                    strpos($service, 'digital') !== false
                ) {

                    // Correct All-in-one image
                    $image = "allin.png";

                } elseif (
                    strpos($service, 'mirror') !== false
                ) {

                    $image = "mirror.jpg";

                } elseif (
                    strpos($service, 'beige') !== false
                ) {

                    $image = "beige.jpg";

                } else {

                    // Use image saved in database
                    $image = basename(
                        $event['image'] ?? ''
                    );

                }

                // =================================
                // DEFAULT IMAGE IF FILE IS MISSING
                // =================================

                if (
                    $image == "" ||
                    !is_file(
                        __DIR__ . "/pictures/" . $image
                    )
                ) {

                    $image = "center.jpg";

                }


                // =================================
                // SERVICE PRICE
                // =================================

                if (
                    strpos($service, 'vintage') !== false
                ) {

                    $price = "$570 - $950";

                } elseif (
                    strpos($service, 'all-in-one') !== false ||
                    strpos($service, 'all in one') !== false ||
                    strpos($service, 'digital') !== false
                ) {

                    $price = "$1,350 - $1,750";

                } elseif (
                    strpos($service, 'mirror') !== false
                ) {

                    $price = "$950 - $1,550";

                } elseif (
                    strpos($service, 'beige') !== false
                ) {

                    $price = "$495 - $850";

                } else {

                    $price = "Contact Us for Pricing";

                }

                ?>


                <!-- =================================
                     SERVICE CARD
                     ================================= -->

                <div class="card service-card">

                    <!-- Service Image -->

                    <img
                        src="pictures/<?php
                            echo htmlspecialchars(
                                $image,
                                ENT_QUOTES,
                                'UTF-8'
                            );
                        ?>"
                        alt="<?php
                            echo htmlspecialchars(
                                $event['event_name'] ?? '',
                                ENT_QUOTES,
                                'UTF-8'
                            );
                        ?>"
                    >


                    <!-- Service Name -->

                    <h3>
                        <?php
                        echo htmlspecialchars(
                            $event['event_name'] ?? '',
                            ENT_QUOTES,
                            'UTF-8'
                        );
                        ?>
                    </h3>


                    <!-- Service Description -->

                    <p>
                        <?php
                        echo nl2br(
                            htmlspecialchars(
                                $event['description'] ?? '',
                                ENT_QUOTES,
                                'UTF-8'
                            )
                        );
                        ?>
                    </p>


                    <!-- Service Price -->

                    <h3 class="price">
                        <?php
                        echo htmlspecialchars(
                            $price,
                            ENT_QUOTES,
                            'UTF-8'
                        );
                        ?>
                    </h3>


                    <!-- Booking Button -->

                    <a
                        href="booking.php?id=<?php
                            echo (int)$event['event_id'];
                        ?>"
                        class="button"
                    >
                        BOOK NOW
                    </a>

                </div>

            <?php } ?>

        <?php } else { ?>

            <p>
                No services available at the moment.
            </p>

        <?php } ?>

    </div>

</section>


<!-- =====================================
     FOOTER
     ===================================== -->

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

</body>
</html>
