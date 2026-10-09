<<<<<<< Updated upstream

<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: account.php");
    exit;
}

include("database/database.php");

$id = $_SESSION['user_id'];

$stmt = $conn->prepare(
    "SELECT name,email,created_at
     FROM users WHERE user_id=?"
);
$stmt->bind_param("i", $id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - RAS</title>
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
        <a href="my_bookings.php">MY BOOKINGS</a>
        <a href="profile.php">PROFILE</a>
        <?php if (($_SESSION['role'] ?? '') == 'admin') { ?>
            <a href="admin.php">ADMIN</a>
        <?php } ?>
        <a href="logout.php">LOGOUT</a>
    </div>
</nav>

<section class="section">
    <p class="small-title">MY ACCOUNT</p>
    <h2>My Profile</h2>

    <div class="card profile-card">
        <h3>
            <?php echo htmlspecialchars($user['name']); ?>
        </h3>

        <p>
            Email:
            <?php echo htmlspecialchars($user['email']); ?>
        </p>

        <p>
            Member Since:
            <?php echo htmlspecialchars($user['created_at']); ?>
        </p>

        <a href="my_bookings.php" class="button">
            VIEW MY BOOKINGS
        </a>
    </div>
</section>

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

if (!isset($_SESSION['user_id'])) {
    header("Location: account.php");
    exit;
}

include("database/database.php");

$id = $_SESSION['user_id'];

$stmt = $conn->prepare(
    "SELECT name,email,created_at
     FROM users WHERE user_id=?"
);
$stmt->bind_param("i", $id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - RAS</title>
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
        <a href="my_bookings.php">MY BOOKINGS</a>
        <a href="profile.php">PROFILE</a>
        <?php if (($_SESSION['role'] ?? '') == 'admin') { ?>
            <a href="admin.php">ADMIN</a>
        <?php } ?>
        <a href="logout.php">LOGOUT</a>
    </div>
</nav>

<section class="section">
    <p class="small-title">MY ACCOUNT</p>
    <h2>My Profile</h2>

    <div class="card profile-card">
        <h3>
            <?php echo htmlspecialchars($user['name']); ?>
        </h3>

        <p>
            Email:
            <?php echo htmlspecialchars($user['email']); ?>
        </p>

        <p>
            Member Since:
            <?php echo htmlspecialchars($user['created_at']); ?>
        </p>

        <a href="my_bookings.php" class="button">
            VIEW MY BOOKINGS
        </a>
    </div>
</section>

<footer class="footer">
    <h3>RAS Video Guest Book</h3>
    <p>Creating memories that last forever.</p>
    <p>&copy; <?php echo date('Y'); ?> RAS Video Guest Book</p>
</footer>

</body>
</html>
>>>>>>> Stashed changes
