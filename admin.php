
<?php
session_start();

// ADMIN ACCESS ONLY
if (
    !isset($_SESSION['user_id']) ||
    ($_SESSION['role'] ?? '') !== 'admin'
) {
    header("Location: account.php");
    exit;
}

include("database/database.php");

// CSRF PROTECTION
if (!isset($_SESSION['admin_csrf'])) {
    $_SESSION['admin_csrf'] = bin2hex(random_bytes(32));
}

// ====================================
// UPDATE BOOKING STATUS
// ====================================

if (isset($_POST['update_booking'])) {

    if (!hash_equals(
        $_SESSION['admin_csrf'],
        $_POST['csrf'] ?? ''
    )) {
        exit("Invalid request.");
    }

    $id = (int)($_POST['booking_id'] ?? 0);
    $status = $_POST['status'] ?? '';

    $allowedStatuses = [
        'Pending',
        'Confirmed',
        'Completed',
        'Cancelled'
    ];

    if (
        $id > 0 &&
        in_array($status, $allowedStatuses, true)
    ) {

        $stmt = $conn->prepare(
            "UPDATE bookings
             SET status = ?
             WHERE booking_id = ?"
        );

        $stmt->bind_param("si", $status, $id);
        $stmt->execute();
        $stmt->close();
    }

    header("Location: admin.php#bookings");
    exit;
}

// ====================================
// DASHBOARD STATISTICS
// ====================================

$stats = [
    'total' => 0,
    'confirmed' => 0,
    'pending' => 0,
    'videos' => 0
];

$counts = $conn->query(
    "SELECT
        COUNT(*) AS total,
        COALESCE(SUM(status = 'Confirmed'), 0) AS confirmed,
        COALESCE(SUM(status = 'Pending'), 0) AS pending
     FROM bookings"
);

if ($counts) {
    $data = $counts->fetch_assoc();

    $stats['total'] = (int)$data['total'];
    $stats['confirmed'] = (int)$data['confirmed'];
    $stats['pending'] = (int)$data['pending'];
}

$videoCount = $conn->query(
    "SELECT COUNT(*) AS total FROM video_messages"
);

if ($videoCount) {
    $stats['videos'] =
        (int)$videoCount->fetch_assoc()['total'];
}

// ====================================
// CUSTOMER BOOKINGS
// SORT BOOKING ID: 1, 2, 3, 4...
// ====================================

$bookings = $conn->query(
    "SELECT b.*, e.event_name
     FROM bookings b
     JOIN events e ON b.event_id = e.event_id
     ORDER BY b.booking_id ASC"
);

// ====================================
// VIDEO MESSAGES
// SORT VIDEO ID: 1, 2, 3, 4...
// ====================================

$videos = $conn->query(
    "SELECT v.*, e.event_name, b.customer_name
     FROM video_messages v
     JOIN bookings b ON v.booking_id = b.booking_id
     JOIN events e ON b.event_id = e.event_id
     ORDER BY v.video_id ASC"
);

