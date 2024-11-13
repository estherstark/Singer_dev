<?php session_start(); ?>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Main Page</title>
</head>
<body>
    <h2>Welcome to the Main Page</h2>
    <h3>Welcome, <?php echo $_SESSION['user_name']; ?></h3>
    
    <!-- Define the buttons -->
    <button id="singerButton" onclick="location.href='singer.php'">Singer</button>
    <button id="fixerButton" onclick="location.href='fixer.php'">Fixer</button>
    <button id="qcButton" onclick="location.href='qc.html'">QC</button>
    <button onclick="location.href='report.html'">Report</button>
    <button onclick="location.href='logout.php'">Logout</button>

    <script>
        // Simulate fetching the user role from the server
        const userRole = <?php echo json_encode($_SESSION['role']); ?>;

        // Set button states based on the user role
        if (userRole === 1) { // Singer
            document.getElementById('fixerButton').disabled = true;
            document.getElementById('qcButton').disabled = true;
        } else if (userRole === 2) { // Fixer
            document.getElementById('qcButton').disabled = true;
        }
    </script>
</body>
</html>
