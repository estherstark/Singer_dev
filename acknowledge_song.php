<?php
session_start();
include("db_connection.php");

header("Content-Type: application/json");

// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Check if the user is logged in and authorized
if (!isset($_SESSION['user_id']) || $_SESSION['role'] < 2) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized access.']);
    exit();
}

// Read JSON input data
$data = json_decode(file_get_contents('php://input'), true);
$case_id = $data['case_id'] ?? null;

if (!$case_id) {
    echo json_encode(['success' => false, 'error' => 'Case ID is required.']);
    exit();
}

$user_id = $_SESSION['user_id'];

try {
    // Update song_case table with acknowledgment details
    $update_query = "
        UPDATE song_case
        SET fixer = :user_id, acc_at = NOW(), status = 1
        WHERE case_id = :case_id AND status = 0";
    $update_stmt = $pdo->prepare($update_query);
    $update_stmt->bindParam(":user_id", $user_id, PDO::PARAM_INT);
    $update_stmt->bindParam(":case_id", $case_id, PDO::PARAM_INT);
    $result = $update_stmt->execute();

    if ($result) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Failed to update the record.']);
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
}
?>
