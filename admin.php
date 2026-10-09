<<<<<<< Updated upstream

<?php
session_start();

if (!isset($_SESSION['user_id']) ||
    ($_SESSION['role'] ?? '') !== 'admin') {
    header("Location: account.php");
    exit;
}

require_once "database/database.php";

function esc($value) {
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}

$stats = [
    'total' => 0,
    'confirmed' => 0,
    'pending' => 0,
    'videos' => 0,
    'events' => 0
];

$result = $conn->query(
    "SELECT COUNT(*) AS total,
     COALESCE(SUM(status = 'Confirmed'), 0) AS confirmed,
     COALESCE(SUM(status = 'Pending'), 0) AS pending
     FROM bookings"
);

if ($result) {
    $data = $result->fetch_assoc();
    $stats['total'] = (int)$data['total'];
    $stats['confirmed'] = (int)$data['confirmed'];
    $stats['pending'] = (int)$data['pending'];
}

$result = $conn->query("SELECT COUNT(*) AS total FROM video_messages");
if ($result) {
    $stats['videos'] = (int)$result->fetch_assoc()['total'];
}

$result = $conn->query("SELECT COUNT(*) AS total FROM events");
if ($result) {
    $stats['events'] = (int)$result->fetch_assoc()['total'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Dashboard - RAS</title>
<link rel="icon" href="pictures/ras.png">
<link rel="stylesheet" href="css/style.css">

<style>
* { box-sizing: border-box; }

body.admin-page {
    margin: 0;
    background: #f7f2e9;
    font-family: Arial, sans-serif;
    color: #2b2520;
}

.admin-layout { display: flex; min-height: 100vh; }

.admin-sidebar {
    width: 245px;
    flex-shrink: 0;
    background: #211b17;
    color: white;
    padding: 30px 20px;
}

.admin-brand { text-align: center; margin-bottom: 40px; }
.admin-brand img { width: 85px; height: 85px; object-fit: contain; }
.admin-brand h3 { color: #c59445; margin: 12px 0 5px; }
.admin-brand p { font-size: 12px; color: #c8bbaa; }

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

.admin-topbar h1 { margin: 0; color: #604126; }
.admin-topbar p { color: #8b735c; }

.admin-user {
    background: white;
    padding: 12px 18px;
    border-radius: 8px;
    font-size: 14px;
}

.admin-stats {
    display: grid;
    grid-template-columns: repeat(5, minmax(0, 1fr));
    gap: 20px;
    margin-bottom: 35px;
}

.stat-card {
    background: white;
    padding: 25px;
    border-radius: 10px;
    border-left: 4px solid #c59445;
}

.stat-card h2 {
    color: #604126;
    font-size: 32px;
    margin: 0 0 10px;
}

.stat-card p { margin: 0; color: #777; }

.admin-panel {
    background: white;
    border-radius: 10px;
    padding: 25px;
    margin-bottom: 30px;
}

.admin-panel h2 { color: #604126; margin-top: 0; }

@media (max-width: 1100px) {
    .admin-stats { grid-template-columns: repeat(2, 1fr); }
}

@media (max-width: 700px) {
    .admin-layout { flex-direction: column; }
    .admin-sidebar { width: 100%; padding: 15px; }
    .admin-brand { margin-bottom: 15px; }
    .admin-brand img { width: 55px; height: 55px; }
    .admin-menu { flex-direction: row; flex-wrap: wrap; }
    .admin-menu a { padding: 10px; }
    .admin-main { padding: 20px 12px; }
    .admin-stats { gap: 10px; }
    .stat-card { padding: 15px; }
    .admin-panel { padding: 15px; }
}
</style>
</head>

<body class="admin-page">
<div class="admin-layout">

<aside class="admin-sidebar">
    <div class="admin-brand">
        <img src="pictures/ras.png" alt="RAS Logo">
        <h3>RAS ADMIN</h3>
        <p>Management Portal</p>
    </div>

    <nav class="admin-menu">
        <a href="admin.php" class="active">Dashboard</a>
        <a href="admin_bookings.php">Customer Bookings</a>
        <a href="admin_events.php">Manage Events</a>
        <a href="admin_videos.php">Video Messages</a>
        <a href="logout.php">Logout</a>
    </nav>
</aside>

<main class="admin-main">
    <div class="admin-topbar">
        <div>
            <h1>Admin Dashboard</h1>
            <p>Manage your RAS Video Guest Book reservations, events and videos.</p>
        </div>

        <div class="admin-user">
            Administrator:
            <strong><?php echo esc($_SESSION['name'] ?? 'Admin'); ?></strong>
        </div>
    </div>

    <div class="admin-stats">
        <div class="stat-card">
            <h2><?php echo $stats['total']; ?></h2>
            <p>Total Bookings</p>
        </div>

        <div class="stat-card">
            <h2><?php echo $stats['confirmed']; ?></h2>
            <p>Confirmed</p>
        </div>

        <div class="stat-card">
            <h2><?php echo $stats['pending']; ?></h2>
            <p>Pending</p>
        </div>

        <div class="stat-card">
            <h2><?php echo $stats['videos']; ?></h2>
            <p>Video Messages</p>
        </div>

        <div class="stat-card">
            <h2><?php echo $stats['events']; ?></h2>
            <p>Total Events</p>
        </div>
    </div>

    <section class="admin-panel">
        <h2>Welcome to RAS Admin</h2>
        <p>Select a menu from the sidebar to manage customer bookings,
        events or video messages.</p>
    </section>
</main>

</div>
</body>
</html>
=======

<?php
session_start();

if (!isset($_SESSION['user_id']) ||
    ($_SESSION['role'] ?? '') !== 'admin') {
    header("Location: account.php");
    exit;
}

require_once "database/database.php";

function esc($value) {
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}

$stats = [
    'total' => 0,
    'confirmed' => 0,
    'pending' => 0,
    'videos' => 0,
    'events' => 0
];

$result = $conn->query(
    "SELECT COUNT(*) AS total,
     COALESCE(SUM(status = 'Confirmed'), 0) AS confirmed,
     COALESCE(SUM(status = 'Pending'), 0) AS pending
     FROM bookings"
);

if ($result) {
    $data = $result->fetch_assoc();
    $stats['total'] = (int)$data['total'];
    $stats['confirmed'] = (int)$data['confirmed'];
    $stats['pending'] = (int)$data['pending'];
}

$result = $conn->query("SELECT COUNT(*) AS total FROM video_messages");
if ($result) {
    $stats['videos'] = (int)$result->fetch_assoc()['total'];
}

$result = $conn->query("SELECT COUNT(*) AS total FROM events");
if ($result) {
    $stats['events'] = (int)$result->fetch_assoc()['total'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Dashboard - RAS</title>
<link rel="icon" href="pictures/ras.png">
<link rel="stylesheet" href="css/style.css">

<style>
* { box-sizing: border-box; }

body.admin-page {
    margin: 0;
    background: #f7f2e9;
    font-family: Arial, sans-serif;
    color: #2b2520;
}

.admin-layout { display: flex; min-height: 100vh; }

.admin-sidebar {
    width: 245px;
    flex-shrink: 0;
    background: #211b17;
    color: white;
    padding: 30px 20px;
}

.admin-brand { text-align: center; margin-bottom: 40px; }
.admin-brand img { width: 85px; height: 85px; object-fit: contain; }
.admin-brand h3 { color: #c59445; margin: 12px 0 5px; }
.admin-brand p { font-size: 12px; color: #c8bbaa; }

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

.admin-topbar h1 { margin: 0; color: #604126; }
.admin-topbar p { color: #8b735c; }

.admin-user {
    background: white;
    padding: 12px 18px;
    border-radius: 8px;
    font-size: 14px;
}

.admin-stats {
    display: grid;
    grid-template-columns: repeat(5, minmax(0, 1fr));
    gap: 20px;
    margin-bottom: 35px;
}

.stat-card {
    background: white;
    padding: 25px;
    border-radius: 10px;
    border-left: 4px solid #c59445;
}

.stat-card h2 {
    color: #604126;
    font-size: 32px;
    margin: 0 0 10px;
}

.stat-card p { margin: 0; color: #777; }

.admin-panel {
    background: white;
    border-radius: 10px;
    padding: 25px;
    margin-bottom: 30px;
}

.admin-panel h2 { color: #604126; margin-top: 0; }

@media (max-width: 1100px) {
    .admin-stats { grid-template-columns: repeat(2, 1fr); }
}

@media (max-width: 700px) {
    .admin-layout { flex-direction: column; }
    .admin-sidebar { width: 100%; padding: 15px; }
    .admin-brand { margin-bottom: 15px; }
    .admin-brand img { width: 55px; height: 55px; }
    .admin-menu { flex-direction: row; flex-wrap: wrap; }
    .admin-menu a { padding: 10px; }
    .admin-main { padding: 20px 12px; }
    .admin-stats { gap: 10px; }
    .stat-card { padding: 15px; }
    .admin-panel { padding: 15px; }
}
</style>
</head>

<body class="admin-page">
<div class="admin-layout">

<aside class="admin-sidebar">
    <div class="admin-brand">
        <img src="pictures/ras.png" alt="RAS Logo">
        <h3>RAS ADMIN</h3>
        <p>Management Portal</p>
    </div>

    <nav class="admin-menu">
        <a href="admin.php" class="active">Dashboard</a>
        <a href="admin_bookings.php">Customer Bookings</a>
        <a href="admin_events.php">Manage Events</a>
        <a href="admin_videos.php">Video Messages</a>
        <a href="logout.php">Logout</a>
    </nav>
</aside>

<main class="admin-main">
    <div class="admin-topbar">
        <div>
            <h1>Admin Dashboard</h1>
            <p>Manage your RAS Video Guest Book reservations, events and videos.</p>
        </div>

        <div class="admin-user">
            Administrator:
            <strong><?php echo esc($_SESSION['name'] ?? 'Admin'); ?></strong>
        </div>
    </div>

    <div class="admin-stats">
        <div class="stat-card">
            <h2><?php echo $stats['total']; ?></h2>
            <p>Total Bookings</p>
        </div>

        <div class="stat-card">
            <h2><?php echo $stats['confirmed']; ?></h2>
            <p>Confirmed</p>
        </div>

        <div class="stat-card">
            <h2><?php echo $stats['pending']; ?></h2>
            <p>Pending</p>
        </div>

        <div class="stat-card">
            <h2><?php echo $stats['videos']; ?></h2>
            <p>Video Messages</p>
        </div>

        <div class="stat-card">
            <h2><?php echo $stats['events']; ?></h2>
            <p>Total Events</p>
        </div>
    </div>

    <section class="admin-panel">
        <h2>Welcome to RAS Admin</h2>
        <p>Select a menu from the sidebar to manage customer bookings,
        events or video messages.</p>
    </section>
</main>

</div>
</body>
</html>
>>>>>>> Stashed changes
