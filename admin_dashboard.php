<?php
session_start();
require_once "db_connect.php";

if (!isset($_SESSION["user_role"])) {
    $_SESSION["user_name"] = "Administrator";
    $_SESSION["user_role"] = "admin";
}

$user_role = $_SESSION["user_role"];
$user_name = $_SESSION["user_name"];

if ($user_role !== "admin") {
    header("Location: login.php");
    exit;
}

$student_count = $conn->query("SELECT COUNT(*) AS total FROM students")->fetch_assoc()["total"];
$event_count   = $conn->query("SELECT COUNT(*) AS total FROM events")->fetch_assoc()["total"];
$user_count    = $conn->query("SELECT COUNT(*) AS total FROM users")->fetch_assoc()["total"];
$log_count     = $conn->query("SELECT COUNT(*) AS total FROM audit_logs")->fetch_assoc()["total"];

$recent_students = $conn->query("SELECT id, name, email, course FROM students ORDER BY id DESC LIMIT 5");
$recent_logs     = $conn->query("SELECT id, user_name, action, ip_address, created_at FROM audit_logs ORDER BY id DESC LIMIT 5");
?>
<!DOCTYPE html>
<html>
<head>
    <title>Admin Dashboard</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f0f2f5;
            margin: 0;
            padding: 0;
        }
        header {
            background: #007bff;            color: white;
            padding: 15px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        header h1 {
            margin: 0;
            font-size: 22px;
        }
        nav a {
            color: white;
            text-decoration: none;
            margin-left: 20px;
            font-weight: bold;
        }
        nav a:hover {
            text-decoration: underline;
        }
        .container {
            max-width: 1050px;
            margin: 30px auto;
            padding: 0 20px;
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
            margin-bottom: 30px;
        }
        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            text-align: center;
            border-top: 4px solid #007bff;
        }
        .stat-card h3 {
            margin: 0 0 10px 0;
            color: #555;
            font-size: 15px;
        }
        .stat-card .number {
            font-size: 32px;
            font-weight: bold;
            color: #007bff;
        }
        .section-card {
            background: white;
            padding: 25px;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            margin-bottom: 25px;
        }
        .section-card h2 {
            margin-top: 0;
            font-size: 18px;
            color: #333;
            border-bottom: 2px solid #f0f2f5;
            padding-bottom: 10px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        th {
            background: #007bff;
            color: white;
            padding: 10px;
            text-align: left;
            font-size: 14px;
        }
        td {
            border-bottom: 1px solid #ddd;
            padding: 10px;
            font-size: 14px;
            color: #333;
        }
        .badge {
            background: #28a745;
            color: white;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
        }
    </style>
</head>
<body>

<header>
    <h1>Admin Dashboard (Welcome, <?php echo htmlspecialchars($user_name); ?>)</h1>
    <nav>
        <?php if ($user_role === "admin"): ?>
            <a href="admin_dashboard.php">Dashboard</a>
            <a href="student_api.html">Manage Students</a>
            <a href="events.php">Manage Events</a>
            <a href="login.php">Logout</a>
        <?php else: ?>
            <a href="dashboard.html">My Dashboard</a>
            <a href="events.php">Browse Events</a>
            <a href="login.php">Logout</a>
        <?php endif; ?>
    </nav>
</header>

<div class="container">

    <div class="stats-grid">
        <div class="stat-card">
            <h3>Total Students</h3>
            <div class="number"><?php echo $student_count; ?></div>
        </div>
        <div class="stat-card">
            <h3>Total Events</h3>
            <div class="number"><?php echo $event_count; ?></div>
        </div>
        <div class="stat-card">
            <h3>Registered Users</h3>
            <div class="number"><?php echo $user_count; ?></div>
        </div>
        <div class="stat-card">
            <h3>Audit Actions</h3>
            <div class="number"><?php echo $log_count; ?></div>
        </div>
    </div>

    <div class="section-card">
        <h2>Latest Student Registrations</h2>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Student Name</th>
                    <th>Email Address</th>
                    <th>Course</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $recent_students->fetch_assoc()): ?>
                    <tr>
                        <td>#<?php echo $row["id"]; ?></td>
                        <td><strong><?php echo htmlspecialchars($row["name"]); ?></strong></td>
                        <td><?php echo htmlspecialchars($row["email"]); ?></td>
                        <td><span class="badge"><?php echo htmlspecialchars($row["course"]); ?></span></td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>

    <div class="section-card">
        <h2>System Audit Log (Admin Activities)</h2>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>User</th>
                    <th>Action Performed</th>
                    <th>IP Address</th>
                    <th>Timestamp</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($log = $recent_logs->fetch_assoc()): ?>
                    <tr>
                        <td>#<?php echo $log["id"]; ?></td>
                        <td><?php echo htmlspecialchars($log["user_name"]); ?></td>
                        <td><?php echo htmlspecialchars($log["action"]); ?></td>
                        <td><?php echo htmlspecialchars($log["ip_address"]); ?></td>
                        <td><?php echo $log["created_at"]; ?></td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>

</div>

</body>
</html>

