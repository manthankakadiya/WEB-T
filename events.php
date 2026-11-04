<?php
session_start();
require_once "db_connect.php";

$success_msg = "";
$error_msg   = "";

$upload_dir = __DIR__ . "/uploads/posters/";
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0755, true);
}

if (isset($_GET['delete_id'])) {
    $delete_id = intval($_GET['delete_id']);

    if ($delete_id > 0) {
        $get_stmt = $conn->prepare("SELECT poster FROM events WHERE id = ?");
        $get_stmt->bind_param("i", $delete_id);
        $get_stmt->execute();
        $res = $get_stmt->get_result();

        if ($res && $row = $res->fetch_assoc()) {
            if (!empty($row['poster']) && file_exists($upload_dir . $row['poster'])) {
                unlink($upload_dir . $row['poster']);
            }
        }
        $get_stmt->close();

        $del_stmt = $conn->prepare("DELETE FROM events WHERE id = ?");
        $del_stmt->bind_param("i", $delete_id);
        if ($del_stmt->execute() && $del_stmt->affected_rows > 0) {
            $success_msg = "Event and associated poster deleted successfully!";
        } else {
            $error_msg = "Failed to delete event, or event not found.";
        }
        $del_stmt->close();
    }
}

function upload_event_poster($file, &$error_msg, $upload_dir) {
    if (!isset($file) || $file['error'] === UPLOAD_ERR_NO_FILE) {
        return null;
    }

    if ($file['error'] !== UPLOAD_ERR_OK) {
        $error_msg = "File upload failed with error code: " . $file['error'];
        return false;
    }

    $max_size = 2 * 1024 * 1024;
    if ($file['size'] > $max_size) {
        $error_msg = "File is too large! Maximum allowed poster size is 2 MB.";
        return false;
    }

    $allowed_extensions = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
    $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($file_ext, $allowed_extensions)) {
        $error_msg = "Invalid file type! Only JPG, JPEG, PNG, WEBP, and GIF images are allowed.";
        return false;
    }

    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime_type = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    $allowed_mimes = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
    if (!in_array($mime_type, $allowed_mimes)) {
        $error_msg = "Security alert: The uploaded file is not a valid image!";
        return false;
    }

    $new_filename = "poster_" . time() . "_" . bin2hex(random_bytes(4)) . "." . $file_ext;
    $destination = $upload_dir . $new_filename;

    if (move_uploaded_file($file['tmp_name'], $destination)) {
        return $new_filename;
    } else {
        $error_msg = "Failed to save uploaded poster file.";
        return false;
    }
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $action      = $_POST['action'] ?? '';
    $name        = trim($_POST['name'] ?? '');
    $category    = trim($_POST['category'] ?? '');
    $event_date  = trim($_POST['event_date'] ?? '');
    $description = trim($_POST['description'] ?? '');

    if (empty($name) || empty($category) || empty($event_date)) {
        $error_msg = "Event Name, Category, and Date are required!";
    } else {
        if ($action === 'add') {
            $uploaded_poster = null;
            if (isset($_FILES['poster']) && $_FILES['poster']['error'] !== UPLOAD_ERR_NO_FILE) {
                $uploaded_poster = upload_event_poster($_FILES['poster'], $error_msg, $upload_dir);
                if ($uploaded_poster === false) {
                    goto end_post_handling;
                }
            }

            $ins_stmt = $conn->prepare("INSERT INTO events (name, category, event_date, description, poster) VALUES (?, ?, ?, ?, ?)");
            $ins_stmt->bind_param("sssss", $name, $category, $event_date, $description, $uploaded_poster);

            if ($ins_stmt->execute()) {
                $success_msg = "Event record and poster added successfully!";
            } else {
                $error_msg = "Database error: " . $conn->error;
            }
            $ins_stmt->close();
        } elseif ($action === 'edit') {
            $event_id = intval($_POST['event_id'] ?? 0);
            $existing_poster = $_POST['existing_poster'] ?? null;
            $poster_to_save = $existing_poster;

            if (isset($_FILES['poster']) && $_FILES['poster']['error'] !== UPLOAD_ERR_NO_FILE) {
                $new_poster = upload_event_poster($_FILES['poster'], $error_msg, $upload_dir);
                if ($new_poster === false) {
                    goto end_post_handling;
                }
                
                if (!empty($existing_poster) && file_exists($upload_dir . $existing_poster)) {
                    unlink($upload_dir . $existing_poster);
                }
                $poster_to_save = $new_poster;
            }

            $upd_stmt = $conn->prepare("UPDATE events SET name = ?, category = ?, event_date = ?, description = ?, poster = ? WHERE id = ?");
            $upd_stmt->bind_param("sssssi", $name, $category, $event_date, $description, $poster_to_save, $event_id);

            if ($upd_stmt->execute()) {
                $success_msg = "Event record updated successfully!";
            } else {
                $error_msg = "Failed to update event: " . $conn->error;
            }
            $upd_stmt->close();
        }
    }
}
end_post_handling:

