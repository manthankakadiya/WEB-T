# Studenthub_Project - Integrated Web Portal

StudentHub is a web portal built with PHP, MySQL, JavaScript, HTML5, and CSS3. It combines user authentication, student management, event administration, poster file uploads, and an admin dashboard.

---

## Integrated Modules

1. **Admin Dashboard (`admin_dashboard.php`)**
   - Role-Based Access Control (RBAC) restricting access to admin accounts.
   - Real-time statistics: Total Students, Total Events, Registered Users, Audit Log entries.
   - Dynamic navigation menu customized by user role.
   - Recent student registrations table.
   - System audit log tracking administrative activities.

2. **Student Management System with Fetch API (`student_api.html` & `api.php`)**
   - Asynchronous CRUD operations without page reloads using JavaScript Fetch API.
   - RESTful JSON API handling GET, POST, PUT, and DELETE methods.
   - Live list view, create student, edit student, and delete student.

3. **Event Management System (`events.php`)**
   - Event creation, updating, deletion, and directory browsing.
   - Secure poster file upload handling with validation:
     - Allowed extensions: JPG, JPEG, PNG, WEBP, GIF.
     - Maximum size limit: 2 MB.
     - Binary MIME type inspection.
     - Unique server filename generation.
     - Automated file deletion upon record deletion or replacement.

4. **Secure Authentication & Registration (`login.php` & `register.php`)**
   - Secure password encryption using Bcrypt via PHP `password_hash()`.
   - Verification using `password_verify()`.
   - Duplicate email and mobile number prevention.
   - Automatic role-based routing (Admin to `admin_dashboard.php`, Student to `dashboard.html`).

---

## Test Login Credentials

| Role | Username | Password | Default Redirect |
| :--- | :--- | :--- | :--- |
| **Admin** | `admin` | `admin123` | `admin_dashboard.php` |
| **Student** | `student` | `student123` | `dashboard.html` |

---

## Local Deployment Instructions (XAMPP)

1. **Start Services:**
   - Launch XAMPP Control Panel.
   - Start Apache and MySQL modules.

2. **Import Database:**
   - Open phpMyAdmin: `http://localhost/phpmyadmin`
   - Create a new database named `student_hub_db` (or import `database.sql`).
   - Run or import the SQL script located at `database.sql`.

3. **Access the Portal:**
   - Home Portal: `http://localhost/Student_hub/index.html`
   - Admin Dashboard: `http://localhost/Student_hub/admin_dashboard.php`
   - Student Management (API): `http://localhost/Student_hub/student_api.html`
   - Event Management: `http://localhost/Student_hub/events.php`
   - Login: `http://localhost/Student_hub/login.php`
   - Register: `http://localhost/Student_hub/register.php`

---

## REST API Endpoints (`api.php`)

| Method | Endpoint | Description | Payload Format |
| :--- | :--- | :--- | :--- |
| `GET` | `api.php` | Retrieve all student records | None |
| `POST` | `api.php` | Add a new student record | `{"name":"...","email":"...","course":"..."}` |
| `PUT` | `api.php` | Update an existing student | `{"id":3,"name":"...","email":"...","course":"..."}` |
| `DELETE`| `api.php?id={id}`| Remove student record | Query parameter `id` |

