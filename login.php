<?php
session_start();
include 'db.php'; // This should connect to your database

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    if (empty($username) || empty($password)) {
        header("Location: loginpage.php?error=Please fill in all fields.");
        exit();
    }

    // Prepare a statement to prevent SQL Injection
    $stmt = $conn->prepare("SELECT id, password FROM users WHERE name = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows === 1) {
        $stmt->bind_result($id, $hashed_password);
        $stmt->fetch();

        if (password_verify($password, $hashed_password)) {
            // Login successful
            $_SESSION['user_id'] = $id;
            $_SESSION['username'] = $username;
            header("Location: dashboard.php"); // Redirect to dashboard
            exit();
        } else {
            // Incorrect password
            header("Location: loginpage.php?error=Incorrect password.");
            exit();
        }
    } else {
        // Username not found
        header("Location: loginpage.php?error=User not found.");
        exit();
    }

    $stmt->close();
} else {
    header("Location: loginpage.php");
    exit();
}
?>