$edit_event = null;
if (isset($_GET['edit_id'])) {
    $edit_id = intval($_GET['edit_id']);
    $fetch_stmt = $conn->prepare("SELECT id, name, category, event_date, description, poster FROM events WHERE id = ?");
    $fetch_stmt->bind_param("i", $edit_id);
    $fetch_stmt->execute();
    $result = $fetch_stmt->get_result();
    if ($result && $result->num_rows === 1) {
        $edit_event = $result->fetch_assoc();
    }
    $fetch_stmt->close();
}

$search_query = trim($_GET['search'] ?? '');
$filter_cat   = trim($_GET['category'] ?? '');

$query = "SELECT id, name, category, event_date, description, poster FROM events WHERE 1=1";
$params = [];
$types  = "";

if (!empty($search_query)) {
    $query .= " AND (name LIKE ? OR description LIKE ?)";
    $like = "%" . $search_query . "%";
    $params[] = $like;
    $params[] = $like;
    $types .= "ss";
}

if (!empty($filter_cat) && in_array($filter_cat, ['Workshop', 'Competition', 'Seminar', 'Other'])) {
    $query .= " AND category = ?";
    $params[] = $filter_cat;
    $types .= "s";
}

$query .= " ORDER BY event_date ASC";

$stmt = $conn->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$events_result = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Event Management System - StudentHub</title>
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
            vertical-align: middle;
        }
        .data-table th {
            background-color: #f1f5f9;
            color: #1e293b;
            font-weight: 600;
        }
        .data-table tr:hover {
            background-color: #f8fafc;
        }
        .poster-thumb {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 6px;
            border: 1px solid #cbd5e1;
            background-color: #f1f5f9;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.75rem;
            color: #64748b;
        }
        .btn-edit {
            background-color: #f59e0b;
            color: white;
            padding: 6px 12px;
            border-radius: 4px;
            text-decoration: none;
            font-size: 0.85rem;
            margin-right: 5px;
        }
        .btn-delete {
            background-color: #ef4444;
            color: white;
            padding: 6px 12px;
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
        .badge-cat {
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
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 15px;
            margin-bottom: 15px;
        }
        .form-grid label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
            font-size: 0.9rem;
        }
        .form-grid input, .form-grid select, .form-grid textarea {
            width: 100%;
            padding: 9px;
            border: 1px solid #cbd5e1;
            border-radius: 6px;
        }
    </style>
