<?php
session_start();
include("db_connection.php"); // Include your database connection file

// Check if user is logged in and authorized
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] < 2) {
    echo "<script>alert('You are not authorized to access this page. Redirecting to the main page.'); window.location.href = 'main_page.php';</script>";
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

// Fetch count for acknowledge (status = 0, same account_id)
$x = 0;
try {
    $ack_query = "SELECT COUNT(*) FROM song_case WHERE account_id = :account_id AND status = 0";
    $ack_stmt = $pdo->prepare($ack_query);
    $ack_stmt->bindParam(":account_id", $account_id, PDO::PARAM_INT);
    $ack_stmt->execute();
    $x = $ack_stmt->fetchColumn();
} catch (PDOException $e) {
    echo "<script>alert('Database error: " . $e->getMessage() . "');</script>";
    exit();
}

// Fetch count for update (status = 1 or 2, fixer = this user_id)
$y = 0;
try {
    $update_query = "SELECT COUNT(*) FROM song_case WHERE fixer = :user_id AND status IN (1, 2)";
    $update_stmt = $pdo->prepare($update_query);
    $update_stmt->bindParam(":user_id", $user_id, PDO::PARAM_INT);
    $update_stmt->execute();
    $y = $update_stmt->fetchColumn();
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
    <title>Fixer Page</title>
</head>
<body>
    <h2>Welcome to the Fixer Page</h2>

    <!-- Define the buttons -->
    <button onclick="location.href='main_page.php'">Back</button><br><br>

    <button onclick="location.href='song_ack.php'">Acknowledge</button>
    <p><?php echo $x; ?> songs await</p><br>

    <button onclick="location.href='song_update.php'">Update</button>
    <p><?php echo $y; ?> songs await</p>

</body>
</html>
