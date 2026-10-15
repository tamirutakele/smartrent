<?php

session_start();

require_once "../config/database.php";


// Check admin login
if (!isset($_SESSION["user"]) || $_SESSION["role"] !== "admin") {
    header("Location: admin-login.php");
    exit;
}


// Check user ID
if (!isset($_GET["id"]) || !is_numeric($_GET["id"])) {
    header("Location: users.php");
    exit;
}

$id = (int) $_GET["id"];


// Get user information
$sql = "SELECT id, name, role
        FROM userss
        WHERE id = ?
        LIMIT 1";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    die("Database error: " . $conn->error);
}

$stmt->bind_param("i", $id);

$stmt->execute();

$result = $stmt->get_result();


// User not found
if ($result->num_rows !== 1) {

    $stmt->close();

    header("Location: users.php");
    exit;
}

$user = $result->fetch_assoc();

$stmt->close();


// Prevent deleting admin account
if ($user["role"] === "admin") {

    die("Admin accounts cannot be deleted.");
}


// Delete user
$sql = "DELETE FROM userss
        WHERE id = ?";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    die("Database error: " . $conn->error);
}

$stmt->bind_param("i", $id);

$stmt->execute();

$stmt->close();


// Return to users page
header("Location: users.php");

exit;

?>