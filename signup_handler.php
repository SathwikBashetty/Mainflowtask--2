<?php
// Start session
session_start();

// Database connection details - consider moving to config file
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "mydatabase";

// Create connection with error handling
try {
    $conn = new mysqli($servername, $username, $password, $dbname);
    
    // Check connection
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }
    
    // Validate CSRF token if implemented
    // if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    //     throw new Exception("Invalid CSRF token");
    // }

    // Validate all fields are present
    if (empty($_POST['username']) || empty($_POST['email']) || 
        empty($_POST['password']) || empty($_POST['confirm_password'])) {
        throw new Exception("All fields are required");
    }

    // Sanitize and validate input
    $name = trim($conn->real_escape_string($_POST['username']));
    $email = filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL);
    $pass = $_POST['password'];
    $confirm_pass = $_POST['confirm_password'];

    // Validate email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new Exception("Invalid email format");
    }

    // Check password match
    if ($pass !== $confirm_pass) {
        throw new Exception("Passwords do not match");
    }

    // Check password strength (optional)
    if (strlen($pass) < 8) {
        throw new Exception("Password must be at least 8 characters");
    }

    // Check if username already exists using prepared statement
    $stmt = $conn->prepare("SELECT id FROM users WHERE name = ?");
    $stmt->bind_param("s", $name);
    $stmt->execute();
    $stmt->store_result();
    
    if ($stmt->num_rows > 0) {
        throw new Exception("Username already exists");
    }
    $stmt->close();

    // Check if email already exists
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();
    
    if ($stmt->num_rows > 0) {
        throw new Exception("Email already registered");
    }
    $stmt->close();

    // Hash the password
    $hashed_password = password_hash($pass, PASSWORD_BCRYPT);

    // Insert user using prepared statement
    $stmt = $conn->prepare("INSERT INTO users (name, email, password) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $name, $email, $hashed_password);
    
    if ($stmt->execute()) {
        header("Location: signup.php?success=Account created successfully. Please login.");
        exit();
    } else {
        throw new Exception("Registration failed. Please try again.");
    }
    
    $stmt->close();
    $conn->close();
    
} catch (Exception $e) {
    // Log error for admin (in production)
    // error_log($e->getMessage());
    
    // Redirect with error message
    header("Location: signup.php?error=" . urlencode($e->getMessage()));
    exit();
}
