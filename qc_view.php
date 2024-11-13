<?php
session_start();
include("db_connection.php");

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['case_id'])) {
    $case_id = $_POST['case_id'];
} else {
    echo "<script>alert('No case selected. Redirecting to QC New Song Page.'); window.location.href = 'qc_new_song.php';</script>";
    exit();
}

// Fetch case details from song_case
try {
    $case_query = "
        SELECT c.case_title, 
               COALESCE(u.user_name, 'The Mask Singer') AS user_name, 
               c.place, 
               c.created_at, 
               c.detail, 
               c.account_id
        FROM song_case c
        LEFT JOIN user u ON c.user_id = u.user_id
        WHERE c.case_id = :case_id";
    $stmt = $pdo->prepare($case_query);
    $stmt->bindParam(":case_id", $case_id, PDO::PARAM_INT);
    $stmt->execute();
    $case = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$case) {
        echo "<script>alert('Case not found. Redirecting to QC New Song Page.'); window.location.href = 'qc_new_song.php';</script>";
        exit();
    }

    // Calculate case age (difference between today and created_at)
    $created_date = new DateTime($case['created_at']);
    $current_date = new DateTime();
    $age = $created_date->diff($current_date)->days;
} catch (PDOException $e) {
    echo "<script>alert('Database error: " . $e->getMessage() . "');</script>";
    exit();
}

// Fetch force_ack threshold from account
$force_ack_threshold = 0;
try {
    $account_query = "SELECT force_ack FROM account WHERE account_id = :account_id";
    $stmt = $pdo->prepare($account_query);
    $stmt->bindParam(":account_id", $case['account_id'], PDO::PARAM_INT);
    $stmt->execute();
    $force_ack_threshold = $stmt->fetchColumn();
} catch (PDOException $e) {
    echo "<script>alert('Database error: " . $e->getMessage() . "');</script>";
    exit();
}

// Enable force acknowledge button if age >= force_ack threshold
$enable_force_ack = ($age >= $force_ack_threshold);

// Fetch users with role = 2 for the dropdown in Force Acknowledge popup
$fixer_options = [];
try {
    $user_query = "SELECT user_id, user_name FROM user WHERE account_id = :account_id AND role = 2";
    $stmt = $pdo->prepare($user_query);
    $stmt->bindParam(":account_id", $case['account_id'], PDO::PARAM_INT);
    $stmt->execute();
    $fixer_options = $stmt->fetchAll(PDO::FETCH_ASSOC);
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
    <title>QC View Song</title>
    <script>
        function showForceClosePopup() {
            document.getElementById('forceClosePopup').style.display = 'block';
            document.getElementById('forceAcknowledgePopup').style.display = 'none';
        }

        function showForceAcknowledgePopup() {
            document.getElementById('forceAcknowledgePopup').style.display = 'block';
            document.getElementById('forceClosePopup').style.display = 'none';
        }

        function closePopup(popupId) {
            document.getElementById(popupId).style.display = 'none';
        }

        function confirmForceClose() {
            const reason = document.getElementById('forceCloseReason').value.trim();
            const userId = <?= json_encode($_SESSION['user_id']) ?>; // Current user's ID

            if (!reason) {
                alert("Please provide a reason.");
                return;
            }
            if (confirm("Are you sure you want to terminate this song?")) {
                fetch("force_close_case.php", {
                    method: "POST",
                    headers: { "Content-Type": "application/json" },
                    body: JSON.stringify({ 
                        case_id: <?= json_encode($case_id) ?>, 
                        reason: reason,
                        force_id: userId // Include current user ID
                    })
                }).then(response => response.json())
                .then(data => {
                    if (data.success) {
                        window.location.href = 'qc_new_song.php';
                    } else {
                        alert("Failed to terminate the song. " + data.message);
                    }
                });
            }
        }


        function confirmForceAcknowledge() {
            const fixerId = document.getElementById('fixerSelect').value;
            if (!fixerId) {
                alert("Please select a fixer.");
                return;
            }
            if (confirm("Are you sure you want to force acknowledge this song?")) {
                fetch("force_acknowledge_case.php", {
                    method: "POST",
                    headers: { "Content-Type": "application/json" },
                    body: JSON.stringify({ case_id: <?= json_encode($case_id) ?>, fixer_id: fixerId })
                }).then(response => response.json())
                .then(data => {
                    if (data.success) {
                        window.location.href = 'qc_new_song.php';
                    } else {
                        alert("Failed to acknowledge the song. " + data.message);
                    }
                });
            }
        }
    </script>

</head>
<body>
    <h2>QC View Song</h2>

    <!-- Navigation Buttons -->
    <button onclick="location.href='qc_new_song.php'">Back</button>
    <button onclick="showForceClosePopup()">Force Close</button>
    <button onclick="showForceAcknowledgePopup()" <?= $enable_force_ack ? '' : 'disabled' ?>>Force Acknowledge</button>

    <!-- Case Information -->
    <p><strong>Case Title:</strong> <?= htmlspecialchars($case['case_title']) ?></p>
    <p><strong>User:</strong> <?= htmlspecialchars($case['user_name']) ?></p>
    <p><strong>Place:</strong> <?= htmlspecialchars($case['place']) ?></p>
    <p><strong>Age:</strong> <?= htmlspecialchars($age) ?> days</p>

    <!-- Case Details Table -->
    <table border="1">
        <tr>
            <th>State</th>
            <th>Date</th>
            <th>Detail</th>
            <th>Attached</th>
        </tr>
        <tr>
            <td>Create</td>
            <td><?= htmlspecialchars(explode(' ', $case['created_at'])[0]) ?></td>
            <td><?= htmlspecialchars($case['detail']) ?></td>
            <td><button disabled>Picture</button> <button disabled>File</button></td>
        </tr>
    </table>

    <!-- Force Close Popup -->
    <div id="forceClosePopup" style="display:none;">
        <h3>Terminate this song</h3>
        <label for="forceCloseReason"><strong>Reason:</strong></label>
        <textarea id="forceCloseReason" required></textarea><br><br>
        <button onclick="closePopup('forceClosePopup')">Cancel</button>
        <button onclick="confirmForceClose()">Confirm</button>
    </div>

    <!-- Force Acknowledge Popup -->
    <div id="forceAcknowledgePopup" style="display:none;">
        <h3>Force work</h3>
        <label for="fixerSelect"><strong>Select Fixer:</strong></label>
        <select id="fixerSelect" required>
            <option value="">Select Fixer</option>
            <?php foreach ($fixer_options as $fixer): ?>
                <option value="<?= htmlspecialchars($fixer['user_id']) ?>"><?= htmlspecialchars($fixer['user_name']) ?></option>
            <?php endforeach; ?>
        </select><br><br>
        <button onclick="closePopup('forceAcknowledgePopup')">Cancel</button>
        <button onclick="confirmForceAcknowledge()">Confirm</button>
    </div>
</body>
</html>
