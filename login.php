<?php
session_start();

require_once "db_connect.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $login_input = trim($_POST["username"] ?? $_POST["email"] ?? "");
    $password    = $_POST["password"] ?? "";

    if (strcasecmp($login_input, 'admin') === 0 && $password === 'admin123') {
        $_SESSION["user_id"]   = 1;
        $_SESSION["user_name"] = "Administrator";
        $_SESSION["user_role"] = "admin";
        header("Location: admin_dashboard.php");
        exit;
    }

    if (strcasecmp($login_input, 'student') === 0 && $password === 'student123') {
        $_SESSION["user_id"]   = 2;
        $_SESSION["user_name"] = "Student";
        $_SESSION["user_role"] = "student";
        header("Location: dashboard.html");
        exit;
    }

    if (!empty($login_input) && !empty($password)) {
        $sql = "SELECT id, name, username, email, password, role FROM users WHERE LOWER(username) = LOWER(?) OR LOWER(email) = LOWER(?) LIMIT 1";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ss", $login_input, $login_input);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result && $result->num_rows === 1) {
            $user = $result->fetch_assoc();

            if (password_verify($password, $user["password"])) {
                $_SESSION["user_id"]   = $user["id"];
                $_SESSION["user_name"] = $user["name"];
                $_SESSION["user_role"] = $user["role"];

                if ($user["role"] === "admin") {
                    header("Location: admin_dashboard.php");
                } else {
                    header("Location: dashboard.html");
                }
                exit;
            }
        }

        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - StudentHub</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        .login-container {
            display: flex;
            flex-wrap: wrap;
            gap: 25px;
            justify-content: center;
            align-items: flex-start;
            margin-top: 20px;
        }
        .login-card {
            flex: 1;
            min-width: 280px;
            max-width: 420px;
            background: #fff;
            padding: 24px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            border: 1px solid #e0e0e0;
        }
        .test-details-card {
            flex: 1;
            min-width: 260px;
            max-width: 340px;
            background: #fdfdfd;
            padding: 24px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            border: 1px solid #d0d7de;
            text-align: center;
        }
        .test-details-card h3 {
            margin-top: 0;
            font-size: 1.25rem;
            color: #111;
            font-weight: 700;
            margin-bottom: 20px;
        }
        .test-section {
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px dashed #ccc;
        }
        .test-section:last-child {
            border-bottom: none;
            margin-bottom: 0;
            padding-bottom: 0;
        }
        .test-section h4 {
            margin: 0 0 6px 0;
            font-size: 1.1rem;
            font-weight: 700;
            color: #222;
        }
        .test-section p {
            margin: 4px 0;
            font-size: 0.95rem;
            color: #333;
        }
        .quick-fill-btn {
            margin-top: 8px;
            background-color: #0066cc;
            color: white;
            border: none;
            padding: 6px 12px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.85rem;
        }
        .quick-fill-btn:hover {
            background-color: #004c99;
        }
    </style>
</head>
<body>

    <header>
        <h1>StudentHub Login</h1>
        <nav aria-label="Main navigation">
            <a href="index.html">Home</a>
            <a href="events.html">Events</a>
            <a href="login.php" aria-current="page">Login</a>
            <a href="register.php">Register</a>
        </nav>
    </header>

    <main id="main-content">
        <div class="login-container">

            <section class="login-card">
                <h2>Account Login</h2>

                <form action="login.php" method="POST">
                    <div class="field-row">
                        <label for="username">Username or Email:</label>
                        <input type="text" id="username" name="username" placeholder="e.g. admin or student" required 
                               value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>">
                    </div>

                    <div class="field-row">
                        <label for="password">Password:</label>
                        <input type="password" id="password" name="password" placeholder="Enter your password" required>
                    </div>

                    <br>
                    <button type="submit">Login</button>
                </form>

                <p style="margin-top: 18px;">Don't have an account? <a href="register.php">Register here</a>.</p>
            </section>

            <aside class="test-details-card" aria-label="Test Credentials">
                <h3>Test Login Details</h3>

                <div class="test-section">
                    <h4>Admin</h4>
                    <p>Username: <strong>admin</strong></p>
                    <p>Password: <strong>admin123</strong></p>
                    <button type="button" class="quick-fill-btn" onclick="fillCredentials('admin', 'admin123')">Auto-fill Admin</button>
                </div>

                <div class="test-section">
                    <h4>Student</h4>
                    <p>Username: <strong>student</strong></p>
                    <p>Password: <strong>student123</strong></p>
                    <button type="button" class="quick-fill-btn" onclick="fillCredentials('student', 'student123')">Auto-fill Student</button>
                </div>
            </aside>

        </div>
    </main>

    <footer>
        <p>&copy; 2026 StudentHub | <a href="index.html">Go to Home Page</a></p>
    </footer>

    <script>
        function fillCredentials(user, pass) {
            document.getElementById('username').value = user;
            document.getElementById('password').value = pass;
        }
    </script>

</body>
</html>
