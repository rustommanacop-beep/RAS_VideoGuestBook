
<?php
session_start();

require_once "database/database.php";

// Security function
function esc($value) {
    return htmlspecialchars(
        (string)($value ?? ''),
        ENT_QUOTES,
        'UTF-8'
    );
}

// Create CSRF token
if (empty($_SESSION['contact_csrf'])) {
    $_SESSION['contact_csrf'] = bin2hex(random_bytes(32));
}

// Form variables
$success = "";
$error = "";

$name = "";
$email = "";
$subject = "";
$message = "";

// Process contact form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $token = $_POST['csrf'] ?? '';

    if (
        !is_string($token) ||
        !hash_equals($_SESSION['contact_csrf'], $token)
    ) {
        http_response_code(403);
        exit("Invalid request.");
    }

    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $subject = trim($_POST['subject'] ?? '');
    $message = trim($_POST['message'] ?? '');

    // Validate form
    if (
        $name === '' ||
        !filter_var($email, FILTER_VALIDATE_EMAIL) ||
        $subject === '' ||
        $message === ''
    ) {

        $error = "Please complete all fields correctly.";

    } elseif (
        strlen($name) > 150 ||
        strlen($email) > 255 ||
        strlen($subject) > 255 ||
        strlen($message) > 5000
    ) {

        $error = "One or more fields are too long.";

    } else {

        // Save message to database
        $stmt = $conn->prepare("
            INSERT INTO contact_messages
                (name, email, subject, message)
            VALUES (?, ?, ?, ?)
        ");

        if (!$stmt) {

            $error = "Unable to process your message.";

        } else {

            $stmt->bind_param(
                "ssss",
                $name,
                $email,
                $subject,
                $message
            );

            if ($stmt->execute()) {

                $success = "Your message has been submitted successfully!";

                $name = "";
                $email = "";
                $subject = "";
                $message = "";

                // Generate a new CSRF token
                $_SESSION['contact_csrf'] = bin2hex(random_bytes(32));

            } else {

                $error = "Unable to save your message. Please try again.";

            }

            $stmt->close();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Contact Us - RAS Video Guest Book</title>

    <link rel="icon" type="image/png" href="pictures/ras.png">
    <link rel="stylesheet" href="css/style.css">

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: Arial, sans-serif;
        }

        /* CONTACT SECTION */

        .contact-section {
            background: #f7f2e9;
            min-height: 70vh;
            padding: 65px 20px;
        }

        .contact-container {
            max-width: 750px;
            margin: 0 auto;
            background: #ffffff;
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 4px 18px rgba(0, 0, 0, 0.06);
        }

        /* HEADING */

        .contact-container h1 {
            text-align: center;
            color: #604126;
            font-size: 32px;
            margin: 0 0 12px;
        }

        .contact-description {
            text-align: center;
            color: #777777;
            line-height: 1.8;
            margin-bottom: 30px;
            font-size: 14px;
        }

        /* CONTACT INFORMATION */

        .contact-info {
            text-align: center;
            padding: 25px;
            margin-bottom: 30px;
            background: #f7f2e9;
            border: 1px solid #e5ddd1;
            border-radius: 8px;
        }

        .contact-info h3 {
            color: #604126;
            font-size: 19px;
            margin: 0 0 10px;
        }

        .contact-number {
            margin: 0 0 12px;
        }

        .contact-number a {
            color: #2b2520;
            font-size: 21px;
            font-weight: bold;
            text-decoration: none;
        }

        .contact-number a:hover {
            text-decoration: underline;
        }

        .contact-info p {
            color: #66594d;
        }

        /* FORM HEADING */

        .contact-form-title {
            color: #604126;
            font-size: 23px;
            margin: 0 0 20px;
            text-align: center;
        }

        /* CONTACT FORM */

        .contact-form {
            display: grid;
            gap: 18px;
        }

        .contact-group {
            display: flex;
            flex-direction: column;
            gap: 7px;
        }

        .contact-group label {
            color: #604126;
            font-size: 14px;
            font-weight: bold;
        }

        .contact-group input,
        .contact-group textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid #cccccc;
            border-radius: 5px;
            background: #ffffff;
            font-family: Arial, sans-serif;
            font-size: 14px;
            color: #222222;
        }

        .contact-group input:focus,
        .contact-group textarea:focus {
            border-color: #888888;
            outline: none;
        }

        .contact-group textarea {
            min-height: 150px;
            resize: vertical;
        }

        /* SUBMIT BUTTON */

        .contact-button {
            display: inline-block;
            width: auto;
            padding: 11px 20px;
            background: #eeeeee;
            color: #222222;
            border: 1px solid #aaaaaa;
            border-radius: 4px;
            font-family: Arial, sans-serif;
            font-size: 14px;
            font-weight: normal;
            cursor: pointer;
        }

        .contact-button:hover {
            background: #dddddd;
        }

        /* NOTIFICATIONS */

        .contact-alert {
            padding: 14px;
            border: 1px solid #cccccc;
            border-radius: 5px;
            margin-bottom: 20px;
            font-size: 14px;
        }

        .contact-success {
            background: #edf7ed;
            color: #286b35;
        }

        .contact-error {
            background: #fff0f0;
            color: #a33a3a;
        }

        /* RESPONSIVE DESIGN */

        @media (max-width: 600px) {
            .contact-section {
                padding: 30px 12px;
            }

            .contact-container {
                padding: 22px;
            }

            .contact-container h1 {
                font-size: 27px;
            }

            .contact-info {
                padding: 18px;
            }

            .contact-number a {
                font-size: 19px;
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
            alt="RAS Video Guest Book Logo"
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

        <?php if (isset($_SESSION['user_id'])) { ?>

            <a href="my_bookings.php">MY BOOKINGS</a>
            <a href="profile.php">PROFILE</a>

            <?php if (($_SESSION['role'] ?? '') === 'admin') { ?>
                <a href="admin.php">ADMIN</a>
            <?php } ?>

            <a href="logout.php">LOGOUT</a>

        <?php } else { ?>

            <a href="account.php">LOGIN / REGISTER</a>

        <?php } ?>

    </div>

</nav>


<!-- CONTACT SECTION -->

<section class="contact-section">

    <div class="contact-container">

        <h1>Contact Us</h1>

        <p class="contact-description">
            Have questions about our video guest book or
            mirror photobooth services? Contact us for
            booking enquiries, event information, or
            assistance with your upcoming celebration.
        </p>


        <!-- CONTACT NUMBER -->

        <div class="contact-info">

            <h3>Contact Number</h3>

            <p class="contact-number">
                <a href="tel:0987612345">0987612345</a>
            </p>

            <p>Call us for booking enquiries and assistance.</p>

        </div>


        <!-- CONTACT FORM HEADING -->

        <h2 class="contact-form-title">Send Us a Message</h2>


        <!-- SUCCESS MESSAGE -->

        <?php if ($success !== '') { ?>

            <div class="contact-alert contact-success">
                <?php echo esc($success); ?>
            </div>

        <?php } ?>


        <!-- ERROR MESSAGE -->

        <?php if ($error !== '') { ?>

            <div class="contact-alert contact-error">
                <?php echo esc($error); ?>
            </div>

        <?php } ?>


        <!-- CONTACT FORM -->

        <form method="POST" action="contactus.php" class="contact-form">

            <input
                type="hidden"
                name="csrf"
                value="<?php echo esc($_SESSION['contact_csrf']); ?>"
            >

            <!-- FULL NAME -->

            <div class="contact-group">

                <label for="name">Full Name</label>

                <input
                    type="text"
                    id="name"
                    name="name"
                    maxlength="150"
                    value="<?php echo esc($name); ?>"
                    placeholder="Enter your full name"
                    required
                >

            </div>


            <!-- EMAIL -->

            <div class="contact-group">

                <label for="email">Email Address</label>

                <input
                    type="email"
                    id="email"
                    name="email"
                    maxlength="255"
                    value="<?php echo esc($email); ?>"
                    placeholder="Enter your email address"
                    required
                >

            </div>


            <!-- SUBJECT -->

            <div class="contact-group">

                <label for="subject">Subject</label>

                <input
                    type="text"
                    id="subject"
                    name="subject"
                    maxlength="255"
                    value="<?php echo esc($subject); ?>"
                    placeholder="Enter message subject"
                    required
                >

            </div>


            <!-- MESSAGE -->

            <div class="contact-group">

                <label for="message">Message</label>

                <textarea
                    id="message"
                    name="message"
                    maxlength="5000"
                    placeholder="Write your message here..."
                    required
                ><?php echo esc($message); ?></textarea>

            </div>


            <!-- SEND BUTTON -->

            <div>

                <button type="submit" class="contact-button">
                    Send Message
                </button>

            </div>

        </form>

    </div>

</section>


<!-- FOOTER -->

<footer class="footer">

    <h3>RAS Video Guest Book</h3>

    <p>Creating memories that last forever.</p>

    <p>
        Contact Number:
        <a href="tel:0987612345">0987612345</a>
    </p>

    <p>
        &copy; <?php echo date('Y'); ?>
        RAS Video Guest Book
    </p>

</footer>

</body>
</html>