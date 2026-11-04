<?php
session_start();
require_once "db_connect.php";

$success_msg = "";
$error_msg   = "";

if (isset($_GET['delete_id'])) {
    $delete_id = intval($_GET['delete_id']);

    if ($delete_id > 0) {
        $del_stmt = $conn->prepare("DELETE FROM users WHERE id = ? AND role = 'student'");
        $del_stmt->bind_param("i", $delete_id);
        
        if ($del_stmt->execute() && $del_stmt->affected_rows > 0) {
            $success_msg = "Student deleted successfully!";
        } else {
            $error_msg = "Failed to delete student, or student not found.";
        }
        $del_stmt->close();
    }
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $action = $_POST['action'] ?? '';
    $name   = trim($_POST['name'] ?? '');
    $email  = trim($_POST['email'] ?? '');
    $mobile = trim($_POST['mobile'] ?? '');
    $course = trim($_POST['course'] ?? '');
    $gender = $_POST['gender'] ?? '';

    if (empty($name) || empty($email) || empty($mobile) || empty($course) || empty($gender)) {
        $error_msg = "All fields are required, including course selection!";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_msg = "Please provide a valid email address!";
    } else {
        if ($action === 'add') {
            $chk_stmt = $conn->prepare("SELECT id, email, mobile FROM users WHERE email = ? OR mobile = ? LIMIT 1");
            $chk_stmt->bind_param("ss", $email, $mobile);
            $chk_stmt->execute();
            $chk_res = $chk_stmt->get_result();

            if ($chk_res && $chk_res->num_rows > 0) {
                $existing = $chk_res->fetch_assoc();
                if ($existing['email'] === $email) {
                    $error_msg = "A student with this email ($email) already exists!";
                } else {
                    $error_msg = "This mobile number ($mobile) is already registered by another student!";
                }
            } else {
                try {
                    $default_pass = password_hash("student123", PASSWORD_DEFAULT);
                    $ins_stmt = $conn->prepare("INSERT INTO users (name, email, mobile, course, gender, password, role) VALUES (?, ?, ?, ?, ?, ?, 'student')");
                    $ins_stmt->bind_param("ssssss", $name, $email, $mobile, $course, $gender, $default_pass);

                    if ($ins_stmt->execute()) {
                        $success_msg = "Student record added successfully for course: " . htmlspecialchars($course);
                    } else {
                        $error_msg = "Database error: " . $conn->error;
                    }
                    $ins_stmt->close();
                } catch (mysqli_sql_exception $e) {
                    if ($e->getCode() == 1062) {
                        $error_msg = "Duplicate entry: A student with this email or mobile number already exists!";
                    } else {
                        $error_msg = "Database error: " . $e->getMessage();
                    }
                }
            }
            $chk_stmt->close();
        } elseif ($action === 'edit') {
            $student_id = intval($_POST['student_id'] ?? 0);

            $chk_stmt = $conn->prepare("SELECT id, email, mobile FROM users WHERE (email = ? OR mobile = ?) AND id != ? LIMIT 1");
            $chk_stmt->bind_param("ssi", $email, $mobile, $student_id);
            $chk_stmt->execute();
            $chk_res = $chk_stmt->get_result();

            if ($chk_res && $chk_res->num_rows > 0) {
                $existing = $chk_res->fetch_assoc();
                if ($existing['email'] === $email) {
                    $error_msg = "This email ($email) is already in use by another user!";
                } else {
                    $error_msg = "This mobile number ($mobile) is already registered by another user!";
                }
            } else {
                try {
                    $upd_stmt = $conn->prepare("UPDATE users SET name = ?, email = ?, mobile = ?, course = ?, gender = ? WHERE id = ? AND role = 'student'");
                    $upd_stmt->bind_param("sssssi", $name, $email, $mobile, $course, $gender, $student_id);

                    if ($upd_stmt->execute()) {
                        $success_msg = "Student record updated successfully!";
                    } else {
                        $error_msg = "Update failed: " . $conn->error;
                    }
                    $upd_stmt->close();
                } catch (mysqli_sql_exception $e) {
                    if ($e->getCode() == 1062) {
                        $error_msg = "Duplicate entry: This email or mobile number is already in use!";
                    } else {
                        $error_msg = "Update failed: " . $e->getMessage();
                    }
                }
            }
            $chk_stmt->close();
        }
    }
}

$edit_student = null;
if (isset($_GET['edit_id'])) {
    $edit_id = intval($_GET['edit_id']);
    $fetch_stmt = $conn->prepare("SELECT id, name, email, mobile, course, gender FROM users WHERE id = ? AND role = 'student'");
    $fetch_stmt->bind_param("i", $edit_id);
    $fetch_stmt->execute();
    $result = $fetch_stmt->get_result();
    if ($result && $result->num_rows === 1) {
        $edit_student = $result->fetch_assoc();
    }
    $fetch_stmt->close();
}

