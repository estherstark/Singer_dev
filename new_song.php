<?php
session_start();
include("db_connection.php");

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    echo "<script>alert('User not logged in. Redirecting to main page.'); window.location.href = 'main_page.php';</script>";
    exit();
}

// Query account_id based on session user_id
$user_id = $_SESSION['user_id'];
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

// Query location list
$locations = [];
try {
    $loc_query = "SELECT loc_id, loc_name FROM location WHERE account_id = :account_id";
    $loc_stmt = $pdo->prepare($loc_query);
    $loc_stmt->bindParam(":account_id", $account_id, PDO::PARAM_INT);
    $loc_stmt->execute();
    $locations = $loc_stmt->fetchAll(PDO::FETCH_ASSOC);
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
    <title>New Song Case</title>
    <script>
        function my_submit() {
            const title = document.getElementById('title').value.trim();
            if (!title) {
                alert("Case title is required.");
                return;
            }

            const loc1 = document.getElementById('loc1').value;
            const loc2 = document.getElementById('loc2').value.trim();
            const scene = loc1 || loc2;

            if (!scene) {
                alert("Please select or enter a location.");
                return;
            }

            const detail = document.getElementById('detail').value.trim();
            if (!detail) {
                alert("Detail is required.");
                return;
            }

            const isMaskSinger = document.getElementById('toggle-mask').checked;
            const mask = isMaskSinger ? 1 : 0;

            const formData = new FormData();
            formData.append("title", title);
            formData.append("scene", scene);
            formData.append("detail", detail);
            formData.append("mask", mask);

            fetch("new_song_handler.php", {
                method: "POST",
                body: formData
            })
            .then(response => response.json())
            .then(result => {
                if (result.success) {
                    alert("New song case created successfully!");
                    window.location.href = "singer.php";
                } else {
                    alert("Error: " + (result.error || "Unknown error"));
                }
            })
            .catch(error => {
                alert("An error occurred: " + error.message);
            });
        }
    </script>
</head>
<body>
    <h1>New Song Case</h1>
    <form>
        <label for="title">Case Title:</label>
        <input type="text" id="title" name="title" required>

        <label for="loc1">Location:</label>
        <select id="loc1" name="loc1">
            <option value="">Select a location</option>
            <?php foreach ($locations as $location): ?>
                <option value="<?= htmlspecialchars($location['loc_name']) ?>"><?= htmlspecialchars($location['loc_name']) ?></option>
            <?php endforeach; ?>
        </select>

        <label for="loc2">Or Enter Location:</label>
        <input type="text" id="loc2" name="loc2">

        <label for="detail">Detail:</label>
        <textarea id="detail" name="detail" required></textarea>

        <button type="button" onclick="alert('Camera functionality is disabled');" disabled>Camera</button>
        <input type="file" id="file" name="file" disabled>

        <label for="toggle-mask">Singer Mask:</label>
        <input type="checkbox" id="toggle-mask" name="toggle-mask">

        <button type="button" onclick="my_submit()">Submit</button>
        <button type="button" onclick="window.location.href='singer.php'">Cancel</button>
    </form>
</body>
</html>
