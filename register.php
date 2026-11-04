<?php
require_once "db_connect.php";

$error_message = "";
$success_message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name             = trim($_POST["name"]);
    $email            = trim($_POST["email"]);
    $mobile           = trim($_POST["mobile"]);
    $password         = $_POST["password"];
    $confirm_password = $_POST["confirm_password"];
    $gender           = isset($_POST["gender"]) ? $_POST["gender"] : "";

    if (empty($name) || empty($email) || empty($mobile) || empty($password) || empty($confirm_password) || empty($gender)) {
        $error_message = "All fields are required!";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = "Please enter a valid email address!";
    } elseif ($password !== $confirm_password) {
        $error_message = "Passwords do not match!";
    } elseif (strlen($password) < 6) {
        $error_message = "Password must be at least 6 characters long!";
    } else {
        $check_sql = "SELECT id, email, mobile FROM users WHERE email = ? OR mobile = ? LIMIT 1";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param("ss", $email, $mobile);
        $check_stmt->execute();
        $check_res = $check_stmt->get_result();

        if ($check_res && $check_res->num_rows > 0) {
            $existing = $check_res->fetch_assoc();
            if ($existing['email'] === $email) {
                $error_message = "This email is already registered! Please use another email or login.";
            } else {
                $error_message = "This mobile number is already registered by another student!";
            }
        } else {
            try {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);

                $insert_sql = "INSERT INTO users (name, email, mobile, password, gender, course, role) VALUES (?, ?, ?, ?, ?, 'Computer Science', 'student')";
                $insert_stmt = $conn->prepare($insert_sql);
                $insert_stmt->bind_param("sssss", $name, $email, $mobile, $hashed_password, $gender);

                if ($insert_stmt->execute()) {
                    $success_message = "Registration successful! You can now login.";
                } else {
                    $error_message = "Something went wrong. Please try again: " . $conn->error;
                }

                $insert_stmt->close();
            } catch (mysqli_sql_exception $e) {
                if ($e->getCode() == 1062) {
                    $error_message = "This email or mobile number is already registered!";
                } else {
                    $error_message = "Registration error: " . $e->getMessage();
                }
            }
        }

        $check_stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>StudentHub | Register</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        .alert-error {
            background-color: #ffe6e6;
            color: #d93025;
            padding: 12px;
            border-radius: 6px;
            margin-bottom: 16px;
            border: 1px solid #f5c6cb;
            font-weight: 500;
        }
        .alert-success {
            background-color: #e6f4ea;
            color: #137333;
            padding: 12px;
            border-radius: 6px;
            margin-bottom: 16px;
            border: 1px solid #c3e6cb;
            font-weight: 500;
        }
    </style>
</head>
<body>

    <header>
        <h1>StudentHub</h1>
        <nav aria-label="Main navigation">
            <a href="index.html">Home</a>
            <a href="events.html">Events</a>
            <a href="login.php">Login</a>
            <a href="register.php" aria-current="page">Register</a>
        </nav>
    </header>

    <main id="main-content">
        <section>
            <h2>Student Registration</h2>
            <p>Please fill in this form to create an account.</p>

            <?php if (!empty($error_message)): ?>
                <div class="alert-error"><?php echo htmlspecialchars($error_message); ?></div>
            <?php endif; ?>

            <?php if (!empty($success_message)): ?>
                <div class="alert-success">
                    <?php echo htmlspecialchars($success_message); ?>
                    <br><a href="login.php" style="font-weight:bold; color:#137333;">Click here to Login</a>
                </div>
            <?php endif; ?>

            <form action="register.php" method="POST">

                <div class="field-row">
                    <label for="name">Full Name *</label>
                    <input type="text" id="name" name="name" placeholder="e.g. Manthan Patel" required value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>">
                </div>

                <div class="field-row">
                    <label for="email">Email Address *</label>
                    <input type="email" id="email" name="email" placeholder="e.g. manthan@example.com" required value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                </div>

                <div class="field-row">
                    <label for="mobile">Mobile Number *</label>
                    <input type="tel" id="mobile" name="mobile" placeholder="10-digit mobile number" maxlength="10" required value="<?php echo isset($_POST['mobile']) ? htmlspecialchars($_POST['mobile']) : ''; ?>">
                </div>

                <div class="field-row">
                    <label for="password">Password *</label>
                    <input type="password" id="password" name="password" placeholder="At least 6 characters" required>
                </div>

                <div class="field-row">
                    <label for="confirm_password">Confirm Password *</label>
                    <input type="password" id="confirm_password" name="confirm_password" placeholder="Re-enter password" required>
                </div>

                <fieldset>
                    <legend>Gender *</legend>
                    <label><input type="radio" name="gender" value="male" required> Male</label>
                    <label><input type="radio" name="gender" value="female"> Female</label>
                    <label><input type="radio" name="gender" value="other"> Other</label>
                </fieldset>

                <br>
                <button type="submit">Register</button>
            </form>

            <p>Already have an account? <a href="login.php">Log in here</a>.</p>
        </section>
    </main>

    <footer>
        <p>&copy; 2026 StudentHub. All rights reserved.</p>
    </footer>

</body>
</html>
