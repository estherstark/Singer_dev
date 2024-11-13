<?php
session_start();
include("db_connection.php");

// Check if user is logged in and authorized (role 2 or above)
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] < 2) {
    echo "<script>alert('You are not authorized to access this page. Redirecting to the Fixer page.'); window.location.href = 'fixer.php';</script>";
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch list of songs assigned to the user (fixer) with status 1 or 2
$songs = [];
try {
    $song_query = "
        SELECT c.case_id, 
               COALESCE(u.user_name, 'The mask singer') AS user_name, 
               c.case_title, 
               c.place, 
               DATE(c.created_at) AS created_date
        FROM song_case c
        LEFT JOIN user u ON c.user_id = u.user_id
        WHERE c.fixer = :user_id AND c.status IN (1, 2)
        ORDER BY c.created_at ASC";
    $song_stmt = $pdo->prepare($song_query);
    $song_stmt->bindParam(":user_id", $user_id, PDO::PARAM_INT);
    $song_stmt->execute();
    $songs = $song_stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    echo "<script>alert('Database error: " . $e->getMessage() . "');</script>";
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Song Update</title>
    <script>
        function enableViewButton() {
            document.getElementById('viewButton').disabled = false;
        }

        function goToSongUpdate2() {
            const selectedCaseId = document.querySelector('input[name="songSelect"]:checked').value;
            location.href = `song_update2.php?case_id=${selectedCaseId}`;
        }
    </script>
</head>
<body>
    <h2>Song Update</h2>

    <!-- Navigation buttons -->
    <button onclick="location.href='fixer.php'">Back</button><br><br>
    <button id="viewButton" onclick="goToSongUpdate2()" disabled>View</button><br><br>

    <!-- List of songs -->
    <form id="songList">
        <?php foreach ($songs as $song): ?>
            <div>
                <input type="radio" name="songSelect" value="<?= $song['case_id'] ?>" onchange="enableViewButton()">
                <strong>User:</strong> <?= htmlspecialchars($song['user_name']) ?>,
                <strong>Title:</strong> <?= htmlspecialchars($song['case_title']) ?>,
                <strong>Place:</strong> <?= htmlspecialchars($song['place']) ?>,
                <strong>Created Date:</strong> <?= htmlspecialchars($song['created_date']) ?>
            </div>
        <?php endforeach; ?>
    </form>
</body>
</html>
