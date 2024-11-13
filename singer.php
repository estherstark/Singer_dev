<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Singer Page</title>
    <style>
        /* Style for list and toggle button area */
        #caseList {
            margin-top: 20px;
        }
        .case-item {
            border-bottom: 1px solid #ccc;
            padding: 10px;
        }
    </style>
</head>
<body>
    <h2>Singer Page</h2>

    <!-- Navigation buttons -->
    <button onclick="location.href='main_page.php'">Back</button>
    <button onclick="location.href='new_song.php'">Create New Song</button>
    <button id="viewButton" onclick="viewSong()" disabled>View</button>
    <button id="cancelButton" onclick="songCancel()" disabled>Cancel</button>

    <!-- Toggle button -->
    <select id="toggleView" onchange="loadCases()">
        <option value="current">Current</option>
        <option value="all_case">All Case</option>
        <option value="all_user_case" id="allUserOption" hidden>All User Case</option>
    </select>

    <!-- List of cases based on toggle condition -->
    <div id="caseList">
        <!-- Cases will be dynamically loaded here -->
    </div>
    <?php session_start(); ?>
    <script>
        // Sample user role (replace this with actual session data in a real app)
        const userRole = <?php echo json_encode($_SESSION['role']); ?>;
        const userId = <?php echo json_encode($_SESSION['user_id']); ?>;

        // Toggle "All User Case" option for admin only
        if (userRole === 4) {
            document.getElementById('allUserOption').hidden = false;
        }

        // Load cases based on the selected view
        function loadCases() {
            const viewType = document.getElementById('toggleView').value;
            const caseList = document.getElementById('caseList');
            caseList.innerHTML = '';  // Clear the current list

            // Fetch data from the backend (replace 'fetch_cases.php' with your backend route)
            fetch(`fetch_cases.php?viewType=${viewType}&userId=${userId}`)
                .then(response => response.json())
                .then(data => {
                    data.forEach(caseItem => {
                        const caseElement = document.createElement('div');
                        caseElement.classList.add('case-item');
                        const createDate = caseItem.created_at.split(' ')[0]; // Get only the date part
                        const userName = caseItem.user_name || "The Mask Singer"; // Default to "The Mask Singer" if no user name
                        caseElement.innerHTML = `
                            <header>Detail</header>
                            <input type="radio" name="caseSelect" value="${caseItem.case_id}" 
                                   onchange="updateButtonStates(${caseItem.status})">
                            <span><strong>User:</strong> ${userName}</span><br>
                            <span><strong>Title:</strong> ${caseItem.case_title}</span><br>
                            <span><strong>Create Date:</strong> ${createDate}</span><br>
                            <span><strong>Supporter:</strong> ${caseItem.fixer_name || 'N/A'}</span><br>
                            <span><strong>Acknowledge Date:</strong> ${caseItem.acc_at || 'N/A'}</span><br>
                            <span><strong>Status:</strong> ${getStatusText(caseItem.status)}</span>
                        `;
                        caseList.appendChild(caseElement);
                    });
                });
        }

        // Update button states based on case status
        function updateButtonStates(status) {
            document.getElementById('viewButton').disabled = false;
            document.getElementById('cancelButton').disabled = !(status === 0 || status === 1 || status === 2);
        }

        // Function to get readable status text
        function getStatusText(status) {
            const statusText = ["Create", "Acknowledge", "Ongoing", "Close", "Cancel", "Force Close"];
            return statusText[status] || "Unknown";
        }

        // Function to view the selected song
        function viewSong() {
            const selectedCaseId = document.querySelector('input[name="caseSelect"]:checked').value;
            if (selectedCaseId) {
                // Use a form to send the case ID via POST
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = 'view.php';

                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'case_id';
                input.value = selectedCaseId;

                form.appendChild(input);
                document.body.appendChild(form);
                form.submit();
            }
        }


        // Function to cancel a song case
        function songCancel() {
            const selectedCaseId = document.querySelector('input[name="caseSelect"]:checked').value;
            const caseTitle = document.querySelector(`input[name="caseSelect"]:checked`)
                .nextElementSibling.textContent.split(": ")[1];
            
            if (confirm(`Do you want to confirm this case "${caseTitle}"?`)) {
                // Send request to cancel the case (replace 'cancel_case.php' with your backend route)
                fetch(`cancel_case.php`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ case_id: selectedCaseId })
                }).then(response => response.json())
                  .then(data => {
                      if (data.success) {
                          alert("Case canceled successfully");
                          loadCases();  // Refresh the list after canceling
                      } else {
                          alert("Failed to cancel case. Please try again.");
                      }
                  });
            }
        }

        // Initial load
        loadCases();
    </script>
</body>
</html>