// ESCAPE OUTPUT
function esc($value) {
    return htmlspecialchars(
        (string)($value ?? ''),
        ENT_QUOTES,
        'UTF-8'
    );
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Admin Dashboard - RAS</title>

    <link rel="icon" href="pictures/ras.png">
    <link rel="stylesheet" href="css/style.css">

    <style>
        /* ADMIN DESIGN */

        body.admin-page {
            margin: 0;
            background: #f7f2e9;
            font-family: Arial, sans-serif;
            color: #2b2520;
        }

        .admin-layout {
            display: flex;
            min-height: 100vh;
        }

        .admin-sidebar {
            width: 245px;
            flex-shrink: 0;
            background: #211b17;
            color: white;
            padding: 30px 20px;
            box-sizing: border-box;
        }

        .admin-brand {
            text-align: center;
            margin-bottom: 40px;
        }

        .admin-brand img {
            width: 85px;
            height: 85px;
            object-fit: contain;
        }

        .admin-brand h3 {
            color: #c59445;
            margin: 12px 0 5px;
        }

        .admin-brand p {
            font-size: 12px;
            color: #c8bbaa;
        }

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
            box-sizing: border-box;
        }

        .admin-topbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
            margin-bottom: 30px;
        }

        .admin-topbar h1 {
            margin: 0;
            color: #604126;
        }

        .admin-topbar p {
            color: #8b735c;
        }

        .admin-user {
            background: white;
            padding: 12px 18px;
            border-radius: 8px;
            font-size: 14px;
        }

        .admin-stats {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
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

        .stat-card p {
            margin: 0;
            color: #777;
        }

        .admin-panel {
            background: white;
            border-radius: 10px;
            padding: 25px;
            margin-bottom: 30px;
        }

        .admin-panel h2 {
            color: #604126;
            margin-top: 0;
            margin-bottom: 22px;
        }

        .admin-table-wrap {
            overflow-x: auto;
        }

        .admin-table {
            width: 100%;
            border-collapse: collapse;
        }

        .admin-table th,
        .admin-table td {
            padding: 13px;
            text-align: left;
            border-bottom: 1px solid #eee;
            font-size: 13px;
            vertical-align: middle;
        }

        .admin-table th {
            background: #f7f2e9;
            color: #604126;
        }

        .status-form {
            display: flex;
            align-items: center;
            gap: 7px;
        }

        .status-form select {
            padding: 8px;
            width: auto;
            min-width: 110px;
        }

        .admin-btn {
            display: inline-block;
            padding: 10px 14px;
            background: #c59445;
            color: white;
            border: 0;
            border-radius: 6px;
            text-decoration: none;
            font-size: 12px;
            font-weight: bold;
            cursor: pointer;
            white-space: nowrap;
        }

        .admin-btn:hover {
            background: #a87930;
        }

        .admin-muted {
            color: #888;
            font-size: 12px;
        }

        @media (max-width: 1050px) {
            .admin-stats {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 700px) {
            .admin-layout {
                flex-direction: column;
            }

            .admin-sidebar {
                width: 100%;
                padding: 15px;
            }

            .admin-brand {
                margin-bottom: 15px;
            }

            .admin-brand img {
                width: 55px;
                height: 55px;
            }

            .admin-menu {
                flex-direction: row;
                flex-wrap: wrap;
            }

            .admin-menu a {
                padding: 10px;
            }

            .admin-main {
                padding: 20px 12px;
            }

            .admin-stats {
                gap: 10px;
            }

            .stat-card {
                padding: 15px;
            }

            .admin-panel {
                padding: 15px;
            }

            .status-form {
                flex-wrap: wrap;
            }
        }
    </style>
</head>

<body class="admin-page">

<div class="admin-layout">

    <!-- ADMIN SIDEBAR -->

    <aside class="admin-sidebar">

        <div class="admin-brand">

            <img
                src="pictures/ras.png"
                alt="RAS Logo"
            >

            <h3>RAS ADMIN</h3>
            <p>Management Portal</p>

        </div>

        <nav class="admin-menu">

            <a href="#dashboard" class="active">
                Dashboard
            </a>

            <a href="#bookings">
                Customer Bookings
            </a>

            <a href="#videos">
                Video Messages
            </a>

            <a href="logout.php">
                Logout
            </a>

        </nav>

    </aside>

    <!-- MAIN ADMIN CONTENT -->

    <main class="admin-main">

        <div class="admin-topbar" id="dashboard">

            <div>
                <h1>Admin Dashboard</h1>

                <p>
                    Manage your RAS Video Guest Book
                    reservations and videos.
                </p>
            </div>

            <div class="admin-user">

                Administrator:

                <strong>
                    <?php echo esc(
                        $_SESSION['name'] ?? 'Admin'
                    ); ?>
                </strong>

            </div>

        </div>

        <!-- DASHBOARD STATISTICS -->

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

        </div>

        <!-- CUSTOMER BOOKINGS -->

        <section class="admin-panel" id="bookings">

            <h2>Customer Bookings</h2>

            <div class="admin-table-wrap">

                <table class="admin-table">

                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Customer</th>
                            <th>Service</th>
                            <th>Date</th>
                            <th>Time</th>
                            <th>Location</th>
                            <th>Status</th>
                            <th>Video Upload</th>
                        </tr>
                    </thead>

                    <tbody>

                    <?php if (
                        $bookings &&
                        $bookings->num_rows > 0
                    ) { ?>

                        <?php while (
                            $row = $bookings->fetch_assoc()
                        ) { ?>

                            <tr>

                                <td>
                                    #<?php echo (int)$row['booking_id']; ?>
                                </td>

                                <td>
                                    <?php echo esc(
                                        $row['customer_name']
                                    ); ?>
                                </td>

                                <td>
                                    <?php echo esc(
                                        $row['event_name']
                                    ); ?>
                                </td>

                                <td>
                                    <?php echo esc(
                                        $row['event_date']
                                    ); ?>
                                </td>

                                <td>
                                    <?php echo esc(
                                        $row['booking_time']
                                    ); ?>
                                </td>

                                <td>
                                    <?php echo esc(
                                        $row['location']
                                    ); ?>
                                </td>

                                <td>

                                    <form
                                        method="POST"
                                        class="status-form"
                                    >

                                        <input
                                            type="hidden"
                                            name="csrf"
                                            value="<?php echo esc(
                                                $_SESSION['admin_csrf']
                                            ); ?>"
                                        >

                                        <input
                                            type="hidden"
                                            name="booking_id"
                                            value="<?php echo (int)$row['booking_id']; ?>"
                                        >

                                        <select name="status">

                                            <?php foreach (
                                                [
                                                    'Pending',
                                                    'Confirmed',
                                                    'Completed',
                                                    'Cancelled'
                                                ] as $status
                                            ) { ?>

                                                <option
                                                    value="<?php echo esc($status); ?>"
                                                    <?php
                                                    if (
                                                        $row['status'] === $status
                                                    ) {
                                                        echo 'selected';
                                                    }
                                                    ?>
                                                >
                                                    <?php echo esc($status); ?>
                                                </option>

                                            <?php } ?>

                                        </select>

                                        <button
                                            type="submit"
                                            name="update_booking"
                                            class="admin-btn"
                                        >
                                            SAVE
                                        </button>

                                    </form>

                                </td>

                                <td>

                                    <?php if (
                                        $row['status'] !== 'Cancelled'
                                    ) { ?>

                                        <a
                                            href="video_message.php?booking_id=<?php
                                                echo (int)$row['booking_id'];
                                            ?>"
                                            class="admin-btn"
                                        >
                                            UPLOAD VIDEO
                                        </a>

                                    <?php } else { ?>

                                        <span class="admin-muted">
                                            Cancelled
                                        </span>

                                    <?php } ?>

                                </td>

                            </tr>

                        <?php } ?>

                    <?php } else { ?>

                        <tr>
                            <td colspan="8">
                                No bookings found.
                            </td>
                        </tr>

                    <?php } ?>

                    </tbody>

                </table>

            </div>

        </section>

        <!-- VIDEO MESSAGES -->

        <section class="admin-panel" id="videos">

            <h2>Video Messages</h2>

            <div class="admin-table-wrap">

                <table class="admin-table">

                    <thead>
                        <tr>
                            <th>Video ID</th>
                            <th>Customer</th>
                            <th>Service</th>
                            <th>Filename</th>
                            <th>Message</th>
                            <th>Status</th>
                        </tr>
                    </thead>

                    <tbody>

                    <?php if (
                        $videos &&
                        $videos->num_rows > 0
                    ) { ?>

                        <?php while (
                            $video = $videos->fetch_assoc()
                        ) { ?>

                            <tr>

                                <td>
                                    #<?php echo (int)$video['video_id']; ?>
                                </td>

                                <td>
                                    <?php echo esc(
                                        $video['customer_name']
                                    ); ?>
                                </td>

                                <td>
                                    <?php echo esc(
                                        $video['event_name']
                                    ); ?>
                                </td>

                                <td>
                                    <?php echo esc(
                                        $video['video_filename']
                                    ); ?>
                                </td>

                                <td>
                                    <?php echo esc(
                                        $video['message']
                                    ); ?>
                                </td>

                                <td>
                                    <?php echo esc(
                                        $video['status']
                                    ); ?>
                                </td>

                            </tr>

                        <?php } ?>

                    <?php } else { ?>

                        <tr>
                            <td colspan="6">
                                No videos uploaded yet.
                            </td>
                        </tr>

                    <?php } ?>

                    </tbody>

                </table>

            </div>

        </section>

    </main>

</div>

</body>
</html>
