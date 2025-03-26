<?php
include '../database/database.php';  // Include the database connection

session_start();
$conn = Database::connect();  // Establishing the database connection

if (isset($_POST['username']) && isset($_POST['password'])) {
    $username = $_POST['username'];
    $password = $_POST['password'];

    // Prepare statement to get the hashed password from the database
    $stmt = $conn->prepare("SELECT pwd_hash FROM iss_persons WHERE email = :email");
    $stmt->execute([':email' => $username]);

    if ($stmt->rowCount() > 0) {
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $hashed_password = $row['pwd_hash'];

        // Verify the password using password_verify
        if (password_verify($password, $hashed_password)) {
            $_SESSION['username'] = $username;
            header("Location: issues_list.php");
            exit();
        } else {
            $error = "Invalid password.";
        }
    } else {
        $error = "User not found.";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Login</title>
</head>
<body>
    <h2>Login</h2>
    <?php if (isset($error)) echo "<p>$error</p>"; ?>
    <form method="POST">
        <label>Username:</label>
        <input type="text" name="username" required><br>
        <label>Password:</label>
        <input type="password" name="password" required><br>
        <button type="submit">Login</button>
    </form>
    <a href="register.php">Don't have an account? Register here</a>
</body>
</html>
