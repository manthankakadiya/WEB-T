<?php
header("Content-Type: application/json");
require_once "db_connect.php";

$method = $_SERVER["REQUEST_METHOD"];
$data = json_decode(file_get_contents("php://input"), true);

if ($method === "GET") {
    $result = $conn->query("SELECT * FROM students");
    $list = [];
    while ($row = $result->fetch_assoc()) {
        $list[] = $row;
    }
    echo json_encode($list);
}

if ($method === "POST") {
    $name = $data["name"];
    $email = $data["email"];
    $course = $data["course"];

    $stmt = $conn->prepare("INSERT INTO students (name, email, course) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $name, $email, $course);
    $stmt->execute();
    echo json_encode(["message" => "Student added successfully"]);
}

if ($method === "PUT") {
    $id = $data["id"];
    $name = $data["name"];
    $email = $data["email"];
    $course = $data["course"];

    $stmt = $conn->prepare("UPDATE students SET name = ?, email = ?, course = ? WHERE id = ?");
    $stmt->bind_param("sssi", $name, $email, $course, $id);
    $stmt->execute();
    echo json_encode(["message" => "Student updated successfully"]);
}

if ($method === "DELETE") {
    $id = $_GET["id"];

    $stmt = $conn->prepare("DELETE FROM students WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    echo json_encode(["message" => "Student deleted successfully"]);
}
?>
