<?php
session_start();
include("db_connection.php");

if (!isset($_SESSION['user_id'])) {
    echo "<script>alert('Unauthorized access. Redirecting to main page.'); window.location.href = 'main_page.php';</script>";
    exit();
}

$perPage = 20;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$start = ($page - 1) * $perPage;
$totalPages = 1;

// Fetch filtered song cases based on conditions and pagination
$conditions = "c.status IN (1, 2, 3)";
$params = [];

// Apply filters if set (filters sent via GET parameters)
if (!empty($_GET['user_name'])) {
    $conditions .= " AND u.user_name LIKE :user_name";
    $params[':user_name'] = '%' . $_GET['user_name'] . '%';
}
if (!empty($_GET['fixer_name'])) {
    $conditions .= " AND f.user_name LIKE :fixer_name";
    $params[':fixer_name'] = '%' . $_GET['fixer_name'] . '%';
}
if (!empty($_GET['case_title'])) {
    $conditions .= " AND c.case_title LIKE :case_title";
    $params[':case_title'] = '%' . $_GET['case_title'] . '%';
}
if (!empty($_GET['place'])) {
    $conditions .= " AND c.place LIKE :place";
    $params[':place'] = '%' . $_GET['place'] . '%';
}
if (!empty($_GET['status'])) {
    $conditions .= " AND c.status = :status";
    $params[':status'] = $_GET['status'];
}

// Fetch paginated data with filters
try {
    // Fetch total count for pagination
    $count_query = "SELECT COUNT(*) FROM song_case c LEFT JOIN user u ON c.user_id = u.user_id LEFT JOIN user f ON c.fixer = f.user_id WHERE $conditions";
    $count_stmt = $pdo->prepare($count_query);
    $count_stmt->execute($params);
    $totalItems = $count_stmt->fetchColumn();
    $totalPages = ceil($totalItems / $perPage);

    // Fetch paginated list
    $query = "
        SELECT c.case_id, 
               COALESCE(u.user_name, 'The Mask Singer') AS user_name, 
               COALESCE(f.user_name, 'N/A') AS fixer_name, 
               c.case_title, 
               c.place, 
               c.status, 
               DATE(c.created_at) AS created_date, 
               DATE(c.close_at) AS close_date
        FROM song_case c
        LEFT JOIN user u ON c.user_id = u.user_id
        LEFT JOIN user f ON c.fixer = f.user_id
        WHERE $conditions
        ORDER BY c.created_at DESC
        LIMIT :start, :perPage";
    $stmt = $pdo->prepare($query);
    foreach ($params as $key => &$value) {
        $stmt->bindParam($key, $value);
    }
    $stmt->bindParam(':start', $start, PDO::PARAM_INT);
    $stmt->bindParam(':perPage', $perPage, PDO::PARAM_INT);
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
    <title>Scoring Page</title>
    <style>
        #filterPanel {
            display: none;
            margin-bottom: 20px;
        }
    </style>
    <script>
        function toggleFilterPanel() {
            const filterPanel = document.getElementById('filterPanel');
            filterPanel.style.display = filterPanel.style.display === 'none' ? 'block' : 'none';
        }

        function applyFilters() {
            document.getElementById('filterForm').submit();
        }

        function clearFilters() {
            document.querySelectorAll('#filterForm input, #filterForm select').forEach(input => input.value = '');
            document.getElementById('filterForm').submit();
        }

        function enableViewButton() {
            document.getElementById('viewButton').disabled = false;
        }
        function viewSelectedSong() {
            const selectedSong = document.querySelector('input[name="songSelect"]:checked');
            if (selectedSong) {
                document.getElementById('selectedCaseId').value = selectedSong.value;
                document.getElementById('viewForm').submit();
            } else {
                alert('Please select a song to view.');
            }
        }
    </script>
</head>
<body>
    <h2>Scoring Page</h2>

    <!-- Navigation Buttons -->
    <button onclick="location.href='qc.html'">Back</button>
    <!-- View and Toggle Filter Buttons -->
    <!-- Form for POST request to qc_score.php -->
    <form id="viewForm" action="qc_score.php" method="POST" style="display: none;">
        <input type="hidden" name="case_id" id="selectedCaseId">
    </form>

    <button id="viewButton" onclick="viewSelectedSong()" disabled>View</button>
    <button onclick="toggleFilterPanel()">Toggle Filter</button>
    <div id="filterPanel">
        <form id="filterForm" method="GET">
            <label for="user_name">User Name:</label>
            <input type="text" name="user_name" value="<?= htmlspecialchars($_GET['user_name'] ?? '') ?>"><br>

            <label for="fixer_name">Fixer Name:</label>
            <input type="text" name="fixer_name" value="<?= htmlspecialchars($_GET['fixer_name'] ?? '') ?>"><br>

            <label for="case_title">Case Title:</label>
            <input type="text" name="case_title" value="<?= htmlspecialchars($_GET['case_title'] ?? '') ?>"><br>

            <label for="place">Place:</label>
            <input type="text" name="place" value="<?= htmlspecialchars($_GET['place'] ?? '') ?>"><br>

            <label for="status">Status:</label>
            <select name="status">
                <option value="">All</option>
                <option value="1" <?= isset($_GET['status']) && $_GET['status'] == "1" ? "selected" : "" ?>>Acknowledge</option>
                <option value="2" <?= isset($_GET['status']) && $_GET['status'] == "2" ? "selected" : "" ?>>Ongoing</option>
                <option value="3" <?= isset($_GET['status']) && $_GET['status'] == "3" ? "selected" : "" ?>>Close</option>
            </select><br><br>

            <button type="button" onclick="applyFilters()">Apply Filters</button>
            <button type="button" onclick="clearFilters()">Clear Filters</button>
        </form>
    </div>

    <!-- Song List Table -->
    <table border="1" cellpadding="5" cellspacing="0">
        <tr>
            <th>Select</th>
            <th>User Name</th>
            <th>Fixer Name</th>
            <th>Case Title</th>
            <th>Place</th>
            <th>Status</th>
            <th>Create Date</th>
            <th>Close Date</th>
        </tr>
        <?php foreach ($songs as $song): ?>
            <tr>
                <td><input type="radio" name="songSelect" value="<?= $song['case_id'] ?>" onchange="enableViewButton()"></td>
                <td><?= htmlspecialchars($song['user_name']) ?></td>
                <td><?= htmlspecialchars($song['fixer_name']) ?></td>
                <td><?= htmlspecialchars($song['case_title']) ?></td>
                <td><?= htmlspecialchars($song['place']) ?></td>
                <td><?= $song['status'] == 1 ? 'Acknowledge' : ($song['status'] == 2 ? 'Ongoing' : 'Close') ?></td>
                <td><?= htmlspecialchars($song['created_date']) ?></td>
                <td><?= htmlspecialchars($song['close_date']) ?></td>
            </tr>
        <?php endforeach; ?>
    </table>

    <!-- Pagination -->
    <div>
        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
            <a href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>"><?= $i ?></a>
        <?php endfor; ?>
    </div>
</body>
</html>
