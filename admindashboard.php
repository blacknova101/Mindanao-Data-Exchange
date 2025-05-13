<?php
$totalTransactions = number_format(123132);
$activeUsers = 10;
$uploadedDatasets = number_format(18000);
$pendingApprovals = 45;
$userName = "Onkan";
?>

<!DOCTYPE html>
<html>
<head>
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="style.css">
    <style>
        * {
    box-sizing: border-box;
    font-family: Arial, sans-serif;
    margin: 0;
    padding: 0;
}

body {
    background-color: #e6f2ff;
}

.top-bar {
    background: #555;
    color: white;
    padding: 8px 15px;
    font-size: 12px;
}

.container {
    display: flex;
}

.sidebar {
    width: 220px;
    background: #001f3f;
    color: white;
    min-height: 100vh;
    padding: 20px;
}

.sidebar h2 {
    font-size: 22px;
    margin-bottom: 30px;
}

.sidebar ul {
    list-style: none;
    padding-left: 0;
}

.sidebar ul li {
    padding: 10px 0;
    border-bottom: 1px solid #ccc;
    cursor: pointer;
}

.sidebar ul li.active {
    background: #fff;
    color: #001f3f;
    font-weight: bold;
    padding-left: 10px;
    border-radius: 4px;
}

.main {
    padding: 30px;
    flex-grow: 1;
}

.main h1 {
    font-size: 30px;
    margin-bottom: 30px;
}

.cards {
    display: flex;
    flex-wrap: wrap;
    gap: 20px;
}

.card {
    background: white;
    border: 1px solid #ccc;
    border-radius: 15px;
    padding: 25px;
    width: 220px;
    height: 100px;
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
}

.card strong {
    margin-bottom: 10px;
    font-size: 14px;
}

    </style>
</head>
<body>
    <div class="top-bar">home logged in</div>

    <div class="container">
        <div class="sidebar">
            <h2>Admin Panel</h2>
            <ul>
                <li class="active">Dashboard</li>
                <li>User Management</li>
                <li>Data Uploads & Approvals</li>
                <li>Transactions & Logs</li>
                <li>Reports & Analytics</li>
                <li><a href="admin_logout.php" style="color: white; text-decoration: none;">Logout</a></li>
            </ul>
        </div>

        <div class="main">
            <h1>Welcome, <?= $userName ?></h1>

            <div class="cards">
                <div class="card">
                    <strong>Total Data Transactions</strong>
                    <p><?= $totalTransactions ?></p>
                </div>
                <div class="card">
                    <strong>Active Users</strong>
                    <p><?= $activeUsers ?></p>
                </div>
                <div class="card">
                    <strong>Uploaded Datasets</strong>
                    <p><?= $uploadedDatasets ?></p>
                </div>
                <div class="card">
                    <strong>Pending Approvals</strong>
                    <p><?= $pendingApprovals ?></p>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
