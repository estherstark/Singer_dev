<?php
session_start();
include("db_connection.php"); // Include your database connection file here

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accountName = trim($_POST['account']);
    $userName = trim($_POST['user']);
    $password = trim($_POST['password']);

    // Check if the account, user, and password match
    $sql = "SELECT user.user_id, user.role, user.password, account.account_id, user.user_name
            FROM user 
            JOIN account ON user.account_id = account.account_id
            WHERE account.account_name = ? AND user.user_name = ? AND user.status = 1";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$accountName, $userName]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($password, $user['password'])) {
        // Set session variables
        $_SESSION['user_id'] = $user['user_id'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['user_name'] = $user['user_name']; // Store the username in the session

        // Log the login event with a formatted message
        $logMessage = "[Info] User ID {$user['user_id']} had log-in";
        $logSql = "INSERT INTO log (message) VALUES (?)";
        $logStmt = $pdo->prepare($logSql);
        $logStmt->execute([$logMessage]);

        // Redirect to the main page
        header("Location: main_page.php");
        exit();
    } else {
        // Invalid credentials
        echo "<script>alert('Invalid account, username, or password. Please try again.'); window.location.href = 'index.html';</script>";
    }
}