$search_term   = trim($_GET['search'] ?? '');
$filter_course = trim($_GET['course'] ?? '');

$query = "SELECT id, name, email, mobile, course, gender, created_at FROM users WHERE role = 'student'";
$params = [];
$types  = "";

if (!empty($search_term)) {
    $query .= " AND (name LIKE ? OR email LIKE ? OR course LIKE ?)";
    $like = "%" . $search_term . "%";
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
    $types .= "sss";
}

if (!empty($filter_course)) {
    $query .= " AND course = ?";
    $params[] = $filter_course;
    $types .= "s";
}

$query .= " ORDER BY id DESC";

$stmt = $conn->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$students_result = $stmt->get_result();

$course_options = [
    "Computer Science",
    "Information Technology",
    "Artificial Intelligence",
    "Data Science",
    "Mechanical Engineering",
    "Civil Engineering",
    "Business Administration"
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Management - StudentHub</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        .container {
            max-width: 1100px;
            margin: 20px auto;
            padding: 0 15px;
        }
        .card {
            background: #fff;
            padding: 24px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.06);
            margin-bottom: 25px;
            border: 1px solid #dfe3ee;
        }
        .filter-bar {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            margin-bottom: 20px;
            align-items: center;
            background: #f8f9fa;
            padding: 16px;
            border-radius: 8px;
            border: 1px solid #e9ecef;
        }
        .filter-bar input[type="text"], .filter-bar select {
            padding: 8px 12px;
            border: 1px solid #ced4da;
            border-radius: 6px;
            font-size: 0.95rem;
        }
        .filter-bar button {
            padding: 8px 16px;
            background-color: #3654d1;
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
        }
        .filter-bar a.reset-btn {
            padding: 8px 14px;
            background-color: #6c757d;
            color: white;
            border-radius: 6px;
            text-decoration: none;
            font-size: 0.9rem;
        }
        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }
        .data-table th, .data-table td {
            padding: 12px 14px;
            text-align: left;
            border-bottom: 1px solid #e2e8f0;
        }
        .data-table th {
            background-color: #f1f5f9;
            color: #1e293b;
            font-weight: 600;
        }
        .data-table tr:hover {
            background-color: #f8fafc;
        }
        .btn-edit {
            background-color: #f59e0b;
            color: white;
            padding: 5px 10px;
            border-radius: 4px;
            text-decoration: none;
            font-size: 0.85rem;
            margin-right: 5px;
        }
        .btn-delete {
            background-color: #ef4444;
            color: white;
            padding: 5px 10px;
            border-radius: 4px;
            text-decoration: none;
            font-size: 0.85rem;
        }
        .alert-success {
            background-color: #dcfce7;
            color: #166534;
            padding: 12px 16px;
            border-radius: 6px;
            margin-bottom: 20px;
            border: 1px solid #bbf7d0;
            font-weight: 500;
        }
        .alert-error {
            background-color: #fee2e2;
            color: #991b1b;
            padding: 12px 16px;
            border-radius: 6px;
            margin-bottom: 20px;
            border: 1px solid #fecaca;
            font-weight: 500;
        }
        .badge-course {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 0.85rem;
            background: #e0e7ff;
            color: #3730a3;
            font-weight: 600;
        }
        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 15px;
        }
        .form-grid label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
            font-size: 0.9rem;
        }
        .form-grid input, .form-grid select {
            width: 100%;
            padding: 9px;
            border: 1px solid #cbd5e1;
            border-radius: 6px;
        }
    </style>
