<?php
session_start();
include("db_connection.php");

// Check if user is logged in and authorized (role 2 or above)
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] < 2) {
    echo "<script>alert('You are not authorized to access this page. Redirecting to the Fixer page.'); window.location.href = 'fixer.php';</script>";
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch account_id for the user
try {
    $query = "SELECT account_id FROM user WHERE user_id = :user_id";
    $stmt = $pdo->prepare($query);
    $stmt->bindParam(":user_id", $user_id, PDO::PARAM_INT);
    $stmt->execute();
    $account_id = $stmt->fetchColumn();
} catch (PDOException $e) {
    echo "<script>alert('Database error: " . $e->getMessage() . "');</script>";
    exit();
}

// Fetch list of songs with status = 0 and same account_id
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
        WHERE c.account_id = :account_id AND c.status = 0
        ORDER BY c.created_at ASC";
    $song_stmt = $pdo->prepare($song_query);
    $song_stmt->bindParam(":account_id", $account_id, PDO::PARAM_INT);
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
    <title>Acknowledge Song Cases</title>
    <script>
        // Enable the View button only if a song is selected
        function enableViewButton() {
            document.getElementById('viewButton').disabled = false;
        }

        // Fetch full details for the selected case
        function showPopup(caseId) {
            fetch(`fetch_song_details.php?case_id=${caseId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const popupContent = `
                            <p><strong>User:</strong> ${data.user_name}</p>
                            <p><strong>Title:</strong> ${data.case_title}</p>
                            <p><strong>Place:</strong> ${data.place}</p>
                            <p><strong>Created Date:</strong> ${data.created_date}</p>
                            <p><strong>Detail:</strong> ${data.detail}</p>
                            <button disabled>Picture</button>
                            <button disabled>File</button>
                            <br><br>
                            <button onclick="acknowledgeSong(${caseId})">Acknowledge</button>
                            <button onclick="closePopup()">Close</button>
                        `;
                        document.getElementById("popupContent").innerHTML = popupContent;
                        document.getElementById("popup").style.display = "block";
                    } else {
                        alert("Failed to load song details: " + data.error);
                    }
                })
                .catch(error => {
                    alert("An error occurred: " + error.message);
                });
        }

        // Close the popup
        function closePopup() {
            document.getElementById("popup").style.display = "none";
        }

        // Acknowledge the selected song
        function acknowledgeSong(caseId) {
            fetch("acknowledge_song.php", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                },
                body: JSON.stringify({ case_id: caseId })
            })
            .then(response => response.json())
            .then(result => {
                if (result.success) {
                    closePopup();
                    location.reload(); // Refresh the page after acknowledgment
                } else {
                    alert("Failed to acknowledge the song: " + result.error);
                }
            })
            .catch(error => {
                alert("An error occurred: " + error.message);
            });
        }
    </script>
    <style>
        /* Style for popup */
        #popup {
            display: none;
            position: fixed;
            top: 20%;
            left: 50%;
            transform: translate(-50%, -20%);
            border: 1px solid #ccc;
            padding: 20px;
            background-color: #fff;
            z-index: 1000;
        }
        #popupContent {
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <h2>Acknowledge Song Cases</h2>

    <!-- Navigation buttons -->
    <button onclick="location.href='fixer.php'">Back</button><br><br>
    <button id="viewButton" onclick="showPopup(document.querySelector('input[name=\'songSelect\']:checked').value)" disabled>View</button><br><br>

    <!-- List of songs -->
    <form id="songList">
        <?php foreach ($songs as $song): ?>
            <div>
                <input type="radio" name="songSelect" value="<?= $song['case_id'] ?>"
                       data-username="<?= htmlspecialchars($song['user_name']) ?>"
                       onchange="enableViewButton()">
                <strong>User:</strong> <?= htmlspecialchars($song['user_name']) ?>,
                <strong>Title:</strong> <?= htmlspecialchars($song['case_title']) ?>,
                <strong>Place:</strong> <?= htmlspecialchars($song['place']) ?>,
                <strong>Created Date:</strong> <?= htmlspecialchars($song['created_date']) ?>
            </div>
        <?php endforeach; ?>
    </form>

    <!-- Popup for song details -->
    <div id="popup">
        <div id="popupContent"></div>
    </div>
</body>
</html>
