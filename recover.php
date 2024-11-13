<?php
session_start();
include("db_connection.php");

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo "<script>alert('Unauthorized access. Redirecting to main page.'); window.location.href = 'main_page.php';</script>";
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch account_id and case_recover from account based on user_id
try {
    $account_query = "
        SELECT a.account_id, a.case_recover 
        FROM account a
        JOIN user u ON u.account_id = a.account_id
        WHERE u.user_id = :user_id";
    $stmt = $pdo->prepare($account_query);
    $stmt->bindParam(":user_id", $user_id, PDO::PARAM_INT);
    $stmt->execute();
    $account = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$account) {
        echo "<script>alert('Account not found.'); window.location.href = 'qc.html';</script>";
        exit();
    }
    $account_id = $account['account_id'];
    $case_recover_days = $account['case_recover'];
} catch (PDOException $e) {
    echo "<script>alert('Database error: " . $e->getMessage() . "');</script>";
    exit();
}

// Fetch recoverable songs based on the criteria
$songs = [];
try {
    $song_query = "
        SELECT c.case_id, 
               COALESCE(u.user_name, 'The Mask Singer') AS user_name, 
               c.case_title, 
               c.place, 
               DATE(c.close_at) AS close_date, 
               f.user_name AS fixer_name
        FROM song_case c
        LEFT JOIN user u ON c.user_id = u.user_id
        LEFT JOIN user f ON c.fixer = f.user_id
        WHERE c.account_id = :account_id AND c.status = 3 
              AND DATEDIFF(CURDATE(), c.close_at) < :case_recover_days
        ORDER BY c.close_at DESC";
    $stmt = $pdo->prepare($song_query);
    $stmt->bindParam(":account_id", $account_id, PDO::PARAM_INT);
    $stmt->bindParam(":case_recover_days", $case_recover_days, PDO::PARAM_INT);
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
    <title>Recover Cases</title>
    <script>
        function enableButtons() {
            document.getElementById('recoverButton').disabled = false;
            document.getElementById('viewButton').disabled = false;
        }

        function showPopup() {
            const selectedCaseId = document.querySelector('input[name="songSelect"]:checked').value;
            const popupContent = document.getElementById('popupContent');

            // Fetch data for the selected case
            fetch("fetch_case_details.php", {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify({ case_id: selectedCaseId })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Populate popup content with case details
                    popupContent.innerHTML = `
                        <p><strong>Case Title:</strong> ${data.case_title}</p>
                        <p><strong>User:</strong> ${data.user_name}</p>
                        <p><strong>Created At:</strong> ${data.created_at}</p>
                        <p><strong>Place:</strong> ${data.place}</p>
                        <p><strong>Fixer:</strong> ${data.fixer_name}</p>
                        <p><strong>Acknowledge At:</strong> ${data.acc_at}</p>
                        <p><strong>Close At:</strong> ${data.close_at}</p>
                        
                        <table border="1">
                            <tr>
                                <th>State</th>
                                <th>Date</th>
                                <th>Detail</th>
                                <th>Attached</th>
                            </tr>
                            <tr>
                                <td>Create</td>
                                <td>${data.created_at}</td>
                                <td>${data.detail}</td>
                                <td><button disabled>Picture</button> <button disabled>File</button></td>
                            </tr>
                            ${data.updates.map(update => `
                                <tr>
                                    <td>Update ${update.update_no}</td>
                                    <td>${update.date}</td>
                                    <td>${update.detail}</td>
                                    <td><button disabled>Picture</button> <button disabled>File</button></td>
                                </tr>
                            `).join('')}
                        </table>
                    `;
                    document.getElementById('viewPopup').style.display = 'block';
                } else {
                    alert("Failed to load case details.");
                }
            });
        }


        function closePopup() {
            document.getElementById('viewPopup').style.display = 'none';
        }

        function recover() {
            const selectedCaseId = document.querySelector('input[name="songSelect"]:checked').value;
            const userName = <?= json_encode($_SESSION['user_name']); ?>;

            if (confirm("Are you sure you want to recover this case?")) {
                fetch("recover_case.php", {
                    method: "POST",
                    headers: { "Content-Type": "application/json" },
                    body: JSON.stringify({ case_id: selectedCaseId, user_name: userName })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert("Case recovered successfully.");
                        window.location.reload();
                    } else {
                        alert("Failed to recover case. " + data.message);
                    }
                });
            }
        }
    </script>
</head>
<body>
    <h2>Recover Cases</h2>

    <!-- Navigation Buttons -->
    <button onclick="location.href='qc.html'">Back</button>
    <button id="recoverButton" onclick="recover()" disabled>Recover</button>
    <button id="viewButton" onclick="showPopup()" disabled>View</button>

    <!-- Song List Table -->
    <table border="1" cellpadding="5" cellspacing="0">
        <tr>
            <th>Select</th>
            <th>Fixer Name</th>
            <th>Case Title</th>
            <th>Place</th>
            <th>Close Date</th>
        </tr>
        <?php foreach ($songs as $song): ?>
            <tr>
                <td><input type="radio" name="songSelect" value="<?= $song['case_id'] ?>" onchange="enableButtons()"></td>
                <td><?= htmlspecialchars($song['fixer_name']) ?></td>
                <td><?= htmlspecialchars($song['case_title']) ?></td>
                <td><?= htmlspecialchars($song['place']) ?></td>
                <td><?= htmlspecialchars($song['close_date']) ?></td>
            </tr>
        <?php endforeach; ?>
    </table>

    <!-- View Popup -->
    <div id="viewPopup" style="display:none;">
        <h3>Case Details</h3>
        <button onclick="closePopup()">Close</button>
        <div id="popupContent">
            <!-- Display additional song_case and case_update details here (similar to previous popups) -->
            <!-- Case information and update details can be fetched dynamically or rendered server-side -->
        </div>
    </div>
</body>
</html>
