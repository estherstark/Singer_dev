<?php
session_start();
include("db_connection.php"); // Include the database connection

// Check if user is logged in and authorized (role 3 or above)
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] < 3) {
    echo "<script>alert('Unauthorized access. Redirecting to main page.'); window.location.href = 'main_page.php';</script>";
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch the account_id based on the user's information
try {
    $query = "SELECT account_id FROM user WHERE user_id = :user_id";
    $stmt = $pdo->prepare($query);
    $stmt->bindParam(":user_id", $user_id, PDO::PARAM_INT);
    $stmt->execute();
    $account_id = $stmt->fetchColumn();

    if (!$account_id) {
        echo "<script>alert('Account not found. Redirecting to main page.'); window.location.href = 'main_page.php';</script>";
        exit();
    }
} catch (PDOException $e) {
    echo "<script>alert('Database error: " . $e->getMessage() . "');</script>";
    exit();
}

// Fetch list of songs with status = 0 and matching account_id
$songs = [];
try {
    $song_query = "
        SELECT c.case_id, 
               COALESCE(u.user_name, 'The Mask Singer') AS user_name, 
               c.case_title, 
               c.place, 
               DATE(c.created_at) AS created_date
        FROM song_case c
        LEFT JOIN user u ON c.user_id = u.user_id
        WHERE c.account_id = :account_id AND c.status = 0
        ORDER BY c.created_at ASC";
    $stmt = $pdo->prepare($song_query);
    $stmt->bindParam(":account_id", $account_id, PDO::PARAM_INT);
    $stmt->execute();
    $songs = $stmt->fetchAll(PDO::FETCH_ASSOC);
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
    <title>QC New Song</title>
    <script>
        function enableViewButton() {
            document.getElementById('viewButton').disabled = false;
        }

        function viewSong() {
            const selectedCaseId = document.querySelector('input[name="songSelect"]:checked').value;
            if (selectedCaseId) {
                // Use a form to send the case ID via POST
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = 'qc_view.php';

                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'case_id';
                input.value = selectedCaseId;

                form.appendChild(input);
                document.body.appendChild(form);
                form.submit();
            }
        }
    </script>
</head>
<body>
    <h2>QC New Song</h2>

    <!-- Navigation Buttons -->
    <button onclick="location.href='qc.html'">Back</button>
    <button id="viewButton" onclick="viewSong()" disabled>View</button>

    <!-- Song List Table -->
    <table border="1" cellpadding="5" cellspacing="0">
        <tr>
            <th>Select</th>
            <th>User</th>
            <th>Case Title</th>
            <th>Place</th>
            <th>Create Date</th>
        </tr>
        <?php foreach ($songs as $song): ?>
            <tr>
                <td><input type="radio" name="songSelect" value="<?= $song['case_id'] ?>" onchange="enableViewButton()"></td>
                <td><?= htmlspecialchars($song['user_name']) ?></td>
                <td><?= htmlspecialchars($song['case_title']) ?></td>
                <td><?= htmlspecialchars($song['place']) ?></td>
                <td><?= htmlspecialchars($song['created_date']) ?></td>
            </tr>
        <?php endforeach; ?>
    </table>
</body>
</html>
