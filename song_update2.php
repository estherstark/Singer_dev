<?php
session_start();
include("db_connection.php");

// Check if user is logged in and authorized
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] < 2) {
    echo "<script>alert('You are not authorized to access this page. Redirecting to the Song Update page.'); window.location.href = 'song_update.php';</script>";
    exit();
}

$case_id = $_GET['case_id'] ?? null;
if (!$case_id) {
    echo "<script>alert('No case selected. Redirecting to the Song Update page.'); window.location.href = 'song_update.php';</script>";
    exit();
}

// Fetch song case details
$song = [];
try {
    $song_query = "
        SELECT c.case_title, 
               COALESCE(u.user_name, 'The mask singer') AS user_name, 
               c.place, 
               DATE(c.created_at) AS created_date, 
               c.detail
        FROM song_case c
        LEFT JOIN user u ON c.user_id = u.user_id
        WHERE c.case_id = :case_id";
    $stmt = $pdo->prepare($song_query);
    $stmt->bindParam(":case_id", $case_id, PDO::PARAM_INT);
    $stmt->execute();
    $song = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    echo "<script>alert('Database error: " . $e->getMessage() . "');</script>";
    exit();
}

// Fetch updates for the case
$updates = [];
$x1 = 1; // Initialize update number for display
try {
    $update_query = "SELECT update_no, DATE(timestamp) AS date, update_detail FROM case_update WHERE case_id = :case_id ORDER BY update_no ASC";
    $stmt = $pdo->prepare($update_query);
    $stmt->bindParam(":case_id", $case_id, PDO::PARAM_INT);
    $stmt->execute();
    $updates = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $x1 = count($updates) + 1;
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
    <title>Song Update Details</title>
    <script>
        function updateCase(status) {
            const detail = document.getElementById("newDetail").value;
            const data = {
                case_id: <?= json_encode($case_id) ?>,
                update_no: <?= json_encode($x1) ?>,
                update_detail: detail,
                status: status
            };

            fetch("update_case.php", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json"
                },
                body: JSON.stringify(data)
            })
            .then(response => response.json())
            .then(result => {
                if (result.success) {
                    window.location.href = 'fixer.php';
                } else {
                    alert("Error: " + result.error);
                }
            })
            .catch(error => {
                alert("An error occurred: " + error.message);
            });
        }
    </script>
</head>
<body>
    <h2>Song Update Details</h2>

    <button onclick="location.href='song_update.php'">Back</button><br><br>

    <p><strong>Case Title:</strong> <?= htmlspecialchars($song['case_title']) ?></p>
    <p><strong>User:</strong> <?= htmlspecialchars($song['user_name']) ?></p>
    <p><strong>Place:</strong> <?= htmlspecialchars($song['place']) ?></p>

    <table border="1">
        <tr>
            <th>State</th>
            <th>Date</th>
            <th>Detail</th>
            <th>Attached</th>
        </tr>
        <tr>
            <td>Create</td>
            <td><?= htmlspecialchars($song['created_date']) ?></td>
            <td><?= htmlspecialchars($song['detail']) ?></td>
            <td><button disabled>Picture</button> <button disabled>File</button></td>
        </tr>
        <?php foreach ($updates as $update): ?>
            <tr>
                <td>Update <?= htmlspecialchars($update['update_no']) ?></td>
                <td><?= htmlspecialchars($update['date']) ?></td>
                <td><?= htmlspecialchars($update['update_detail']) ?></td>
                <td><button disabled>Picture</button> <button disabled>File</button></td>
            </tr>
        <?php endforeach; ?>
        <tr>
            <td>Update <?= htmlspecialchars($x1) ?></td>
            <td><?= date("Y-m-d") ?></td>
            <td><textarea id="newDetail"></textarea></td>
            <td><button disabled>Picture</button> <button disabled>File</button></td>
        </tr>
    </table>

    <button onclick="updateCase(2)">Update</button>
    <button onclick="updateCase(3)">Update & Close Case</button>
</body>
</html>