</head>
<body>

    <header>
        <h1>StudentHub - Student Management System</h1>
        <nav aria-label="Main Navigation">
            <a href="admin.html">Admin Home</a> |
            <a href="dashboard.html">Dashboard</a> |
            <a href="events.html">Events</a> |
            <a href="login.php">Logout</a>
        </nav>
    </header>

    <main class="container">

        <?php if (!empty($success_msg)): ?>
            <div class="alert-success">&#10004; <?php echo htmlspecialchars($success_msg); ?></div>
        <?php endif; ?>

        <?php if (!empty($error_msg)): ?>
            <div class="alert-error">&#9888; <?php echo htmlspecialchars($error_msg); ?></div>
        <?php endif; ?>

        <section class="card">
            <h2><?php echo $edit_student ? 'Edit Student Record' : 'Add New Student'; ?></h2>

            <form action="students.php" method="POST">
                <input type="hidden" name="action" value="<?php echo $edit_student ? 'edit' : 'add'; ?>">
                <?php if ($edit_student): ?>
                    <input type="hidden" name="student_id" value="<?php echo $edit_student['id']; ?>">
                <?php endif; ?>

                <div class="form-grid">
                    <div>
                        <label for="name">Full Name *</label>
                        <input type="text" id="name" name="name" required placeholder="e.g. John Doe"
                               value="<?php echo htmlspecialchars($edit_student['name'] ?? ''); ?>">
                    </div>

                    <div>
                        <label for="email">Email Address *</label>
                        <input type="email" id="email" name="email" required placeholder="e.g. john@example.com"
                               value="<?php echo htmlspecialchars($edit_student['email'] ?? ''); ?>">
                    </div>

                    <div>
                        <label for="mobile">Mobile Number *</label>
                        <input type="tel" id="mobile" name="mobile" required maxlength="10" placeholder="10-digit number"
                               value="<?php echo htmlspecialchars($edit_student['mobile'] ?? ''); ?>">
                    </div>

                    <div>
                        <label for="course">Course *</label>
                        <select id="course" name="course" required>
                            <option value="">-- Select Course --</option>
                            <?php foreach ($course_options as $c): ?>
                                <option value="<?php echo htmlspecialchars($c); ?>"
                                    <?php echo ($edit_student['course'] ?? '') === $c ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($c); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div>
                        <label for="gender">Gender *</label>
                        <select id="gender" name="gender" required>
                            <option value="">-- Select Gender --</option>
                            <option value="male" <?php echo ($edit_student['gender'] ?? '') === 'male' ? 'selected' : ''; ?>>Male</option>
                            <option value="female" <?php echo ($edit_student['gender'] ?? '') === 'female' ? 'selected' : ''; ?>>Female</option>
                            <option value="other" <?php echo ($edit_student['gender'] ?? '') === 'other' ? 'selected' : ''; ?>>Other</option>
                        </select>
                    </div>
                </div>

                <button type="submit" style="padding: 10px 20px; background-color: #3654d1; color: white; border: none; border-radius: 6px; cursor: pointer; font-weight: 600;">
                    <?php echo $edit_student ? 'Update Student' : 'Add Student'; ?>
                </button>

                <?php if ($edit_student): ?>
                    <a href="students.php" style="margin-left: 10px; color: #64748b; text-decoration: underline;">Cancel Edit</a>
                <?php endif; ?>
            </form>
        </section>

        <section class="card">
            <h2>Student Directory</h2>

            <form action="students.php" method="GET" class="filter-bar">
                <input type="text" name="search" placeholder="Search by name, email, or course..." 
                       value="<?php echo htmlspecialchars($search_term); ?>" style="flex: 1; min-width: 240px;">

                <select name="course">
                    <option value="">All Courses</option>
                    <?php foreach ($course_options as $c): ?>
                        <option value="<?php echo htmlspecialchars($c); ?>"
                            <?php echo $filter_course === $c ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($c); ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <button type="submit">Search & Filter</button>

                <?php if (!empty($search_term) || !empty($filter_course)): ?>
                    <a href="students.php" class="reset-btn">Reset</a>
                <?php endif; ?>
            </form>

            <table class="data-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Mobile</th>
                        <th>Course</th>
                        <th>Gender</th>
                        <th>Joined Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($students_result && $students_result->num_rows > 0): ?>
                        <?php while ($row = $students_result->fetch_assoc()): ?>
                            <tr>
                                <td>#<?php echo htmlspecialchars($row['id']); ?></td>
                                <td><strong><?php echo htmlspecialchars($row['name']); ?></strong></td>
                                <td><?php echo htmlspecialchars($row['email']); ?></td>
                                <td><?php echo htmlspecialchars($row['mobile']); ?></td>
                                <td><span class="badge-course"><?php echo htmlspecialchars($row['course'] ?? 'Computer Science'); ?></span></td>
                                <td><?php echo ucfirst(htmlspecialchars($row['gender'])); ?></td>
                                <td><?php echo date("d M Y", strtotime($row['created_at'])); ?></td>
                                <td>
                                    <a href="students.php?edit_id=<?php echo $row['id']; ?>" class="btn-edit">Edit</a>
                                    <a href="students.php?delete_id=<?php echo $row['id']; ?>" class="btn-delete"
                                       onclick="return confirm('Are you sure you want to delete this student record?');">Delete</a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8" style="text-align: center; color: #64748b; padding: 24px;">
                                No student records found matching your search or course filter.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </section>

    </main>

    <footer>
        <p>&copy; 2026 StudentHub</p>
    </footer>

</body>
</html>