</head>
<body>

    <header>
        <h1>StudentHub - Event Management System</h1>
        <nav aria-label="Main Navigation">
            <a href="admin.html">Admin Home</a> |
            <a href="students.php">Students</a> |
            <a href="events.php" aria-current="page">Events</a> |
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
            <h2><?php echo $edit_event ? 'Edit Event Record' : 'Add New Event'; ?></h2>

            <form action="events.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" value="<?php echo $edit_event ? 'edit' : 'add'; ?>">
                <?php if ($edit_event): ?>
                    <input type="hidden" name="event_id" value="<?php echo $edit_event['id']; ?>">
                    <input type="hidden" name="existing_poster" value="<?php echo htmlspecialchars($edit_event['poster'] ?? ''); ?>">
                <?php endif; ?>

                <div class="form-grid">
                    <div>
                        <label for="name">Event Name *</label>
                        <input type="text" id="name" name="name" required placeholder="e.g. AI & Robotics Hackathon"
                               value="<?php echo htmlspecialchars($edit_event['name'] ?? ''); ?>">
                    </div>

                    <div>
                        <label for="category">Category *</label>
                        <select id="category" name="category" required>
                            <option value="Workshop" <?php echo ($edit_event['category'] ?? '') === 'Workshop' ? 'selected' : ''; ?>>Workshop</option>
                            <option value="Competition" <?php echo ($edit_event['category'] ?? '') === 'Competition' ? 'selected' : ''; ?>>Competition</option>
                            <option value="Seminar" <?php echo ($edit_event['category'] ?? '') === 'Seminar' ? 'selected' : ''; ?>>Seminar</option>
                            <option value="Other" <?php echo ($edit_event['category'] ?? '') === 'Other' ? 'selected' : ''; ?>>Other</option>
                        </select>
                    </div>

                    <div>
                        <label for="event_date">Event Date *</label>
                        <input type="date" id="event_date" name="event_date" required
                               value="<?php echo htmlspecialchars($edit_event['event_date'] ?? ''); ?>">
                    </div>

                    <div>
                        <label for="poster">Event Poster (JPG, PNG, WEBP, GIF &le; 2MB)</label>
                        <input type="file" id="poster" name="poster" accept=".jpg,.jpeg,.png,.webp,.gif">
                        <?php if (!empty($edit_event['poster']) && file_exists($upload_dir . $edit_event['poster'])): ?>
                            <small style="color: #64748b;">Current: <a href="uploads/posters/<?php echo htmlspecialchars($edit_event['poster']); ?>" target="_blank">View Poster</a></small>
                        <?php endif; ?>
                    </div>
                </div>

                <div style="margin-bottom: 15px;">
                    <label for="description" style="display:block; margin-bottom:5px; font-weight:600; font-size:0.9rem;">Event Description</label>
                    <textarea id="description" name="description" rows="3" placeholder="Provide details, schedule, or prerequisites..." style="width:100%; padding:9px; border:1px solid #cbd5e1; border-radius:6px;"><?php echo htmlspecialchars($edit_event['description'] ?? ''); ?></textarea>
                </div>

                <button type="submit" style="padding: 10px 20px; background-color: #3654d1; color: white; border: none; border-radius: 6px; cursor: pointer; font-weight: 600;">
                    <?php echo $edit_event ? 'Update Event' : 'Add Event'; ?>
                </button>

                <?php if ($edit_event): ?>
                    <a href="events.php" style="margin-left: 10px; color: #64748b; text-decoration: underline;">Cancel Edit</a>
                <?php endif; ?>
            </form>
        </section>

        <section class="card">
            <h2>Events Directory</h2>

            <form action="events.php" method="GET" class="filter-bar">
                <input type="text" name="search" placeholder="Search event by name or description..." 
                       value="<?php echo htmlspecialchars($search_query); ?>" style="flex: 1; min-width: 240px;">

                <select name="category">
                    <option value="">All Categories</option>
                    <option value="Workshop" <?php echo $filter_cat === 'Workshop' ? 'selected' : ''; ?>>Workshop</option>
                    <option value="Competition" <?php echo $filter_cat === 'Competition' ? 'selected' : ''; ?>>Competition</option>
                    <option value="Seminar" <?php echo $filter_cat === 'Seminar' ? 'selected' : ''; ?>>Seminar</option>
                    <option value="Other" <?php echo $filter_cat === 'Other' ? 'selected' : ''; ?>>Other</option>
                </select>

                <button type="submit">Search & Filter</button>

                <?php if (!empty($search_query) || !empty($filter_cat)): ?>
                    <a href="events.php" class="reset-btn">Reset</a>
                <?php endif; ?>
            </form>

            <table class="data-table">
                <thead>
                    <tr>
                        <th>Poster</th>
                        <th>Event Name</th>
                        <th>Category</th>
                        <th>Date</th>
                        <th>Description</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($events_result && $events_result->num_rows > 0): ?>
                        <?php while ($row = $events_result->fetch_assoc()): ?>
                            <tr>
                                <td>
                                    <?php if (!empty($row['poster']) && file_exists($upload_dir . $row['poster'])): ?>
                                        <a href="uploads/posters/<?php echo htmlspecialchars($row['poster']); ?>" target="_blank">
                                            <img src="uploads/posters/<?php echo htmlspecialchars($row['poster']); ?>" alt="Poster" class="poster-thumb">
                                        </a>
                                    <?php else: ?>
                                        <div class="poster-thumb">No Poster</div>
                                    <?php endif; ?>
                                </td>
                                <td><strong><?php echo htmlspecialchars($row['name']); ?></strong></td>
                                <td><span class="badge-cat"><?php echo htmlspecialchars($row['category']); ?></span></td>
                                <td><?php echo date("d M Y", strtotime($row['event_date'])); ?></td>
                                <td style="max-width: 260px; font-size: 0.9rem; color: #475569;">
                                    <?php echo htmlspecialchars($row['description'] ?? 'No description provided.'); ?>
                                </td>
                                <td>
                                    <a href="events.php?edit_id=<?php echo $row['id']; ?>" class="btn-edit">Edit</a>
                                    <a href="events.php?delete_id=<?php echo $row['id']; ?>" class="btn-delete"
                                       onclick="return confirm('Are you sure you want to delete this event and its poster?');">Delete</a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" style="text-align: center; color: #64748b; padding: 24px;">
                                No events found matching your search or filter criteria.
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
