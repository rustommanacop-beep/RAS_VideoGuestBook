
<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    exit("Please log in.");
}

include("database/database.php");

$userId = (int)$_SESSION['user_id'];
$isAdmin = ($_SESSION['role'] ?? '') === 'admin';
$videoId = (int)($_GET['id'] ?? 0);

if ($videoId <= 0) {
    http_response_code(400);
    exit("Invalid video ID.");
}

$stmt = $conn->prepare(
    "SELECT v.video_filename, b.user_id
     FROM video_messages v
     JOIN bookings b ON v.booking_id = b.booking_id
     WHERE v.video_id = ?"
);

$stmt->bind_param("i", $videoId);
$stmt->execute();

$video = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$video) {
    http_response_code(404);
    exit("Video not found.");
}

if (!$isAdmin && (int)$video['user_id'] !== $userId) {
    http_response_code(403);
    exit("You do not have permission to view this video.");
}

$filename = $video['video_filename'];

if (
    !is_string($filename) ||
    !preg_match(
        '/^[a-f0-9]{32}\.(mp4|webm|mov)$/',
        $filename
    )
) {
    http_response_code(404);
    exit("Invalid video filename.");
}

$uploadDir = dirname(__DIR__) .
    DIRECTORY_SEPARATOR .
    'private_ras_videos';

$filePath = $uploadDir .
    DIRECTORY_SEPARATOR .
    $filename;

if (!is_file($filePath) || !is_readable($filePath)) {
    http_response_code(404);
    exit("Video file unavailable.");
}

$extension = strtolower(pathinfo(
    $filename,
    PATHINFO_EXTENSION
));

$mimeTypes = [
    'mp4' => 'video/mp4',
    'webm' => 'video/webm',
    'mov' => 'video/quicktime'
];

$mime = $mimeTypes[$extension] ?? 'application/octet-stream';

$fileSize = filesize($filePath);

if ($fileSize === false) {
    http_response_code(500);
    exit("Unable to read video size.");
}

$download = isset($_GET['download']) &&
    $_GET['download'] === '1';

header('Content-Type: ' . $mime);
header('X-Content-Type-Options: nosniff');
header('Cache-Control: private, no-store');
header('Accept-Ranges: bytes');

if ($download) {
    header(
        'Content-Disposition: attachment; filename="' .
        $filename . '"'
    );
} else {
    header(
        'Content-Disposition: inline; filename="' .
        $filename . '"'
    );
}

$start = 0;
$end = $fileSize - 1;

if ($fileSize === 0) {
    header('Content-Length: 0');
    exit;
}

if (isset($_SERVER['HTTP_RANGE'])) {

    $range = $_SERVER['HTTP_RANGE'];

    if (!preg_match(
        '/^bytes=(\d*)-(\d*)$/',
        $range,
        $matches
    )) {
        header('Content-Range: bytes */' . $fileSize);
        http_response_code(416);
        exit;
    }

    $rangeStart = $matches[1];
    $rangeEnd = $matches[2];

    if ($rangeStart === '' && $rangeEnd === '') {
        http_response_code(416);
        header('Content-Range: bytes */' . $fileSize);
        exit;
    }

    if ($rangeStart === '') {
        $suffix = (int)$rangeEnd;

        if ($suffix <= 0) {
            http_response_code(416);
            header('Content-Range: bytes */' . $fileSize);
            exit;
        }

        $start = max(0, $fileSize - $suffix);
    } else {
        $start = (int)$rangeStart;

        if ($rangeEnd !== '') {
            $end = min((int)$rangeEnd, $end);
        }
    }

    if ($start > $end || $start >= $fileSize) {
        http_response_code(416);
        header('Content-Range: bytes */' . $fileSize);
        exit;
    }

    http_response_code(206);
    header(
        "Content-Range: bytes $start-$end/$fileSize"
    );
}

$length = $end - $start + 1;
header('Content-Length: ' . $length);

$handle = fopen($filePath, 'rb');

if (!$handle) {
    http_response_code(500);
    exit;
}

fseek($handle, $start);

$remaining = $length;

while ($remaining > 0 && !feof($handle)) {

    $chunk = fread(
        $handle,
        min(8192, $remaining)
    );

    if ($chunk === false || $chunk === '') {
        break;
    }

    echo $chunk;

    $remaining -= strlen($chunk);

    flush();
}

fclose($handle);
exit;
?>
