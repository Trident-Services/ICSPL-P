<?php
session_start();

// Auto logout logic
$timeout_duration = 1200; // 20 minutes
if (isset($_SESSION['LAST_ACTIVITY']) && (time() - $_SESSION['LAST_ACTIVITY']) > $timeout_duration) {
    session_unset();
    session_destroy();
    header("Location: login?timeout=1");
    exit();
}
$_SESSION['LAST_ACTIVITY'] = time();

// Check login
if (!isset($_SESSION["admin"])) {
    header("Location: /login");
    exit();
}
if (isset($_GET["logout"])) {
    session_destroy();
    header("Location: /logout");
    exit();
}

$conn = new mysqli("localhost", "root", "root", "icspl");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$sql = "SELECT id, name, phone_number, gmail FROM users";
$result = $conn->query($sql);
$count_sql = "SELECT COUNT(*) as total_users FROM users";
$count_result = $conn->query($count_sql);
$total_users = 0;
if ($count_result && $count_result->num_rows > 0) {
    $row = $count_result->fetch_assoc();
    $total_users = $row['total_users'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>User List</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            background-color: #0f172a;
            color: #f8fafc;
            font-family: 'Inter', sans-serif;
            padding-bottom: 50px;
        }
        .header {
            background-color: #1e293b;
            padding: 20px 40px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 3px solid #334155;
            flex-wrap: wrap;
        }
        .admin-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        .admin-info img {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid #94a3b8;
        }
        .nav-links {
            display: flex;
            gap: 25px;
            align-items: center;
            flex-wrap: wrap;
        }
        .nav-links a {
            color: #f8fafc;
            text-decoration: none;
            font-weight: 600;
            position: relative;
            padding: 10px 6px;
            transition: color 0.3s ease;
        }
        .nav-links a::after {
            content: "";
            position: absolute;
            width: 0%;
            height: 3px;
            bottom: 0;
            left: 0;
            background-color: #3b82f6;
            transition: width 0.3s ease;
            border-radius: 2px;
        }
        .nav-links a:hover {
            color: #38bdf8;
        }
        .nav-links a:hover::after {
            width: 100%;
        }
        .nav-links .logout-btn {
            background-color: #ef4444;
            color: white !important;
            padding: 10px 16px;
            border-radius: 8px;
            transition: background-color 0.3s ease;
        }
        .nav-links .logout-btn:hover {
            background-color: #dc2626;
        }
        h1 {
            text-align: center;
            margin: 40px 0 10px;
            font-size: 32px;
        }
        .user-count {
            text-align: center;
            font-size: 18px;
            margin-bottom: 20px;
        }
        .chart-container {
            max-width: 500px;
            margin: 0 auto 30px;
        }
        .table-container {
            width: 98%;
            max-width: 1400px;
            margin: 0 auto 40px;
            background-color: #1e293b;
            border-radius: 12px;
            overflow-x: auto;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.5);
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            padding: 16px;
            text-align: left;
            border-bottom: 1px solid #334155;
        }
        th {
            background-color: #0f172a;
            font-weight: 600;
            text-transform: uppercase;
            color: #38bdf8;
        }
        tr:hover td {
            background-color: #334155;
        }
        td {
            color: #f1f5f9;
        }
        @media screen and (max-width: 600px) {
            h1 { font-size: 24px; }
        }
    </style>
</head>
<body>
<div class="header">
    <div class="admin-info">
        <img src="https://img.freepik.com/premium-photo/3d-sales-manager-character-leading-with-animated-ambition_893571-11254.jpg" alt="Admin">
        <strong>Welcome, <?php echo htmlspecialchars($_SESSION["admin"]); ?></strong>
    </div>
    <nav class="nav-links">
        <a href="/admin">Dashboard</a>
        <a href="/upload">Uploaded Files</a>
        <a href="/users">User List</a>
        <a href="?/logout=true" class="logout-btn">Logout</a>
    </nav>
</div>

<h1>👥 Registered Users</h1>
<p class="user-count">Total Users: <?php echo $total_users; ?></p>
<div class="chart-container">
    <canvas id="userChart"></canvas>
</div>
<div class="table-container">
    <table>
        <thead>
        <tr>
            <th>ID</th>
            <th>Name</th>
            <th>Phone Number</th>
            <th>Email</th>
        </tr>
        </thead>
        <tbody>
        <?php
        if ($result->num_rows > 0) {
            while ($user = $result->fetch_assoc()) {
                echo "<tr>
                        <td>" . htmlspecialchars($user['id']) . "</td>
                        <td>" . htmlspecialchars($user['name']) . "</td>
                        <td>" . htmlspecialchars($user['phone_number']) . "</td>
                        <td>" . htmlspecialchars($user['gmail']) . "</td>
                      </tr>";
            }
        } else {
            echo "<tr><td colspan='4' style='text-align:center;'>No users found.</td></tr>";
        }
        ?>
        </tbody>
    </table>
</div>
<script>
const labels = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
const data = {
    labels: labels,
    datasets: [{
        label: 'New Users This Week',
        backgroundColor: '#3b82f6',
        borderColor: '#60a5fa',
        data: [2, 4, 3, 5, 1, 6, 4],
        fill: true,
        tension: 0.4
    }]
};
const config = {
    type: 'line',
    data: data,
    options: {
        responsive: true,
        plugins: {
            legend: {
                display: false
            }
        },
        scales: {
            x: {
                ticks: { color: '#cbd5e1' }
            },
            y: {
                ticks: { color: '#cbd5e1' }
            }
        }
    }
};
new Chart(document.getElementById('userChart'), config);
</script>
</body>
</html>