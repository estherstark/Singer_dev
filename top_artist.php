<?php
session_start();
include("db_connection.php");

// Check if the user is logged in and has an account ID
$account_id = $_SESSION['account_id'] ?? null;
if (!$account_id) {
    echo "<script>alert('Unauthorized access. Redirecting to main page.'); window.location.href = 'main_page.php';</script>";
    exit();
}

// Fetch distinct month-year values for the dropdown
$month_years = [];
try {
    $query = "SELECT DISTINCT DATE_FORMAT(created_at, '%Y-%m') AS month_year FROM song_case WHERE account_id = :account_id ORDER BY month_year DESC";
    $stmt = $pdo->prepare($query);
    $stmt->execute([':account_id' => $account_id]);
    $month_years = $stmt->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $e) {
    echo "<script>alert('Database error: " . $e->getMessage() . "');</script>";
    exit();
}

// Default to the latest month
$selected_month = $_GET['month_year'] ?? $month_years[0];

// Fetch data for Top Vocalist Table
$top_vocalists = [];
try {
    $query = "
        SELECT COALESCE(u.user_name, 'The Mask Singer') AS user_name, 
               SUM(c.score) AS total_score, 
               COUNT(c.user_id) AS total_song
        FROM song_case c
        LEFT JOIN user u ON c.user_id = u.user_id
        WHERE c.account_id = :account_id AND DATE_FORMAT(c.created_at, '%Y-%m') = :selected_month
        GROUP BY user_name WITH ROLLUP";
    $stmt = $pdo->prepare($query);
    $stmt->execute([':account_id' => $account_id, ':selected_month' => $selected_month]);
    $top_vocalists = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    echo "<script>alert('Database error: " . $e->getMessage() . "');</script>";
    exit();
}

// Fetch data for Top Guitarist Table
$top_guitarists = [];
try {
    $query = "
        SELECT COALESCE(f.user_name, 'Total') AS fixer_name, 
               SUM(c.fix_score) AS total_score, 
               COUNT(c.fixer) AS total_song,
               COUNT(CASE WHEN c.close_at IS NOT NULL THEN c.fixer END) AS total_close_song,
               AVG(DATEDIFF(c.acc_at, c.created_at)) AS avg_accept_date,
               AVG(CASE WHEN c.close_at IS NOT NULL THEN DATEDIFF(c.close_at, c.created_at) END) AS avg_close_date
        FROM song_case c
        LEFT JOIN user f ON c.fixer = f.user_id
        WHERE c.account_id = :account_id AND DATE_FORMAT(c.created_at, '%Y-%m') = :selected_month
        GROUP BY fixer_name WITH ROLLUP";
    $stmt = $pdo->prepare($query);
    $stmt->execute([':account_id' => $account_id, ':selected_month' => $selected_month]);
    $top_guitarists = $stmt->fetchAll(PDO::FETCH_ASSOC);
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
    <title>Top Artist Report</title>
    <script>
        function updateTable() {
            const selectedMonth = document.getElementById('monthDropdown').value;
            window.location.href = 'top_artist.php?month_year=' + selectedMonth;
        }
    </script>
</head>
<body>
    <h2>Top Artist Report</h2>

    <!-- Back Button -->
    <button onclick="location.href='report.html'">Back</button>

    <!-- Month Dropdown -->
    <label for="monthDropdown">Month:</label>
    <select id="monthDropdown" onchange="updateTable()">
        <?php foreach ($month_years as $month): ?>
            <option value="<?= htmlspecialchars($month) ?>" <?= $selected_month === $month ? 'selected' : '' ?>>
                <?= htmlspecialchars($month) ?>
            </option>
        <?php endforeach; ?>
    </select>

    <!-- Top Vocalist Table -->
    <h3>Top Vocalist</h3>
    <table border="1" cellpadding="5" cellspacing="0">
        <tr>
            <th>User Name</th>
            <th>Total Score</th>
            <th>Total Songs</th>
        </tr>
        <?php foreach ($top_vocalists as $vocalist): ?>
            <tr>
                <td><?= htmlspecialchars($vocalist['user_name'] ?? 'Total') ?></td>
                <td><?= htmlspecialchars($vocalist['total_score']) ?></td>
                <td><?= htmlspecialchars($vocalist['total_song']) ?></td>
            </tr>
        <?php endforeach; ?>
    </table>

    <br><br><br>

    <!-- Top Guitarist Table -->
    <h3>Top Guitarist</h3>
    <table border="1" cellpadding="5" cellspacing="0">
        <tr>
            <th>Fixer Name</th>
            <th>Total Score</th>
            <th>Total Songs</th>
            <th>Total Closed Songs</th>
            <th>Avg Accept Date (days)</th>
            <th>Avg Close Date (days)</th>
        </tr>
        <?php foreach ($top_guitarists as $guitarist): ?>
            <tr>
                <td><?= htmlspecialchars($guitarist['fixer_name']) ?></td>
                <td><?= htmlspecialchars($guitarist['total_score']) ?></td>
                <td><?= htmlspecialchars($guitarist['total_song']) ?></td>
                <td><?= htmlspecialchars($guitarist['total_close_song']) ?></td>
                <td><?= is_null($guitarist['avg_accept_date']) ? '-' : round($guitarist['avg_accept_date'], 2) ?></td>
                <td><?= is_null($guitarist['avg_close_date']) ? '-' : round($guitarist['avg_close_date'], 2) ?></td>
            </tr>
        <?php endforeach; ?>
    </table>
</body>
</html>
