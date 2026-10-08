
<?php
session_start();

if (
    !isset($_SESSION['user_id']) ||
    ($_SESSION['role'] ?? '') !== 'admin'
) {
    http_response_code(403);
    exit("Access denied. Admin only.");
}

include("database/database.php");

function esc($value) {
    return htmlspecialchars(
        (string)($value ?? ''),
        ENT_QUOTES,
        'UTF-8'
    );
}

if (!isset($_SESSION['video_csrf'])) {
    $_SESSION['video_csrf'] = bin2hex(random_bytes(32));
}

$bookingId = (int)(
    $_POST['booking_id'] ??
    $_GET['booking_id'] ??
    0
);

if ($bookingId <= 0) {
    exit("Invalid booking ID.");
}

// CHECK BOOKING EXISTS
$stmt = $conn->prepare(
    "SELECT b.booking_id, b.customer_name,
            b.status, e.event_name
     FROM bookings b
     JOIN events e ON b.event_id = e.event_id
     WHERE b.booking_id = ?"
);

$stmt->bind_param("i", $bookingId);
$stmt->execute();

$booking = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$booking) {
    http_response_code(404);
    exit("Booking not found.");
}

$error = '';
$success = '';

if (isset($_POST['upload_video'])) {

    if (!hash_equals(
        $_SESSION['video_csrf'],
        $_POST['csrf'] ?? ''
    )) {
        exit("Invalid request.");
    }

    if ($booking['status'] === 'Cancelled') {
        $error = "Cannot upload a video for a cancelled booking.";
    } elseif (
        !isset($_FILES['video']) ||
        $_FILES['video']['error'] !== UPLOAD_ERR_OK
    ) {
        $error = "Please select a valid video file.";
    } else {

        $file = $_FILES['video'];
        $message = trim($_POST['message'] ?? '');

        // Maximum 100 MB
        $maxSize = 100 * 1024 * 1024;

        if ($file['size'] > $maxSize) {
            $error = "Video must be 100 MB or smaller.";
        } else {

            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $mime = $finfo->file($file['tmp_name']);

            $allowed = [
                'video/mp4' => 'mp4',
                'video/webm' => 'webm',
                'video/quicktime' => 'mov'
            ];

            if (!isset($allowed[$mime])) {
                $error = "Only MP4, WebM, or MOV videos are allowed.";
            } else {

                // Store outside the public web directory.
                // For WAMP, this resolves to:
                // C:\wamp64\private_ras_videos
                $uploadDir = dirname(__DIR__) .
                    DIRECTORY_SEPARATOR .
                    'private_ras_videos';

                if (
                    !is_dir($uploadDir) &&
                    !mkdir($uploadDir, 0700, true)
                ) {
                    $error = "Could not create video storage directory.";
                } else {

                    $filename =
                        bin2hex(random_bytes(16)) .
                        '.' . $allowed[$mime];

                    $destination = $uploadDir .
                        DIRECTORY_SEPARATOR . $filename;

                    if (!move_uploaded_file(
                        $file['tmp_name'],
                        $destination
                    )) {
                        $error = "Failed to save uploaded video.";
                    } else {

                        $status = 'Uploaded';

                        $insert = $conn->prepare(
                            "INSERT INTO video_messages
                             (booking_id, video_filename,
                              message, status, upload_date)
                             VALUES (?, ?, ?, ?, NOW())"
                        );

                        $insert->bind_param(
                            "isss",
                            $bookingId,
                            $filename,
                            $message,
                            $status
                        );

                        if ($insert->execute()) {
                            $success = "Video uploaded successfully. The customer can now view it in My Videos.";
                        } else {
                            $error = "Database error while saving video.";
                            unlink($destination);
                        }

                        $insert->close();
                    }
                }
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Upload Video - RAS Admin</title>
<link rel="icon" href="pictures/ras.png">
<link rel="stylesheet" href="css/style.css">

<style>
body.upload-page {
    margin: 0;
    background: #f7f2e9;
    font-family: Arial, sans-serif;
}

.upload-header {
    background: #211b17;
    color: #c59445;
    padding: 25px;
    text-align: center;
}

.upload-container {
    max-width: 650px;
    margin: 55px auto;
    padding: 0 20px;
}

.upload-card {
    background: white;
    border-radius: 12px;
    padding: 35px;
    border: 1px solid #eee5da;
}

.upload-card h2 {
    color: #604126;
}

.upload-card label {
    display: block;
    font-weight: bold;
    margin: 18px 0 8px;
    color: #604126;
}

.upload-card input[type="file"],
.upload-card textarea {
    display: block;
    width: 100%;
    box-sizing: border-box;
    padding: 12px;
    border: 1px solid #ddd;
    border-radius: 6px;
}

.upload-card textarea {
    min-height: 110px;
    resize: vertical;
}

.upload-btn {
    background: #c59445;
    color: white;
    padding: 13px 20px;
    border: 0;
    border-radius: 6px;
    cursor: pointer;
    margin-top: 20px;
}

.upload-back {
    display: inline-block;
    margin-top: 20px;
    color: #604126;
    text-decoration: none;
}

.upload-message {
    padding: 12px;
    border-radius: 6px;
    margin: 20px 0;
}

.upload-message.success {
    background: #e4f4e7;
    color: #27783b;
}

.upload-message.error {
    background: #fce7e7;
    color: #a32c2c;
}
</style>
</head>

<body class="upload-page">

<header class="upload-header">
    <h2>RAS ADMIN - VIDEO UPLOAD</h2>
</header>

<main class="upload-container">

    <div class="upload-card">

        <h2>Upload Customer Video</h2>

        <p>
            <strong>Booking:</strong>
            #<?php echo (int)$booking['booking_id']; ?>
        </p>

        <p>
            <strong>Customer:</strong>
            <?php echo esc($booking['customer_name']); ?>
        </p>

        <p>
            <strong>Service:</strong>
            <?php echo esc($booking['event_name']); ?>
        </p>

        <?php if ($error !== '') { ?>
            <div class="upload-message error">
                <?php echo esc($error); ?>
            </div>
        <?php } ?>

        <?php if ($success !== '') { ?>
            <div class="upload-message success">
                <?php echo esc($success); ?>
            </div>
        <?php } ?>

        <?php if ($booking['status'] !== 'Cancelled') { ?>

            <form
                method="POST"
                enctype="multipart/form-data"
            >

                <input
                    type="hidden"
                    name="csrf"
                    value="<?php echo esc($_SESSION['video_csrf']); ?>"
                >

                <input
                    type="hidden"
                    name="booking_id"
                    value="<?php echo $bookingId; ?>"
                >

                <label for="video">Choose Video</label>

                <input
                    type="file"
                    id="video"
                    name="video"
                    accept=".mp4,.webm,.mov,video/mp4,video/webm,video/quicktime"
                    required
                >

                <label for="message">Message (Optional)</label>

                <textarea
                    id="message"
                    name="message"
                    maxlength="2000"
                    placeholder="Write a message for the customer..."
                ></textarea>

                <button
                    type="submit"
                    name="upload_video"
                    class="upload-btn"
                >
                    UPLOAD VIDEO
                </button>

            </form>

        <?php } else { ?>

            <p>
                Video uploads are disabled for cancelled bookings.
            </p>

        <?php } ?>

        <a href="admin.php#bookings" class="upload-back">
            ← Back to Admin Dashboard
        </a>

    </div>

</main>

</body>
</html>
